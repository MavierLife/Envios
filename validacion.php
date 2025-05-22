<?php
// ...existing code...

// Obtener todos los .csv de ProdPendientes
$csvFiles = glob(__DIR__ . '/ProdPendientes/*.csv');
?>
<link rel="stylesheet" href="Css/validacion.css">

<div class="table-responsive">
  <table class="table table-bordered table-hover table-striped">
    <thead>
      <tr>
        <th>Archivo CSV</th>
        <th style="width:240px;text-align:center;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($csvFiles as $csv): 
        $file = htmlspecialchars(basename($csv));
      ?>
      <tr>
        <td><?php echo $file; ?></td>
        <td class="text-center">
          <button class="btn btn-xs btn-info revisar-btn" data-file="<?php echo $file; ?>">
            <i class="icon-eye"></i> Revisar
          </button>
          <button class="btn btn-xs btn-success validar-btn" data-file="<?php echo $file; ?>">
            <i class="icon-check"></i> Validar
          </button>
          <button class="btn btn-xs btn-warning modificar-btn" data-file="<?php echo $file; ?>">
            <i class="icon-pencil"></i> Modificar
          </button>
          <button class="btn btn-xs btn-danger rechazar-btn" data-file="<?php echo $file; ?>">
            <i class="icon-cross2"></i> Rechazar
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal para mostrar detalle del CSV -->
<div class="modal fade" id="csvDetalleModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle CSV: <span id="detalleNombre"></span></h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="csvDetalleContent" style="overflow:auto;"></div>
    </div>
  </div>
</div>

<script src="Js/validacion.js"></script>
<?php
// ...existing code...