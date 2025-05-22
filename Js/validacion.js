$(function(){
  $('.revisar-btn').click(function(){
    var file = $(this).data('file');
    $('#detalleNombre').text(file);
    $('#csvDetalleContent').html('<p>Cargando...</p>');
    $.get('detalle_validacion.php', { file: file }, function(html){
      $('#csvDetalleContent').html(html);
      $('#csvDetalleModal').modal('show');
    });
  });

  // TODO: agregar manejadores para validar, modificar y rechazar
});