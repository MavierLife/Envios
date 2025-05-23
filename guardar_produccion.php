<?php
session_start();
require_once 'Config/Database.php';           // ← corrije la barra y nombre de archivo

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'error'    => 'Session expired',
        'redirect' => 'login.php?session_expired=true'
    ]);
    exit;
}

$produccion = $_POST['produccion'] ?? [];
if (!is_array($produccion) || empty($produccion)) {
    header('Location: Produccion.php');
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
    die('Error de conexión a la base de datos');
}

// Obtener descripciones de los productos
$codigos       = array_keys($produccion);
$placeholders  = implode(',', array_fill(0, count($codigos), '?'));
$sql           = "SELECT CodigoPROD, Descripcion FROM tblcatalogodeproductos WHERE CodigoPROD IN ($placeholders)";
$stmt          = $db->prepare($sql);
$stmt->execute($codigos);
$descs         = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Armar las líneas tab-delimitadas
$lineas = [];
foreach ($produccion as $codigo => $cantidad) {
    $cantidad = floatval($cantidad);
    if ($cantidad <= 0) continue;    // ← ignorar si la cantidad es 0 o negativa
    $descripcion = $descs[$codigo] ?? '';
    $lineas[]    = implode("\t", [
        $codigo,
        $descripcion,
        $cantidad,
        $userName,  // ← aquí usar $userName
        $fecha
    ]);
}

// Directorio y nombre de archivo .csv
$dir      = __DIR__ . DIRECTORY_SEPARATOR . 'ProdPendientes';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$nombre   = 'produccion_' . date('Ymd_His') . "_{$userCode}.csv";
$rutaFile = $dir . DIRECTORY_SEPARATOR . $nombre;

// Crear y escribir CSV
$fp = fopen($rutaFile, 'w');
// Cabecera
fputcsv($fp, ['CodigoPROD','Descripcion','Produccion','Usuario','Fecha']);
// Filas de datos
foreach ($produccion as $codigo => $cantidad) {
    $cantidad = floatval($cantidad);
    if ($cantidad <= 0) continue;
    $descripcion = $descs[$codigo] ?? '';
    fputcsv($fp, [
        $codigo,
        $descripcion,
        $cantidad,
        $userName,  // ← aquí usar $userName
        $fecha
    ]);
}
fclose($fp);

// Redirigir de vuelta a Producción dentro del SPA
header('Location: Produccion.php?guardado=ok');
exit;