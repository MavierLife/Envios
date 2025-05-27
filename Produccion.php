<?php
session_start();
require_once 'Config/Database.php'; // Para la conexión a la BD

// --- INICIO DE LA SECCIÓN PARA EVITAR CARGA DIRECTA SIN ESTILOS ---
// Si no es una solicitud AJAX Y se accede directamente a este archivo
if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $currentPage = basename(__FILE__);
    $pageNameWithoutExtension = pathinfo($currentPage, PATHINFO_FILENAME); // obtiene "produccion"
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

$productos = [];
$error_db = '';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db) {
        // Consulta para obtener los productos que contienen "Quality" en la descripción
        $query = "SELECT CodigoPROD, Descripcion, Unidades
                  FROM tblcatalogodeproductos
                  WHERE Descripcion LIKE :searchTerm
                  ORDER BY CodigoPROD ASC";

        $stmt = $db->prepare($query);
        $searchTerm = "%Quality%";
        $stmt->bindParam(':searchTerm', $searchTerm);
        $stmt->execute();

        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error_db = "No se pudo conectar a la base de datos.";
    }
} catch (PDOException $e) {
    $error_db = "Error de base de datos: " . $e->getMessage();
}

?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="Css/produccion.css">

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <i class="icon-stack" style="color: #28a745;"></i>
                Registro de Producción
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
                        <i class="icon-package"></i>
                        Productos "Quality"
                        <?php if (!empty($productos)): ?>
                            <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem; margin-left: 0.5rem;">
                                <?php echo count($productos); ?> productos
                            </span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($productos) && !$error_db): ?>
                        <div class="alert alert-info">
                            <i class="icon-info" style="margin-right: 0.5rem;"></i>
                            <strong>Sin productos disponibles</strong><br>
                            No se encontraron productos que contengan la palabra "Quality" o no hay productos registrados.
                        </div>
                    <?php elseif (!empty($productos)): ?>
                        <form id="formProduccion" action="guardar_produccion.php" method="post">
                            <div class="products-grid">
                                <?php foreach ($productos as $producto): 
                                    $codigo = htmlspecialchars($producto['CodigoPROD']);
                                    $descripcion = htmlspecialchars($producto['Descripcion']);
                                    $unidades = htmlspecialchars($producto['Unidades']);
                                ?>
                                <div class="product-card" 
                                     id="card-<?php echo $codigo; ?>"
                                     data-codigoprod="<?php echo $codigo; ?>"
                                     data-descripcion="<?php echo $descripcion; ?>"
                                     data-unidades="<?php echo $unidades; ?>">
                                    
                                    <div class="production-badge zero" id="badge-<?php echo $codigo; ?>">
                                        <span class="display-produccion-<?php echo $codigo; ?>">0</span>
                                    </div>
                                    
                                    <div class="product-header">
                                        <div class="product-icon">
                                            <i class="icon-package"></i>
                                        </div>
                                        <div class="product-info">
                                            <div class="product-name"><?php echo $descripcion; ?></div>
                                            <div class="product-meta">
                                                <span>
                                                    <i class="icon-barcode"></i>
                                                    Código: <?php echo $codigo; ?>
                                                </span>
                                                <span>
                                                    <i class="icon-package"></i>
                                                    Unidades/Paquete: <?php echo $unidades; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="production-controls">
                                        <div class="production-display">
                                            <span>Producido:</span>
                                            <span class="production-value display-produccion-<?php echo $codigo; ?>">0</span>
                                        </div>
                                        <button type="button" 
                                                class="register-btn btn-registrar-produccion"
                                                data-toggle="modal" 
                                                data-target="#produccionModal">
                                            <i class="icon-plus3"></i>
                                            <span>Registrar</span>
                                        </button>
                                    </div>
                                    
                                    <input type="hidden" 
                                           class="produccion-hidden-input"
                                           name="produccion[<?php echo $codigo; ?>]"
                                           id="input-produccion-<?php echo $codigo; ?>"
                                           value="0">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="form-footer">
                                <div class="production-summary">
                                    <i class="icon-chart" style="color: #28a745; font-size: 1.2rem;"></i>
                                    <span>Total registrado:</span>
                                    <span class="total-count" id="conteoProduccion">0</span>
                                    <span>unidades</span>
                                </div>
                                <button type="submit" class="save-btn" id="btnGuardarTodo">
                                    <i class="icon-save"></i>
                                    <span>Guardar Producción</span>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para registrar producción -->
<div class="modal fade" id="produccionModal" tabindex="-1" role="dialog" aria-labelledby="produccionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="produccionModalLabel">
                    <i class="icon-edit"></i>
                    Registrar Producción
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
                    <p><strong>Unidades por Paquete:</strong> <span id="modalProductoUnidades"></span></p>
                </div>
                
                <div class="form-group">
                    <label for="modalCantidadProducida">
                        <i class="icon-plus3" style="margin-right: 0.25rem;"></i>
                        Cantidad Producida Hoy:
                    </label>
                    <input type="number" 
                           class="form-control" 
                           id="modalCantidadProducida" 
                           min="0" 
                           value="0" 
                           pattern="[0-9]*" 
                           inputmode="numeric"
                           placeholder="Ingresa la cantidad producida">
                    <input type="hidden" id="modalCodigoProdHidden">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" 
                        class="btn btn-secondary" 
                        data-dismiss="modal">             <!-- corregido -->
                    <i class="icon-cross2"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarProduccionModal">
                    <i class="icon-check"></i>
                    Guardar Cantidad
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- JavaScript personalizado -->
<script src="js/produccion.js?v=<?php echo time(); ?>"></script>