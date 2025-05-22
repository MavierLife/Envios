<?php
session_start();
require_once 'Config/Database.php'; // Para la conexión a la BD

// --- INICIO DE LA SECCIÓN PARA EVITAR CARGA DIRECTA SIN ESTILOS ---
// Si no es una solicitud AJAX Y se accede directamente a este archivo
if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $currentPage = basename(__FILE__);
    $pageNameWithoutExtension = pathinfo($currentPage, PATHINFO_FILENAME); // obtiene “produccion”
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

// Opcional: Verificación de rol específico para esta página
/*
if (!isset($_SESSION['user_acceso']) || $_SESSION['user_acceso'] !== 'rol_para_produccion') {
    http_response_code(403); // Prohibido
    echo json_encode(['error' => 'Access denied', 'message' => 'No tienes permiso para ver la sección de Producción.']);
    exit;
}
*/

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

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Registro de Producción</h1>
        </div>
    </div>

    <?php if ($error_db): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="icon-stack"></i> Productos "Quality"</h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($productos) && !$error_db): ?>
                        <div class="alert alert-info">No se encontraron productos que contengan la palabra "Quality" o no hay productos registrados.</div>
                    <?php elseif (!empty($productos)): ?>
                        <form id="formProduccion" action="guardar_produccion.php" method="post">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 120px;">Producción</th>
                                            <th>Descripción</th>
                                            <th class="text-right d-none d-md-table-cell">Unidades (Paquete)</th>
                                            <th>Código</th>
                                            <th class="text-center d-none d-md-table-cell" style="width: 100px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos as $producto): ?>
                                            <tr id="row-<?php echo htmlspecialchars($producto['CodigoPROD']); ?>"
                                                class="fila-producto-clickable"
                                                data-codigoprod="<?php echo htmlspecialchars($producto['CodigoPROD']); ?>"
                                                data-descripcion="<?php echo htmlspecialchars($producto['Descripcion']); ?>"
                                                data-unidades="<?php echo htmlspecialchars($producto['Unidades']); ?>">

                                                <td class="text-center">
                                                    <span class="display-produccion-<?php echo htmlspecialchars($producto['CodigoPROD']); ?>">0</span>
                                                    <input
                                                        type="hidden"
                                                        class="produccion-hidden-input"
                                                        name="produccion[<?php echo htmlspecialchars($producto['CodigoPROD']); ?>]"
                                                        id="input-produccion-<?php echo htmlspecialchars($producto['CodigoPROD']); ?>"
                                                        value="0">
                                                </td>
                                                <td class="descripcion-producto">
                                                    <?php echo htmlspecialchars($producto['Descripcion']); ?>
                                                </td>
                                                <td class="text-right d-none d-md-table-cell"><?php echo htmlspecialchars($producto['Unidades']); ?></td>
                                                <td><?php echo htmlspecialchars($producto['CodigoPROD']); ?></td>
                                                <td class="text-center d-none d-md-table-cell">
                                                    <button type="button"
                                                            class="btn btn-xs btn-info btn-registrar-produccion-desktop"
                                                            data-toggle="modal"
                                                            data-target="#produccionModal">
                                                        <i class="icon-plus3"></i> Registrar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>Total registradas: <strong><span id="conteoProduccion">0</span></strong></div>
                                <button type="submit" class="btn btn-primary">Guardar Toda la Producción</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="produccionModal" tabindex="-1" role="dialog" aria-labelledby="produccionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="produccionModalLabel">Registrar Producción</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><strong>Producto:</strong> <span id="modalProductoDescripcion"></span></p>
        <p><small><strong>Código:</strong> <span id="modalProductoCodigo"></span></small></p>
        <p><small><strong>Unidades Paquete:</strong> <span id="modalProductoUnidades"></span></small></p>
        <div class="form-group">
          <label for="modalCantidadProducida">Cantidad Producida Hoy:</label>
          <input type="number" class="form-control" id="modalCantidadProducida" min="0" value="0" pattern="[0-9]*" inputmode="numeric">
          <input type="hidden" id="modalCodigoProdHidden">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarProduccionModal">Guardar Cantidad</button>
      </div>
    </div>
  </div>
</div>


<style>
    .table th.text-right, .table td.text-right { text-align: right; }
    .table th.text-center, .table td.text-center { text-align: center; }

    .descripcion-producto {
        cursor: pointer;
    }
    /*
    .descripcion-producto:hover {
        background-color: #f8f9fa;
    }
    */

    /* Si usas Bootstrap 3 y necesitas control más fino para ocultar/mostrar
       que el que proveen las clases por defecto de BS3 como hidden-xs, etc. */
    /*
    @media (max-width: 767px) { // xs
        .columna-unidades-bs3, .columna-accion-bs3 { display: none !important; }
    }
    @media (min-width: 768px) and (max-width: 991px) { // sm
        .columna-unidades-bs3, .columna-accion-bs3 { display: none !important; }
    }
    @media (min-width: 992px) { // md y superior
        .columna-unidades-bs3, .columna-accion-bs3 { display: table-cell !important; }
    }
    */
    /* Las clases d-none y d-md-table-cell de Bootstrap 4/5 simplifican esto.
       Asegúrate que tu index.php carga una versión de Bootstrap que las soporte (BS4+).
       Si es BS3, tendrías que quitar d-none d-md-table-cell del HTML y usar
       clases como 'hidden-xs hidden-sm' en las th/td que quieres ocultar en móvil.
    */

    /* SweetAlert2 */
</style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Tu lógica de producción -->
    <script src="js/produccion.js?v=<?php echo time(); ?>"></script>

