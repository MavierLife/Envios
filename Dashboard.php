<?php
session_start();
require_once 'Config/Database.php';
require_once 'Config/Inventario.php';

// Configurar zona horaria de El Salvador
date_default_timezone_set('America/El_Salvador');

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

// Leer datos del inventario con la nueva clase
$inventarioObj = new Inventario();
$inventario = $inventarioObj->obtenerProductos();

// --- OBTENER NOMBRES DE PRODUCTOS DESDE LA BASE DE DATOS ---
$inventarioConNombres = [];
if (!empty($inventario)) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            // Obtener todos los códigos del inventario
            $codigos = array_column($inventario, 'codigo');
            
            if (!empty($codigos)) {
                // Crear placeholders para la consulta
                $placeholders = implode(',', array_fill(0, count($codigos), '?'));
                
                // Consultar las descripciones desde la base de datos
                $query = "SELECT CodigoPROD, Descripcion 
                         FROM tblcatalogodeproductos 
                         WHERE CodigoPROD IN ($placeholders)";
                
                $stmt = $db->prepare($query);
                $stmt->execute($codigos);
                $productosDB = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Crear array asociativo para búsqueda rápida
                $descripcionesDB = [];
                foreach ($productosDB as $producto) {
                    $descripcionesDB[$producto['CodigoPROD']] = $producto['Descripcion'];
                }
                
                // Combinar datos del inventario con descripciones de la BD
                foreach ($inventario as $item) {
                    $codigo = $item['codigo'];
                    $inventarioConNombres[] = [
                        'codigo' => $codigo,
                        'descripcion' => $descripcionesDB[$codigo] ?? $item['descripcion'], // Usar BD o fallback al JSON
                        'inventario' => $item['inventario'],
                        'usuarioUpdate' => $item['usuarioUpdate'],
                        'fecha' => $item['fecha']
                    ];
                }
            }
        } else {
            // Si no hay conexión, usar datos del JSON
            $inventarioConNombres = $inventario;
        }
    } catch (Exception $e) {
        // En caso de error, usar datos del JSON
        error_log("Error al obtener descripciones de productos: " . $e->getMessage());
        $inventarioConNombres = $inventario;
    }
} else {
    $inventarioConNombres = $inventario;
}

// --- INICIO DE LA MODIFICACIÓN PARA OPCIÓN 1 ---
// Si no es una solicitud AJAX Y se accede directamente a este archivo
if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $currentPage = basename(__FILE__);
    $pageNameWithoutExtension = pathinfo($currentPage, PATHINFO_FILENAME); // obtiene "dashboard"
    header('Location: index.php#' . $pageNameWithoutExtension);
    exit;
}
// --- FIN DE LA MODIFICACIÓN ---

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // No Autorizado
    echo json_encode(['error' => 'Session expired', 'redirect' => 'login.php?session_expired=true']);
    exit;
}
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
                            <i class="icon-stack-check fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $validacionesPendiente; ?></div>
                            <div>Validaciones pendiente</div>
                        </div>
                    </div>
                </div>
                <a href="#validacion">
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
                            <i class="icon-stats-bars fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $unidadesPendientes; ?></div>
                            <div>Unidades totales pendientes</div>
                        </div>
                    </div>
                </div>
                <a href="#validacion">
                    <div class="panel-footer">
                        <span class="pull-left">Ver Detalles</span>
                        <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Panel de inventario actual -->
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="icon-archive"></i> Inventario Actual
                        <?php if (!empty($inventarioConNombres)): ?>
                            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem; margin-left: 0.5rem;">
                                <?php echo count($inventarioConNombres); ?> productos
                            </span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($inventarioConNombres)): ?>
                        <div class="alert alert-info">
                            <i class="icon-info" style="margin-right: 0.5rem;"></i>
                            <strong>Sin productos en inventario</strong><br>
                            No hay productos registrados en el inventario.
                        </div>
                    <?php else: ?>
                        <div class="products-grid">
                            <?php foreach ($inventarioConNombres as $producto): 
                                $codigo = htmlspecialchars($producto['codigo']);
                                $descripcion = htmlspecialchars($producto['descripcion']);
                                $cantidad = (int)$producto['inventario'];
                                $usuario = htmlspecialchars($producto['usuarioUpdate']);
                                $fecha = htmlspecialchars($producto['fecha']);
                                
                                // Determinar clase de tarjeta basada en inventario
                                $cardClass = $cantidad > 0 ? 'has-production' : '';
                                $badgeClass = $cantidad > 0 ? '' : 'zero';
                            ?>
                            <div class="product-card <?php echo $cardClass; ?>" 
                                 id="inv-card-<?php echo $codigo; ?>"
                                 data-codigoprod="<?php echo $codigo; ?>"
                                 data-descripcion="<?php echo $descripcion; ?>">
                                
                                <div class="production-badge <?php echo $badgeClass; ?>" id="inv-badge-<?php echo $codigo; ?>">
                                    <span><?php echo $cantidad; ?></span>
                                </div>
                                
                                <div class="product-header">
                                    <div class="product-icon">
                                        <i class="icon-archive"></i>
                                    </div>
                                    <div class="product-info">
                                        <div class="product-name"><?php echo $descripcion; ?></div>
                                        <div class="product-meta">
                                            <span>
                                                <i class="icon-barcode"></i>
                                                Código: <?php echo $codigo; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="production-controls">
                                    <div class="production-display">
                                        <span>En inventario:</span>
                                        <span class="production-value"><?php echo $cantidad; ?></span>
                                    </div>
                                    <div class="product-meta">
                                        <span>
                                            <i class="icon-user"></i>
                                            Actualizado por: <?php echo $usuario; ?>
                                        </span>
                                        <span>
                                            <i class="icon-calendar"></i>
                                            Fecha: <?php echo date('d/m/Y g:i A', strtotime($fecha)); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
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

<!-- Añadir estilos de produccion.css para las tarjetas -->
<link rel="stylesheet" href="Css/produccion.css">
<style>
    /* Estilos adicionales para las tarjetas de inventario */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    /* Modificar colores para diferenciar de producción */
    #inv-card .production-badge {
        background: #17a2b8; /* Color azul para inventario */
    }
    
    .product-card.has-production .product-icon {
        background: #28a745; /* Verde para productos con inventario */
    }
    
    /* Ajustes para móvil */
    @media (max-width: 767px) {
        .products-grid {
            grid-template-columns: 1fr;
        }
    }
</style>