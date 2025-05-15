<?php
session_start();

// --- INICIO DE LA MODIFICACIÓN PARA OPCIÓN 1 ---
// Si no es una solicitud AJAX Y se accede directamente a este archivo
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    $currentPage = basename(__FILE__); // Obtiene "produccion.php"
    header('Location: index.php#' . $currentPage);
    exit;
}
// --- FIN DE LA MODIFICACIÓN ---

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // No Autorizado
    echo json_encode(['error' => 'Session expired', 'redirect' => 'login.php?session_expired=true']);
    exit;
}

// Opcional: Verificación de rol específico para esta página
/*
if (!isset($_SESSION['user_acceso']) || $_SESSION['user_acceso'] !== 'rol_para_produccion') {
    http_response_code(403); // Prohibido
    echo json_encode(['error' => 'Access denied', 'message' => 'No tienes permiso para ver la sección de Producción.']);
    exit;
}
*/
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Módulo de Producción</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="icon-stack"></i> Gestión de Producción</h3>
                </div>
                <div class="panel-body">
                    <p>Aquí va el contenido específico para el módulo de producción.</p>
                    <p>Por ejemplo, podrías tener tablas para órdenes de producción, seguimiento de procesos, etc.</p>

                    <h4>Acciones Rápidas:</h4>
                    <ul>
                        <li><a href="#">Crear Nueva Orden de Producción</a></li>
                        <li><a href="#">Ver Órdenes Activas</a></li>
                        <li><a href="#">Reporte de Producción Semanal</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>