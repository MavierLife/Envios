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

if (empty($tiendaUUID)) {
    echo json_encode(['status' => 'error', 'message' => 'UUID de tienda requerido']);
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
    $productosInventario = $inventario->obtenerProductos();
    
    // Crear array de códigos disponibles en inventario
    $codigosInventario = array_column($productosInventario, 'codigo');
    
    if (empty($codigosInventario)) {
        echo json_encode([
            'status' => 'ok',
            'productos' => [],
            'inventario' => [],
            'message' => 'No hay productos en el inventario'
        ]);
        exit;
    }
    
    // Crear placeholders para la consulta
    $placeholders = implode(',', array_fill(0, count($codigosInventario), '?'));
    
    // Consultar productos de la tienda que estén en el inventario
    // Unir con tblcatalogodeproductos para obtener la descripción
    $query = "SELECT ps.CodigoPROD, tcp.Descripcion, ps.Existencia, ps.UUIDSucursal
              FROM tblproductossucursal ps
              LEFT JOIN tblcatalogodeproductos tcp ON ps.CodigoPROD = tcp.CodigoPROD
              WHERE ps.UUIDSucursal = ? 
              AND ps.CodigoPROD IN ($placeholders)
              ORDER BY tcp.Descripcion ASC";
    
    $stmt = $db->prepare($query);
    $params = array_merge([$tiendaUUID], $codigosInventario);
    $stmt->execute($params);
    
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filtrar productos que no tienen descripción (por si no están en el catálogo)
    $productosValidos = array_filter($productos, function($producto) {
        return !empty($producto['Descripcion']);
    });
    
    echo json_encode([
        'status' => 'ok',
        'productos' => array_values($productosValidos), // Reindexar array
        'inventario' => $productosInventario,
        'count' => count($productosValidos)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>