$(document).ready(function() {
    inicializarEventos();
    inicializarEstadosVisuales();
    actualizarTotal();
    mostrarMensajesEstado();
    configurarAccesibilidad();
});

// Variable global para el elemento que lanza el modal
let elementoLanzadorActualParaModal = null;

// Inicializar todos los eventos
function inicializarEventos() {
    inicializarModal();
    manejarClicEnTarjetas();
    manejarBotonesRegistrar();
    manejarGuardadoEnModal();
    manejarEnvioFormulario();
    configurarAtajosTeclado();
}

// Configurar modal
function inicializarModal() {
    $('#produccionModal').on('show.bs.modal', function (event) {
        const disparador = event.relatedTarget ? $(event.relatedTarget) : elementoLanzadorActualParaModal;
        
        if (!disparador) {
            console.error('No se pudo determinar el origen del modal.');
            return;
        }

        const tarjeta = disparador.closest('.product-card');
        if (!tarjeta.length) {
            console.error('Tarjeta del producto no encontrada.');
            return;
        }

        const codigo = tarjeta.data('codigoprod');
        const descripcion = tarjeta.data('descripcion');
        const unidades = tarjeta.data('unidades');
        const actual = $('#input-produccion-' + codigo).val() || 0;

        // Llenar datos del modal
        $('#modalProductoDescripcion').text(descripcion);
        $('#modalProductoCodigo').text(codigo);
        $('#modalProductoUnidades').text(unidades);
        $('#modalCodigoProdHidden').val(codigo);
        $('#modalCantidadProducida').val(actual);
        
        // Actualizar título del modal
        $('#produccionModalLabel').html(`
            <i class="icon-edit"></i>
            ${descripcion.length > 30 ? descripcion.substring(0, 30) + '...' : descripcion}
        `);
    });

    $('#produccionModal').on('shown.bs.modal', function () {
        $('#modalCantidadProducida').focus().select();
        elementoLanzadorActualParaModal = null;
    });
    
    // Limpiar al cerrar modal
    $('#produccionModal').on('hidden.bs.modal', function () {
        elementoLanzadorActualParaModal = null;
    });
}

// Manejar clic en tarjetas (móvil)
function manejarClicEnTarjetas() {
    $('.product-card').on('click', function(e) {
        // Solo en móvil y si no se clickeó un botón
        if (window.innerWidth < 768 && !$(e.target).closest('button').length) {
            elementoLanzadorActualParaModal = $(this);
            $('#produccionModal').modal('show');
        }
    });
}

// Manejar botones registrar
function manejarBotonesRegistrar() {
    $('.btn-registrar-produccion').on('click', function(e) {
        e.stopPropagation(); // Evitar que se dispare el clic de la tarjeta
        elementoLanzadorActualParaModal = $(this);
    });
}

// Guardar cantidad desde modal
function manejarGuardadoEnModal() {
    $('#btnGuardarProduccionModal').on('click', function() {
        const codigo = $('#modalCodigoProdHidden').val();
        const cantidadInput = $('#modalCantidadProducida');
        const cantidad = cantidadInput.val().trim();

        // Validación
        if (cantidad === '' || isNaN(parseFloat(cantidad)) || parseFloat(cantidad) < 0) {
            mostrarError('Ingresa una cantidad válida (mayor o igual a 0).');
            cantidadInput.focus().select();
            return;
        }

        const cantidadNum = parseFloat(cantidad);
        
        // Mostrar loading en el botón
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.html('<div class="spinner" style="width: 16px; height: 16px; border-width: 2px; margin-right: 0.5rem;"></div>Guardando...');
        $btn.prop('disabled', true);
        
        // Simular un pequeño delay para mejor UX
        setTimeout(() => {
            // Actualizar valores
            actualizarProduccion(codigo, cantidadNum);
            
            // Restaurar botón
            $btn.html(originalText);
            $btn.prop('disabled', false);
            
            // Cerrar modal
            $('#produccionModal').modal('hide');
            
            // Mostrar feedback visual
            mostrarExito('Producción actualizada correctamente');
        }, 300);
    });
}

// Actualizar producción específica
function actualizarProduccion(codigo, cantidad) {
    const inputProduccion = $('#input-produccion-' + codigo);
    const displayProduccion = $('.display-produccion-' + codigo);
    const tarjeta = $(`.product-card[data-codigoprod="${codigo}"]`);
    const badge = $('#badge-' + codigo);
    
    // Actualizar valores
    inputProduccion.val(cantidad);
    displayProduccion.text(cantidad);
    
    // Actualizar badge y estados visuales
    if (cantidad > 0) {
        badge.removeClass('zero');
        tarjeta.addClass('has-production');
    } else {
        badge.addClass('zero');
        tarjeta.removeClass('has-production');
    }
    
    // Agregar clase de animación para feedback visual
    tarjeta.addClass('success-flash');
    setTimeout(() => {
        tarjeta.removeClass('success-flash');
    }, 500);
    
    // Actualizar total
    actualizarTotal();
}

// Manejar envío del formulario
function manejarEnvioFormulario() {
    $('#formProduccion').on('submit', function(e) {
        e.preventDefault();
        
        const total = parseInt($('#conteoProduccion').text(), 10) || 0;
        
        if (total <= 0) {
            mostrarError('Debes registrar al menos una producción antes de guardar.');
            return;
        }
        
        // Mostrar diálogo de confirmación
        Swal.fire({
            icon: 'question',
            title: 'Confirmar registro',
            text: `¿Desea registrar la producción total de ${total} unidades?`,
            showCancelButton: true,
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Registrando...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Enviar formulario
                this.submit();
            }
        });
    });
}

// Configurar atajos de teclado
function configurarAtajosTeclado() {
    $(document).on('keydown', function(e) {
        // Escape para cerrar modal
        if (e.key === 'Escape' && $('#produccionModal').hasClass('show')) {
            $('#produccionModal').modal('hide');
        }
        
        // Enter en el modal para guardar
        if (e.key === 'Enter' && $('#produccionModal').hasClass('show')) {
            e.preventDefault();
            $('#btnGuardarProduccionModal').click();
        }
        
        // Ctrl+S para guardar formulario
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('#formProduccion').submit();
        }
    });
}

// Configurar accesibilidad
function configurarAccesibilidad() {
    // ARIA labels dinámicos
    $('.btn-registrar-produccion').attr('aria-label', function() {
        const tarjeta = $(this).closest('.product-card');
        const descripcion = tarjeta.data('descripcion');
        return `Registrar producción para ${descripcion}`;
    });
    
    // Anunciar cambios al lector de pantalla
    $('#conteoProduccion').attr('aria-live', 'polite');
    
    // Mejorar accesibilidad del modal
    $('#produccionModal').attr('aria-describedby', 'modalProductoDescripcion');
}

// Recalcular total de producción
function actualizarTotal() {
    const inputs = $('.produccion-hidden-input');
    let total = 0;
    
    inputs.each(function() {
        const valor = parseInt($(this).val(), 10) || 0;
        total += valor;
    });
    
    const $contador = $('#conteoProduccion');
    const valorAnterior = parseInt($contador.text(), 10) || 0;
    
    // Actualizar valor
    $contador.text(total);
    
    // Animación si cambió el valor
    if (total !== valorAnterior) {
        $contador.addClass('success-flash');
        setTimeout(() => {
            $contador.removeClass('success-flash');
        }, 500);
    }
    
    // Actualizar estado del botón de envío
    const $btnEnviar = $('#btnGuardarTodo');
    if (total > 0) {
        $btnEnviar.prop('disabled', false).removeClass('btn-secondary').addClass('save-btn');
    } else {
        $btnEnviar.prop('disabled', true).removeClass('save-btn').addClass('btn-secondary');
    }
}

// Mostrar mensajes de estado
function mostrarMensajesEstado() {
    const params = new URLSearchParams(window.location.search);
    
    if (params.get('guardado') === 'ok') {
        mostrarExito('El registro se creó correctamente.');
        // Limpiar URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    if (params.get('error') === '1') {
        mostrarError('Ocurrió un error al procesar la solicitud.');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

// Funciones de utilidad para mensajes
function mostrarExito(mensaje) {
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: mensaje,
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

function mostrarError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: '¡Error!',
        text: mensaje,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#dc3545'
    });
}

function mostrarAdvertencia(mensaje) {
    Swal.fire({
        icon: 'warning',
        title: 'Atención',
        text: mensaje,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#ffc107'
    });
}

// Función para manejar errores de red o conexión
function manejarErrorConexion() {
    mostrarError('Error de conexión. Por favor, verifica tu conexión a internet e intenta nuevamente.');
}

// Debounce para optimizar actualizaciones
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Versión optimizada de actualizar total con debounce
const actualizarTotalOptimizado = debounce(actualizarTotal, 300);

// Event listeners adicionales para mejor UX
$(window).on('resize', debounce(function() {
    // Reconfigurar eventos móviles si cambia el tamaño de pantalla
    if (window.innerWidth >= 768) {
        $('.product-card').off('click.mobile');
    }
}, 250));

// Validación en tiempo real en el modal
$('#modalCantidadProducida').on('input', function() {
    const valor = $(this).val();
    const $btn = $('#btnGuardarProduccionModal');
    
    if (valor === '' || isNaN(parseFloat(valor)) || parseFloat(valor) < 0) {
        $btn.prop('disabled', true);
        $(this).addClass('is-invalid');
    } else {
        $btn.prop('disabled', false);
        $(this).removeClass('is-invalid');
    }
});

// Limpiar formulario (si es necesario)
function limpiarFormulario() {
    $('.produccion-hidden-input').val(0);
    $('.display-produccion').text(0);
    $('.production-badge').addClass('zero');
    $('.product-card').removeClass('has-production');
    actualizarTotal();
    mostrarExito('Formulario limpiado correctamente');
}

// Función adicional para inicializar estados visuales al cargar
function inicializarEstadosVisuales() {
    $('.produccion-hidden-input').each(function() {
        const codigo = $(this).attr('id').replace('input-produccion-', '');
        const cantidad = parseInt($(this).val()) || 0;
        
        if (cantidad > 0) {
            actualizarProduccion(codigo, cantidad);
        }
    });
}