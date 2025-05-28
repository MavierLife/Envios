<?php
session_start();
require_once '../Config/Database.php';

// Configurar zona horaria de El Salvador
date_default_timezone_set('America/El_Salvador');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'error'    => 'Session expired',
        'redirect' => '../login.php?session_expired=true'
    ]);
    exit;
}

$produccion = $_POST['produccion'] ?? [];
if (!is_array($produccion) || empty($produccion)) {
    echo json_encode(['status' => 'error', 'message' => 'No hay producción para guardar']);
    exit;
}

// Código EMP del usuario logueado
$userCode     = $_SESSION['user_code'] ?? $_SESSION['user_id'];
// Nombre completo del usuario (sin truncar)
$userFullName = $_SESSION['user_name'] ?? '';
$userName     = $userFullName;

$fecha = date('Y-m-d H:i:s');

// Conexión a la BD
$db = (new Database())->getConnection();
if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener descripciones y precios de los productos
$codigos       = array_keys($produccion);
$placeholders  = implode(',', array_fill(0, count($codigos), '?'));
$sql           = "SELECT CodigoPROD, Descripcion, PrecioCosto, Unidades FROM tblcatalogodeproductos WHERE CodigoPROD IN ($placeholders)";
$stmt          = $db->prepare($sql);
$stmt->execute($codigos);
$productos     = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear arrays asociativos para fácil acceso
$descs = [];
$precios = [];
foreach ($productos as $producto) {
    $descs[$producto['CodigoPROD']] = $producto['Descripcion'];
    $unidades = (float)$producto['Unidades'];
    $precioCosto = (float)$producto['PrecioCosto'];
    // Calcular precio por unidad
    $precios[$producto['CodigoPROD']] = $unidades > 0 ? ($precioCosto / $unidades) : 0;
}

// Directorio y nombre de archivo .csv
$dir      = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ProdPendientes';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$nombre   = 'produccion_' . date('Ymd_His') . "_{$userCode}.csv";
$rutaFile = $dir . DIRECTORY_SEPARATOR . $nombre;

// Crear y escribir CSV
$fp = fopen($rutaFile, 'w');
// Cabecera - agregar PrecioUnidad
fputcsv($fp, ['CodigoPROD','Descripcion','Produccion','Usuario','Fecha','PrecioUnidad']);
// Filas de datos
foreach ($produccion as $codigo => $cantidad) {
    $cantidad = floatval($cantidad);
    if ($cantidad <= 0) continue;
    $descripcion = $descs[$codigo] ?? '';
    $precioUnidad = $precios[$codigo] ?? 0;
    fputcsv($fp, [
        $codigo,
        $descripcion,
        $cantidad,
        $userName,
        $fecha,
        number_format($precioUnidad, 2)
    ]);
}
fclose($fp);

// Devolver respuesta JSON en lugar de redireccionar
echo json_encode([
    'status' => 'ok',
    'message' => 'Producción guardada correctamente',
    'file' => $nombre
]);
exit;