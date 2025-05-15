<?php
session_start();

// --- INICIO DE LA MODIFICACIÓN PARA OPCIÓN 1 ---
// Si no es una solicitud AJAX Y se accede directamente a este archivo
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    $currentPage = basename(__FILE__); // Obtiene "dashboard.php"
    // Asumiendo que index.php está en el mismo directorio (directorio raíz de /Envios/)
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
if (!isset($_SESSION['user_acceso']) || $_SESSION['user_acceso'] !== 'rol_para_dashboard') {
    http_response_code(403); // Prohibido
    echo json_encode(['error' => 'Access denied', 'message' => 'No tienes permiso para ver el Dashboard.']);
    exit;
}
*/
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Dashboard</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="icon-cart5 fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge">26</div>
                            <div>Nuevas Órdenes!</div>
                        </div>
                    </div>
                </div>
                <a href="#">
                    <div class="panel-footer">
                        <span class="pull-left">Ver Detalles</span>
                        <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="panel panel-green">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="icon-stats-dots fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge">12</div>
                            <div>Nuevas Tareas!</div>
                        </div>
                    </div>
                </div>
                <a href="#">
                    <div class="panel-footer">
                        <span class="pull-left">Ver Detalles</span>
                        <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="icon-clock"></i> Actividad Reciente</h3>
                </div>
                <div class="panel-body">
                    <p>Aquí iría la actividad reciente del sistema o más gráficos.</p>
                    <p>Bienvenido, <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuario'; ?>.</p>
                    <p>Tu nivel de acceso es: <?php echo isset($_SESSION['user_acceso']) ? htmlspecialchars($_SESSION['user_acceso']) : 'No definido'; ?>.</p>
                </div>
            </div>
        </div>
    </div>
</div>