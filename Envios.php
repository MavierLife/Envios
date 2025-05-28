<?php
session_start();
require_once 'Config/Database.php';
require_once 'Config/Inventario.php';

// Verificar acceso directo sin AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $currentPage = basename(__FILE__);
    $pageNameWithoutExtension = pathinfo($currentPage, PATHINFO_FILENAME);
    header('Location: index.php#' . $pageNameWithoutExtension);
    exit;
}

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Session expired', 'redirect' => 'login.php?session_expired=true']);
    exit;
}

$tiendas = [];
$productos = [];
$error_db = '';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db) {
        // Obtener tiendas únicas - solo UUID sin nombre ya que no existe la columna
        $queryTiendas = "SELECT DISTINCT UUIDSucursal
                         FROM tblproductossucursal 
                         WHERE UUIDSucursal IS NOT NULL
                         ORDER BY UUIDSucursal ASC";
        
        $stmtTiendas = $db->prepare($queryTiendas);
        $stmtTiendas->execute();
        $tiendasResultado = $stmtTiendas->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear los resultados para mostrar un nombre descriptivo
        foreach ($tiendasResultado as $tienda) {
            $tiendas[] = [
                'UUIDSucursal' => $tienda['UUIDSucursal'],
                'NombreTienda' => 'Tienda - ' . substr($tienda['UUIDSucursal'], 0, 8) . '...'
            ];
        }
        
    } else {
        $error_db = "No se pudo conectar a la base de datos.";
    }
} catch (PDOException $e) {
    $error_db = "Error de base de datos: " . $e->getMessage();
}

// Obtener inventario actual
$inventarioObj = new Inventario();
$inventario = $inventarioObj->obtenerProductos();
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="Css/produccion.css">
<link rel="stylesheet" href="Css/envios.css">

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <i class="icon-truck" style="color: #007bff;"></i>
                Envíos a Tiendas
            </h1>
        </div>
    </div>

    <?php if ($error_db): ?>
        <div class="alert alert-danger">
            <i class="icon-cross2" style="margin-right: 0.5rem;"></i>
            <strong>Error de conexión:</strong><br>
            <?php echo htmlspecialchars($error_db); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="icon-shop"></i>
                        Seleccionar Tienda y Productos
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($tiendas) && !$error_db): ?>
                        <div class="alert alert-info">
                            <i class="icon-info" style="margin-right: 0.5rem;"></i>
                            <strong>Sin tiendas disponibles</strong><br>
                            No se encontraron tiendas registradas en el sistema.
                        </div>
                    <?php elseif (!empty($tiendas)): ?>
                        <!-- Selector de tienda -->
                        <div class="form-group">
                            <label for="selectTienda">
                                <i class="icon-shop"></i>
                                Seleccionar Tienda:
                            </label>
                            <select class="form-control" id="selectTienda">
                                <option value="">-- Seleccione una tienda --</option>
                                <?php foreach ($tiendas as $tienda): ?>
                                    <option value="<?php echo htmlspecialchars($tienda['UUIDSucursal']); ?>">
                                        <?php echo htmlspecialchars($tienda['NombreTienda']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Área de productos -->
                        <div id="productosContainer" style="display: none;">
                            <form id="formEnvios" action="controllers/procesar_envio.php" method="post">
                                <input type="hidden" id="tiendaSeleccionada" name="tienda_uuid">
                                
                                <div class="products-header">
                                    <h4>
                                        <i class="icon-package"></i>
                                        Productos Disponibles para Envío
                                        <span class="badge badge-info" id="contadorProductos">0</span>
                                    </h4>
                                    <div class="search-box">
                                        <input type="text" id="buscarProducto" placeholder="Buscar producto..." class="form-control">
                                        <i class="icon-search"></i>
                                    </div>
                                </div>

                                <div id="productosGrid" class="products-grid">
                                    <!-- Los productos se cargarán aquí dinámicamente -->
                                </div>

                                <div class="form-footer" id="formFooter" style="display: none;">
                                    <div class="envio-summary">
                                        <i class="icon-truck" style="color: #007bff; font-size: 1.2rem;"></i>
                                        <span>Total a enviar:</span>
                                        <span class="total-count" id="conteoEnvio">0</span>
                                        <span>unidades</span>
                                    </div>
                                    <button type="submit" class="save-btn" id="btnGuardarEnvio">
                                        <i class="icon-truck"></i>
                                        <span>Procesar Envío</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para registrar cantidad de envío -->
<div class="modal fade" id="envioModal" tabindex="-1" role="dialog" aria-labelledby="envioModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="envioModalLabel">
                    <i class="icon-truck"></i>
                    Registrar Envío
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="product-details">
                    <h6>Información del Producto</h6>
                    <p><strong>Descripción:</strong> <span id="modalProductoDescripcion"></span></p>
                    <p><strong>Código:</strong> <span id="modalProductoCodigo"></span></p>
                    <p><strong>Disponible en Inventario:</strong> <span id="modalInventarioDisponible"></span> unidades</p>
                    <p><strong>Existencia Actual en Tienda:</strong> <span id="modalExistenciaTienda"></span> unidades</p>
                </div>
                
                <div class="form-group">
                    <label for="modalCantidadEnvio">
                        <i class="icon-truck" style="margin-right: 0.25rem;"></i>
                        Cantidad a Enviar:
                    </label>
                    <input type="number" 
                           class="form-control" 
                           id="modalCantidadEnvio" 
                           min="0" 
                           value="0" 
                           pattern="[0-9]*" 
                           inputmode="numeric"
                           placeholder="Ingresa la cantidad a enviar">
                    <small class="form-text text-muted">
                        Máximo disponible: <span id="maxDisponible">0</span> unidades
                    </small>
                    <input type="hidden" id="modalCodigoProdHidden">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarEnvioModal">
                    <i class="icon-save"></i>
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 - AGREGAR ESTA LÍNEA -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- JavaScript personalizado -->
<script src="Js/envios.js"></script>