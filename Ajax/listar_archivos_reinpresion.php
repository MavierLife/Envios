<?php
session_start();
require_once '../Config/Database.php';

header('Content-Type: application/json');

// Validar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

// Validar tipo
if (!isset($_POST['tipo'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo no especificado']);
    exit;
}

$tipo = $_POST['tipo'];
$carpetas = [];

// Determinar carpetas según el tipo
switch($tipo) {
    case 'envios':
        $carpetas = ['../EnviosRealizados/'];
        break;
    case 'producciones':
        // Buscar en ambas carpetas: validadas Y pendientes
        $carpetas = ['../ProdValidadas/', '../ProdPendientes/'];
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Tipo no válido']);
        exit;
}

try {
    $listaArchivos = [];
    
    // Buscar archivos en todas las carpetas especificadas
    foreach ($carpetas as $carpeta) {
        // Verificar que la carpeta existe
        if (!is_dir($carpeta)) {
            continue; // Si la carpeta no existe, continuar con la siguiente
        }
        
        // Obtener archivos CSV de la carpeta
        $archivos = glob($carpeta . '*.csv');
        
        foreach ($archivos as $archivo) {
            $nombreArchivo = basename($archivo);
            $fechaCreacion = date('d/m/Y H:i', filemtime($archivo));
            $tamaño = filesize($archivo);
            
            // Formatear tamaño
            if ($tamaño > 1024) {
                $tamañoFormateado = round($tamaño / 1024, 1) . ' KB';
            } else {
                $tamañoFormateado = $tamaño . ' B';
            }
            
            // Determinar estado basado en la carpeta
            $estado = '';
            if (strpos($archivo, 'ProdValidadas') !== false) {
                $estado = ' (Validada)';
            } elseif (strpos($archivo, 'ProdPendientes') !== false) {
                $estado = ' (Pendiente)';
            }
            
            $listaArchivos[] = [
                'nombre' => $nombreArchivo,
                'ruta' => $archivo,
                'fecha' => $fechaCreacion,
                'tamaño' => $tamañoFormateado,
                'estado' => $estado
            ];
        }
    }
    
    // Ordenar por fecha de creación (más recientes primero)
    usort($listaArchivos, function($a, $b) {
        return filemtime($b['ruta']) - filemtime($a['ruta']);
    });
    
    echo json_encode([
        'success' => true,
        'archivos' => $listaArchivos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al listar archivos: ' . $e->getMessage()
    ]);
}
?>