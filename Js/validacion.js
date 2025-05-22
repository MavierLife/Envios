$(function(){
  
  // Función para mostrar loading
  function showLoading(element) {
    const originalContent = element.html();
    element.data('original-content', originalContent);
    element.html('<div class="spinner" style="width: 16px; height: 16px; border-width: 2px;"></div>');
    element.prop('disabled', true);
  }
  
  // Función para ocultar loading
  function hideLoading(element) {
    const originalContent = element.data('original-content');
    element.html(originalContent);
    element.prop('disabled', false);
  }
  
  // Función para mostrar notificaciones toast
  function showToast(message, type = 'info') {
    // Crear el toast si no existe
    let toastContainer = $('#toast-container');
    if (toastContainer.length === 0) {
      $('body').append('<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
      toastContainer = $('#toast-container');
    }
    
    const toastId = 'toast-' + Date.now();
    const iconMap = {
      success: 'icon-check',
      error: 'icon-cross2',
      warning: 'icon-warning',
      info: 'icon-info'
    };
    
    const colorMap = {
      success: '#28a745',
      error: '#dc3545',
      warning: '#ffc107',
      info: '#17a2b8'
    };
    
    const toast = $(`
      <div id="${toastId}" class="toast-message" style="
        background: white;
        border-left: 4px solid ${colorMap[type]};
        padding: 1rem;
        margin-bottom: 0.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(100%);
        transition: all 0.3s ease;
        max-width: 300px;
        word-wrap: break-word;
      ">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <i class="${iconMap[type]}" style="color: ${colorMap[type]}; font-size: 1.1rem;"></i>
          <span style="color: #2c3e50; font-size: 0.9rem;">${message}</span>
        </div>
      </div>
    `);
    
    toastContainer.append(toast);
    
    // Animar entrada
    setTimeout(() => {
      toast.css('transform', 'translateX(0)');
    }, 100);
    
    // Auto-ocultar después de 4 segundos
    setTimeout(() => {
      toast.css('transform', 'translateX(100%)');
      setTimeout(() => {
        toast.remove();
      }, 300);
    }, 4000);
  }
  
  // Función para confirmar acciones
  function showConfirm(title, message, onConfirm, confirmText = 'Confirmar', cancelText = 'Cancelar') {
    // Crear modal de confirmación si no existe
    let confirmModal = $('#confirmModal');
    if (confirmModal.length === 0) {
      $('body').append(`
        <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-sm">
            <div class="modal-content" style="border-radius: 12px; border: none;">
              <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title" id="confirmTitle"></h5>
              </div>
              <div class="modal-body" style="padding: 1.5rem;">
                <p id="confirmMessage" style="margin-bottom: 0; color: #2c3e50;"></p>
              </div>
              <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 1rem;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="confirmCancel" style="margin-right: 0.5rem;"></button>
                <button type="button" class="btn btn-primary" id="confirmOk"></button>
              </div>
            </div>
          </div>
        </div>
      `);
      confirmModal = $('#confirmModal');
    }
    
    $('#confirmTitle').text(title);
    $('#confirmMessage').text(message);
    $('#confirmCancel').text(cancelText);
    $('#confirmOk').text(confirmText);
    
    // Limpiar eventos anteriores
    $('#confirmOk').off('click').on('click', function() {
      confirmModal.modal('hide');
      onConfirm();
    });
    
    confirmModal.modal('show');
  }
  
  // Manejador para revisar producción
  $('.revisar-btn').click(function(){
    const $btn = $(this);
    const file = $btn.data('file');
    
    $('#detalleNombre').text(file);
    $('#csvDetalleContent').html(`
      <div class="loading">
        <div class="spinner"></div>
        <p style="margin-top: 1rem; text-align: center; color: #6c757d;">Cargando datos...</p>
      </div>
    `);
    
    $('#csvDetalleModal').modal('show');
    
    // Cargar el contenido
    $.get('detalle_validacion.php', { file: file })
      .done(function(html){
        $('#csvDetalleContent').html(html);
      })
      .fail(function(){
        $('#csvDetalleContent').html(`
          <div class="alert alert-danger">
            <i class="icon-cross2" style="margin-right: 0.5rem;"></i>
            <strong>Error:</strong> No se pudo cargar el detalle de la producción.
          </div>
        `);
      });
  });
  
  // Manejador para aceptar producción
  $('.validar-btn').click(function(){
    const $btn = $(this);
    const file = $btn.data('file');
    
    showConfirm(
      'Confirmar Aceptación',
      `¿Está seguro de que desea aceptar la producción "${file}"?`,
      function() {
        showLoading($btn);
        
        // Simular llamada AJAX para aceptar
        setTimeout(() => {
          // Aquí iría la llamada real a tu endpoint
          $.post('procesar_validacion.php', { 
            action: 'aceptar', 
            file: file 
          })
          .done(function(response) {
            hideLoading($btn);
            showToast('Producción aceptada correctamente', 'success');
            
            // Remover el elemento con animación
            const $item = $btn.closest('.production-item');
            $item.css('transform', 'scale(0.95)');
            $item.fadeOut(300, function() {
              $item.remove();
              
              // Si no quedan más elementos, mostrar mensaje
              if ($('.production-item').length === 0) {
                $('.productions-list').html(`
                  <div class="alert alert-info">
                    <i class="icon-info" style="margin-right: 0.5rem;"></i>
                    <strong>Sin producciones pendientes</strong><br>
                    Todas las producciones han sido procesadas.
                  </div>
                `);
              }
            });
          })
          .fail(function() {
            hideLoading($btn);
            showToast('Error al procesar la aceptación', 'error');
          });
        }, 1000); // Simular delay de red
      },
      'Aceptar',
      'Cancelar'
    );
  });
  
  // Manejador para rechazar producción
  $('.rechazar-btn').click(function(){
    const $btn = $(this);
    const file = $btn.data('file');
    
    showConfirm(
      'Confirmar Rechazo',
      `¿Está seguro de que desea rechazar la producción "${file}"? Esta acción no se puede deshacer.`,
      function() {
        showLoading($btn);
        
        // Simular llamada AJAX para rechazar
        setTimeout(() => {
          // Aquí iría la llamada real a tu endpoint
          $.post('procesar_validacion.php', { 
            action: 'rechazar', 
            file: file 
          })
          .done(function(response) {
            hideLoading($btn);
            showToast('Producción rechazada', 'warning');
            
            // Remover el elemento con animación
            const $item = $btn.closest('.production-item');
            $item.css('transform', 'scale(0.95)');
            $item.fadeOut(300, function() {
              $item.remove();
              
              // Si no quedan más elementos, mostrar mensaje
              if ($('.production-item').length === 0) {
                $('.productions-list').html(`
                  <div class="alert alert-info">
                    <i class="icon-info" style="margin-right: 0.5rem;"></i>
                    <strong>Sin producciones pendientes</strong><br>
                    Todas las producciones han sido procesadas.
                  </div>
                `);
              }
            });
          })
          .fail(function() {
            hideLoading($btn);
            showToast('Error al procesar el rechazo', 'error');
          });
        }, 1000); // Simular delay de red
      },
      'Rechazar',
      'Cancelar'
    );
  });
  
  // Efecto hover para los items de producción
  $('.production-item').hover(
    function() {
      $(this).css('transform', 'translateY(-2px)');
    },
    function() {
      $(this).css('transform', 'translateY(0)');
    }
  );
  
  // Cerrar modal con Escape
  $(document).keydown(function(e) {
    if (e.keyCode === 27) { // Escape key
      $('.modal').modal('hide');
    }
  });
  
  // Mejorar accesibilidad del modal
  $('#csvDetalleModal').on('shown.bs.modal', function () {
    $(this).find('.close').focus();
  });
  
});