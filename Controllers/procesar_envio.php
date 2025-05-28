<?php
session_start();
require_once '../Config/Database.php';
require_once '../Config/Inventario.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$tiendaUUID = $_POST['tienda_uuid'] ?? '';
$envios = $_POST['envios'] ?? [];

if (empty($tiendaUUID) || !is_array($envios) || empty($envios)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos de envío incompletos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos']);
        exit;
    }
    
    // Obtener inventario actual
    $inventario = new Inventario();
    $usuario = $_SESSION['user_name'] ?? 'Sistema';
    
    // Iniciar transacción
    $db->beginTransaction();
    
    $enviosRealizados = [];
    $errores = [];
    
    foreach ($envios as $codigo => $cantidad) {
        $cantidad = floatval($cantidad);
        if ($cantidad <= 0) continue;
        
        // Verificar inventario disponible
        $productoInventario = $inventario->obtenerProducto($codigo);
        if (!$productoInventario) {
            $errores[] = "Producto $codigo no encontrado en inventario";
            continue;
        }
        
        $inventarioDisponible = $productoInventario['inventario'];
        if ($cantidad > $inventarioDisponible) {
            $errores[] = "Cantidad insuficiente para producto $codigo (disponible: $inventarioDisponible, solicitado: $cantidad)";
            continue;
        }
        
        // Obtener existencia actual en la tienda
        $queryExistencia = "SELECT Existencia FROM tblproductossucursal 
                           WHERE UUIDSucursal = ? AND CodigoPROD = ?";
        $stmtExistencia = $db->prepare($queryExistencia);
        $stmtExistencia->execute([$tiendaUUID, $codigo]);
        $existenciaActual = $stmtExistencia->fetchColumn();
        
        if ($existenciaActual === false) {
            $errores[] = "Producto $codigo no encontrado en la tienda";
            continue;
        }
        
        // Calcular nueva existencia (sumar, no reemplazar)
        $nuevaExistencia = $existenciaActual + $cantidad;
        
        // Actualizar existencia en la tienda
        $queryUpdate = "UPDATE tblproductossucursal 
                       SET Existencia = ? 
                       WHERE UUIDSucursal = ? AND CodigoPROD = ?";
        $stmtUpdate = $db->prepare($queryUpdate);
        
        if (!$stmtUpdate->execute([$nuevaExistencia, $tiendaUUID, $codigo])) {
            $errores[] = "Error al actualizar existencia para producto $codigo";
            continue;
        }
        
        // Reducir inventario
        $nuevoInventario = $inventarioDisponible - $cantidad;
        if (!$inventario->actualizarInventario($codigo, $nuevoInventario, $usuario, $productoInventario['descripcion'])) {
            $errores[] = "Error al actualizar inventario para producto $codigo";
            continue;
        }
        
        $enviosRealizados[] = [
            'codigo' => $codigo,
            'descripcion' => $productoInventario['descripcion'],
            'cantidad' => $cantidad,
            'existencia_anterior' => $existenciaActual,
            'existencia_nueva' => $nuevaExistencia,
            'inventario_anterior' => $inventarioDisponible,
            'inventario_nuevo' => $nuevoInventario
        ];
    }
    
    if (!empty($errores)) {
        $db->rollBack();
        echo json_encode([
            'status' => 'error',
            'message' => 'Errores en el procesamiento',
            'errors' => $errores
        ]);
        exit;
    }
    
    if (empty($enviosRealizados)) {
        $db->rollBack();
        echo json_encode([
            'status' => 'error',
            'message' => 'No se procesaron envíos válidos'
        ]);
        exit;
    }
    
    // Confirmar transacción
    $db->commit();
    
    // Generar registro de envío (opcional - similar al CSV de producción)
    $fecha = date('Y-m-d H:i:s');
    $envioDir = dirname(__DIR__) . '/EnviosRealizados';
    if (!is_dir($envioDir)) {
        mkdir($envioDir, 0755, true);
    }
    
    $archivoEnvio = $envioDir . '/envio_' . date('Ymd_His') . '_' . substr($tiendaUUID, 0, 8) . '.csv';
    $fp = fopen($archivoEnvio, 'w');
    
    // Cabecera del CSV
    fputcsv($fp, ['CodigoPROD', 'Descripcion', 'CantidadEnviada', 'ExistenciaAnterior', 'ExistenciaNueva', 'TiendaUUID', 'Usuario', 'Fecha']);
    
    // Datos del envío
    foreach ($enviosRealizados as $envio) {
        fputcsv($fp, [
            $envio['codigo'],
            $envio['descripcion'],
            $envio['cantidad'],
            $envio['existencia_anterior'],
            $envio['existencia_nueva'],
            $tiendaUUID,
            $usuario,
            $fecha
        ]);
    }
    
    fclose($fp);
    
    echo json_encode([
        'status' => 'ok',
        'message' => 'Envío procesado correctamente',
        'envios_realizados' => count($enviosRealizados),
        'archivo' => basename($archivoEnvio),
        'detalles' => $enviosRealizados
    ]);
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>