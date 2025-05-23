<?php
session_start();
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

    // 2) Leer inventario actual
    $invPath   = __DIR__ . '/Inventario/inventario.csv';
    $inventory = [];
    if (($h = fopen($invPath, 'r')) !== false) {
        $invHeaders = fgetcsv($h);
        while (($row = fgetcsv($h)) !== false) {
            $inventory[$row[0]] = [
                'Descripcion'   => $row[1],
                'Inventario'    => intval($row[2]),
                'UsuarioUpdate' => $row[3],
                'Fecha'         => $row[4],
            ];
        }
        fclose($h);
    }

    // 3) Actualizar cada ítem
    foreach ($productions as $prod) {
        [$code, $desc, $qty] = [$prod[0], $prod[1], intval($prod[2])];
        if (isset($inventory[$code])) {
            $inventory[$code]['Inventario']    += $qty;
            $inventory[$code]['UsuarioUpdate']  = $validator;
            $inventory[$code]['Fecha']          = $now;
        } else {
            // Si no existe, lo agregamos
            $inventory[$code] = [
                'Descripcion'   => $desc,
                'Inventario'    => $qty,
                'UsuarioUpdate' => $validator,
                'Fecha'         => $now,
            ];
        }
    }

    // 4) Volver a escribir el CSV de inventario
    if (($h = fopen($invPath, 'w')) !== false) {
        fputcsv($h, ['CodigoPROD','Descripcion','Inventario','UsuarioUpdate','Fecha']);
        foreach ($inventory as $code => $info) {
            fputcsv($h, [
                $code,
                $info['Descripcion'],
                $info['Inventario'],
                $info['UsuarioUpdate'],
                $info['Fecha'],
            ]);
        }
        fclose($h);
    }

    // 5) Eliminar el archivo pendiente
    unlink($pendPath);

    echo json_encode(['status'=>'ok']);
    exit;
}