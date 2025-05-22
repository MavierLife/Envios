<?php
session_start();

// --- MOVER CONTEO ANTES DEL GUARDIA AJAX ---
$csvFiles = glob(__DIR__ . '/ProdPendientes/*.csv');
$validacionesPendiente = $csvFiles ? count($csvFiles) : 0;

// Calcular unidades totales pendientes
$unidadesPendientes = 0;
foreach ($csvFiles as $file) {
    if (($handle = fopen($file, 'r')) !== false) {
        $headers   = fgetcsv($handle);
        $prodIndex = $headers ? array_search('Produccion', $headers) : false;
        if ($prodIndex !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $unidadesPendientes += isset($row[$prodIndex])
                    ? (int) $row[$prodIndex]
                    : 0;
            }
        }
        fclose($handle);
    }
}
// ---------------------------------------------

// --- INICIO DE LA MODIFICACIÓN PARA OPCIÓN 1 ---
// Si no es una solicitud AJAX Y se accede directamente a este archivo
if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $currentPage = basename(__FILE__);
    $pageNameWithoutExtension = pathinfo($currentPage, PATHINFO_FILENAME); // obtiene “dashboard”
    header('Location: index.php#' . $pageNameWithoutExtension);
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
                            <!-- Antes: <i class="icon-cart5 fa-5x"></i> -->
                            <i class="icon-stack-check fa-5x"></i>  <!-- Validaciones pendientes -->
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $validacionesPendiente; ?></div>
                            <div>Validaciones pendiente</div>
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
                            <!-- Antes: <i class="icon-stats-dots fa-5x"></i> -->
                            <i class="icon-stats-bars fa-5x"></i>   <!-- Unidades totales pendientes -->
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $unidadesPendientes; ?></div>
                            <div>Unidades totales pendientes</div>
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
                    <h3 class="panel-title">
                        <!-- Mantener icon-clock para actividad reciente -->
                        <i class="icon-clock fa-lg"></i> Actividad Reciente
                    </h3>
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