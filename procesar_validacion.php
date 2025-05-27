<?php
session_start();
require_once 'Config/Inventario.php';

if (!isset($_SESSION['user_name'])) {
    http_response_code(401);
    echo json_encode(['error'=>'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';
$file   = basename($_POST['file'] ?? '');

if ($action === 'aceptar') {
    $pendPath = __DIR__ . '/ProdPendientes/' . $file;
    if (!is_readable($pendPath)) {
        http_response_code(400);
        echo json_encode(['error'=>'Archivo pendiente no encontrado']);
        exit;
    }

    $validator = $_SESSION['user_name'];
    $now       = date('Y-m-d H:i:s');

    // 1) Leer producciones pendientes
    $productions = [];
    if (($h = fopen($pendPath, 'r')) !== false) {
        fgetcsv($h); // descartar cabecera
        while (($row = fgetcsv($h)) !== false) {
            // [0]=CodigoPROD, [1]=Descripcion, [2]=Produccion
            $productions[] = $row;
        }
        fclose($h);
    }

    // 2) Actualizar inventario con la nueva clase
    $inventario = new Inventario();
    
    // 3) Actualizar cada ítem
    foreach ($productions as $prod) {
        [$code, $desc, $qty] = [$prod[0], $prod[1], intval($prod[2])];
        
        $productoActual = $inventario->obtenerProducto($code);
        $nuevaCantidad = ($productoActual ? $productoActual['inventario'] : 0) + $qty;
        
        $inventario->actualizarInventario($code, $nuevaCantidad, $validator, $desc);
    }

    // 4) Eliminar el archivo pendiente
    unlink($pendPath);

    echo json_encode(['status'=>'ok']);
    exit;
}