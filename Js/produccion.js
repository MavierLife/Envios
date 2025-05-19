$(document).ready(function() {
    inicializarModal();
    manejarClicDescripcionEnMovil();
    manejarGuardadoEnModal();
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


// recalcula el total de producción registrada
function actualizarTotal() {
    const inputs = document.querySelectorAll('.produccion-hidden-input');
    let total = 0;
    inputs.forEach(i => {
        total += parseInt(i.value, 10) || 0;
    });
    document.getElementById('conteoProduccion').textContent = total;
}

// al iniciar, muestra el total (0 o lo que haya)
document.addEventListener('DOMContentLoaded', actualizarTotal);

// en tu handler de guardar en modal (ejemplo):
document.getElementById('btnGuardarProduccionModal').addEventListener('click', function() {
    const codigo = document.getElementById('modalCodigoProdHidden').value;
    const cantidad = parseInt(document.getElementById('modalCantidadProducida').value, 10) || 0;

    // actualiza el span y el input hidden
    document.querySelector('.display-produccion-' + codigo).textContent = cantidad;
    document.getElementById('input-produccion-' + codigo).value = cantidad;

    // recalcula total
    actualizarTotal();

    // cierra modal
    $('#produccionModal').modal('hide');
});
