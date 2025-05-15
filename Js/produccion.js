$(document).ready(function() {
    inicializarModal();
    manejarClicDescripcionEnMovil();
    manejarGuardadoEnModal();
    manejarEnvioFormulario();
});


// ESTO INICIALIZA EL MODAL: llena los datos cada vez que se abre
function inicializarModal() {
    $('#produccionModal').on('show.bs.modal', function (event) {
        var disparador;
        if (event.relatedTarget) {
            disparador = $(event.relatedTarget);
        } else if (elementoLanzadorActualParaModal) {
            disparador = elementoLanzadorActualParaModal;
        } else {
            console.error('No se pudo determinar el origen del modal.');
            return;
        }

        var fila = disparador.closest('tr');
        if (!fila.length) {
            console.error('Fila del producto no encontrada para el modal.');
            return;
        }

        var codigo  = fila.data('codigoprod');
        var desc    = fila.data('descripcion');
        var unidades= fila.data('unidades');
        var actual  = $('#input-produccion-' + codigo).val() || 0;

        var modal = $(this);
        modal.find('#modalProductoDescripcion').text(desc);
        modal.find('#modalProductoCodigo').text(codigo);
        modal.find('#modalProductoUnidades').text(unidades);
        modal.find('#modalCodigoProdHidden').val(codigo);
        modal.find('#modalCantidadProducida').val(actual);
    });

    $('#produccionModal').on('shown.bs.modal', function () {
        $('#modalCantidadProducida').focus().select();
        elementoLanzadorActualParaModal = null;
    });
}


// ESTO MANEJA EL CLIC EN LA DESCRIPCIÓN PARA MÓVIL: abre el modal vía JS
function manejarClicDescripcionEnMovil() {
    $('table tbody').on('click', '.descripcion-producto', function() {
        if (window.innerWidth < 768) {
            elementoLanzadorActualParaModal = $(this);
            $('#produccionModal').modal('show');
        }
    });
}


// ESTO GUARDA LA CANTIDAD DESDE EL MODAL: actualiza el input y la etiqueta
function manejarGuardadoEnModal() {
    $('#btnGuardarProduccionModal').on('click', function() {
        var codigo = $('#modalCodigoProdHidden').val();
        var cantidad = $('#modalCantidadProducida').val();

        if (cantidad === '' || isNaN(parseFloat(cantidad)) || parseFloat(cantidad) < 0) {
            alert('Ingresa una cantidad válida (>= 0).');
            $('#modalCantidadProducida').focus().select();
            return;
        }

        cantidad = parseFloat(cantidad);
        $('#input-produccion-' + codigo).val(cantidad);
        $('.display-produccion-' + codigo).text(cantidad);
        $('#produccionModal').modal('hide');
    });
}


// ESTO MANEJA EL ENVÍO DEL FORMULARIO: valida y prepara los datos para enviar
function manejarEnvioFormulario() {
    $('#formProduccion').on('submit', function(e) {
        e.preventDefault();

        var datosArray = $(this).serializeArray();
        var produccionData = {};

        datosArray.forEach(function(item) {
            var match = item.name.match(/produccion\[(.*?)\]/);
            if (match && match[1]) {
                var val = parseFloat(item.value);
                if (!isNaN(val) && val >= 0) {
                    produccionData[match[1]] = val;
                }
            }
        });

        if (Object.keys(produccionData).length === 0) {
            alert('No hay datos de producción para guardar.');
            return;
        }

        console.log('Datos de producción a enviar:', produccionData);
        alert('Función de guardado aún no implementada en backend. Revisa consola.');
        // Aquí iría la llamada AJAX al servidor:
        /*
        $.post('procesar_produccion.php', { produccion: produccionData }, function(respuesta) {
            // manejar respuesta...
        }, 'json');
        */
    });
}
