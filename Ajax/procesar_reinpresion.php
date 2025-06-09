<?php
session_start();
require_once '../Config/Database.php';

header('Content-Type: application/json');

// Validar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

// Validar parámetros
if (!isset($_POST['archivo']) || !isset($_POST['nombre'])) {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
    exit;
}

$archivo = $_POST['archivo'];
$nombre = $_POST['nombre'];

// Verificar que el archivo existe
if (!file_exists($archivo)) {
    echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
    exit;
}

try {
    // Determinar el tipo de archivo basado en la ruta
    $tipo = 'produccion'; // por defecto
    $carpeta = 'ProdValidadas'; // por defecto para producciones validadas
    
    if (strpos($archivo, 'EnviosRealizados') !== false) {
        $tipo = 'envio';
        $carpeta = 'EnviosRealizados';
    } elseif (strpos($archivo, 'ProdValidadas') !== false) {
        $carpeta = 'ProdValidadas'; // producciones validadas
    } elseif (strpos($archivo, 'ProdPendientes') !== false) {
        $carpeta = 'ProdPendientes'; // por si acaso hay alguna pendiente
    }
    
    // Construir la URL para generar_ticket.php con la carpeta correcta
    $ticketUrl = 'generar_ticket.php?file=' . urlencode($nombre) . '&tipo=' . $tipo . '&carpeta=' . $carpeta . '&autoprint=true';
    
    echo json_encode([
        'success' => true,
        'message' => 'Redirigiendo a impresión...',
        'redirect' => $ticketUrl
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>