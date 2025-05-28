<?php
session_start();
require_once 'Config/Database.php';

// Configurar zona horaria de El Salvador
date_default_timezone_set('America/El_Salvador');

// --- INICIO DE LA SECCIÓN PARA EVITAR CARGA DIRECTA SIN ESTILOS ---
// Si no es una solicitud AJAX Y se accede directamente a este archivo
if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $currentPage = basename(__FILE__);
    $pageNameWithoutExtension = pathinfo($currentPage, PATHINFO_FILENAME); // obtiene "validacion"
    header('Location: index.php#' . $pageNameWithoutExtension);
    exit;
}
// --- FIN DE LA SECCIÓN ---

// --- INICIO VALIDACIÓN DE SESIÓN ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // No Autorizado
    echo json_encode(['error' => 'Session expired', 'redirect' => 'login.php?session_expired=true']);
    exit;
}
// --- FIN VALIDACIÓN DE SESIÓN ---

// Obtener todos los .csv de ProdPendientes
$csvFiles = glob(__DIR__ . '/ProdPendientes/*.csv');
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="Css/validacion.css">

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <i class="icon-check" style="margin-right: 0.5rem; color: #667eea;"></i>
                Validación de Producciones
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="icon-list"></i>
                        Producciones Pendientes
                        <?php if (!empty($csvFiles)): ?>
                            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem; margin-left: 0.5rem;">
                                <?php echo count($csvFiles); ?>
                            </span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($csvFiles)): ?>
                        <div class="alert alert-info">
                            <i class="icon-info" style="margin-right: 0.5rem;"></i>
                            <strong>Sin producciones pendientes</strong><br>
                            No hay producciones disponibles para validar en este momento.
                        </div>
                    <?php else: ?>
                        <div class="productions-list">
                            <?php foreach ($csvFiles as $csv): 
                                $file = htmlspecialchars(basename($csv));
                                $fileSize = filesize($csv);
                                $fileSizeFormatted = $fileSize > 1024 ? round($fileSize/1024, 1) . ' KB' : $fileSize . ' B';
                                $fileDate = date('d/m/Y g:i A', filemtime($csv));
                            ?>
                            <div class="production-item" data-file="<?php echo $file; ?>">
                                <div class="production-header">
                                    <div class="production-icon">
                                        <i class="icon-file-text"></i>
                                    </div>
                                    <div class="production-info">
                                        <div class="production-name"><?php echo $file; ?></div>
                                        <div class="production-meta" style="font-size: 0.8rem; color: #6c757d; margin-top: 0.25rem;">
                                            <span><i class="icon-calendar" style="margin-right: 0.25rem;"></i><?php echo $fileDate; ?></span>
                                            <span style="margin-left: 1rem;"><i class="icon-database" style="margin-right: 0.25rem;"></i><?php echo $fileSizeFormatted; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="production-actions">
                                    <button class="action-btn btn-revisar revisar-btn" data-file="<?php echo $file; ?>" title="Revisar producción">
                                        <i class="icon-eye"></i>
                                        <span>Revisar</span>
                                    </button>
                                    <button class="action-btn btn-aceptar validar-btn" data-file="<?php echo $file; ?>" title="Aceptar producción">
                                        <i class="icon-check"></i>
                                        <span>Aceptar</span>
                                    </button>
                                    <button class="action-btn btn-rechazar rechazar-btn" data-file="<?php echo $file; ?>" title="Rechazar producción">
                                        <i class="icon-cross2"></i>
                                        <span>Rechazar</span>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar detalle del CSV -->
<div class="modal fade" id="csvDetalleModal" tabindex="-1" role="dialog" aria-labelledby="csvDetalleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="csvDetalleModalLabel">
                    <i class="icon-file-text" style="margin-right: 0.5rem;"></i>
                    Detalle: <span id="detalleNombre"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="csvDetalleContent">
                <div class="loading">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="Js/validacion.js"></script>