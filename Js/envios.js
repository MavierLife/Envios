$(document).ready(function() {
    inicializarEnvios();
});

let elementoLanzadorActualParaModal = null;
let productosDisponibles = [];
let inventarioActual = [];

function inicializarEnvios() {
    configurarSelectorTienda();
    configurarBusqueda();
    configurarModalEnvio();
    configurarFormularioEnvio();
}

// Configurar selector de tienda
function configurarSelectorTienda() {
    $('#selectTienda').on('change', function() {
        const tiendaUUID = $(this).val();
        const nombreTienda = $(this).find('option:selected').text();
        
        // LIMPIAR TODO antes de cargar nueva tienda
        limpiarSeleccionTienda();
        
        if (tiendaUUID) {
            $('#tiendaSeleccionada').val(tiendaUUID);
            cargarProductosTienda(tiendaUUID, nombreTienda);
        } else {
            $('#productosContainer').hide();
        }
    });
}

// Nueva función para limpiar completamente la selección de tienda
function limpiarSeleccionTienda() {
    // Ocultar contenedor de productos
    $('#productosContainer').hide();
    
    // Limpiar grid de productos
    $('#productosGrid').empty();
    
    // Limpiar información de tienda anterior
    $('.tienda-info').remove();
    
    // Resetear contadores
    $('#contadorProductos').text('0');
    $('#conteoEnvio').text('0');
    
    // Ocultar footer
    $('#formFooter').hide();
    
    // Limpiar campo oculto
    $('#tiendaSeleccionada').val('');
    
    // Limpiar búsqueda
    $('#buscarProducto').val('');
    
    // Resetear variables globales
    productosDisponibles = [];
    inventarioActual = [];
    elementoLanzadorActualParaModal = null;
    
    // Cerrar modal si está abierto
    $('#envioModal').modal('hide');
}

// Cargar productos de la tienda seleccionada
function cargarProductosTienda(tiendaUUID, nombreTienda) {
    // Asegurar que esté limpio antes de mostrar loading
    $('#productosGrid').empty();
    $('.tienda-info').remove();
    
    mostrarCargando();
    
    $.ajax({
        url: 'controllers/obtener_productos_tienda.php',
        type: 'POST',
        data: { tienda_uuid: tiendaUUID },
        dataType: 'json',
        success: function(response) {
            // Limpiar de nuevo por si acaso
            $('#productosGrid').empty();
            $('.tienda-info').remove();
            
            if (response.status === 'ok') {
                productosDisponibles = response.productos;
                inventarioActual = response.inventario;
                
                mostrarInfoTienda(nombreTienda);
                renderizarProductos(productosDisponibles);
                configurarEventosProductos();
                
                $('#productosContainer').show();
                $('#contadorProductos').text(productosDisponibles.length);
            } else {
                mostrarError(response.message || 'Error al cargar productos');
                $('#productosContainer').hide();
            }
        },
        error: function() {
            mostrarError('Error de conexión al cargar productos');
            $('#productosContainer').hide();
        },
        complete: function() {
            ocultarCargando();
        }
    });
}

// Mostrar información de la tienda
function mostrarInfoTienda(nombreTienda) {
    const infoHtml = `
        <div class="tienda-info">
            <h5><i class="icon-shop"></i> ${nombreTienda}</h5>
            <p class="mb-0">Seleccione los productos y cantidades para enviar a esta tienda.</p>
        </div>
    `;
    
    $('#productosGrid').before(infoHtml);
}

// Renderizar productos
function renderizarProductos(productos) {
    const grid = $('#productosGrid');
    grid.empty();
    
    if (productos.length === 0) {
        grid.html(`
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="icon-info"></i>
                    No hay productos disponibles para esta tienda que estén en el inventario.
                </div>
            </div>
        `);
        return;
    }
    
    productos.forEach(producto => {
        const inventarioProducto = inventarioActual.find(inv => inv.codigo === producto.CodigoPROD);
        const cantidadInventario = inventarioProducto ? inventarioProducto.inventario : 0;
        
        let estadoInventario = 'agotado';
        let textoEstado = 'Agotado';
        
        if (cantidadInventario > 10) {
            estadoInventario = 'disponible';
            textoEstado = 'Disponible';
        } else if (cantidadInventario > 0) {
            estadoInventario = 'limitado';
            textoEstado = 'Stock Limitado';
        }
        
        const cardHtml = `
            <div class="product-card envio-card" 
                 id="envio-card-${producto.CodigoPROD}"
                 data-codigoprod="${producto.CodigoPROD}"
                 data-descripcion="${producto.Descripcion}"
                 data-inventario="${cantidadInventario}"
                 data-existencia="${producto.Existencia}">
                
                <div class="envio-badge zero" id="envio-badge-${producto.CodigoPROD}">
                    <span class="display-envio-${producto.CodigoPROD}">0</span>
                </div>
                
                <div class="product-header">
                    <div class="product-icon">
                        <i class="icon-package"></i>
                    </div>
                    <div class="product-info">
                        <div class="product-name">${producto.Descripcion}</div>
                        <div class="product-meta">
                            <span>
                                <i class="icon-barcode"></i>
                                Código: ${producto.CodigoPROD}
                            </span>
                            <span>
                                <i class="icon-archive"></i>
                                Inventario: ${cantidadInventario}
                            </span>
                            <span>
                                <i class="icon-shop"></i>
                                En tienda: ${producto.Existencia}
                            </span>
                        </div>
                        <div class="inventory-status ${estadoInventario}">
                            ${textoEstado}
                        </div>
                    </div>
                </div>
                
                <div class="envio-controls">
                    <div class="envio-display">
                        <span>A enviar:</span>
                        <span class="envio-value display-envio-${producto.CodigoPROD}">0</span>
                    </div>
                    <button type="button" 
                            class="register-btn btn-registrar-envio"
                            ${cantidadInventario <= 0 ? 'disabled' : ''}
                            data-toggle="modal" 
                            data-target="#envioModal">
                        <i class="icon-truck"></i>
                        <span>Enviar</span>
                    </button>
                </div>
                
                <input type="hidden" 
                       class="envio-hidden-input"
                       name="envios[${producto.CodigoPROD}]"
                       id="input-envio-${producto.CodigoPROD}"
                       value="0">
            </div>
        `;
        
        grid.append(cardHtml);
    });
}

// Configurar eventos de productos
function configurarEventosProductos() {
    // Clic en tarjetas (móvil)
    $('.product-card').on('click', function(e) {
        if (window.innerWidth < 768 && !$(e.target).closest('button').length) {
            const cantidadInventario = parseInt($(this).data('inventario')) || 0;
            if (cantidadInventario > 0) {
                elementoLanzadorActualParaModal = $(this);
                $('#envioModal').modal('show');
            }
        }
    });

    // Botones registrar
    $('.btn-registrar-envio').on('click', function(e) {
        e.stopPropagation();
        elementoLanzadorActualParaModal = $(this);
        $('#envioModal').modal('show');
    });
}

// Configurar modal de envío
function configurarModalEnvio() {
    $('#envioModal').on('show.bs.modal', function (event) {
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
        const inventario = tarjeta.data('inventario');
        const existencia = tarjeta.data('existencia');
        const actual = $('#input-envio-' + codigo).val() || 0;

        // Llenar datos del modal
        $('#modalProductoDescripcion').text(descripcion);
        $('#modalProductoCodigo').text(codigo);
        $('#modalInventarioDisponible').text(inventario);
        $('#modalExistenciaTienda').text(existencia);
        $('#modalCodigoProdHidden').val(codigo);
        $('#modalCantidadEnvio').val(actual);
        $('#maxDisponible').text(inventario);
        
        // Configurar validación
        $('#modalCantidadEnvio').attr('max', inventario);
        
        setTimeout(() => {
            $('#modalCantidadEnvio').focus().select();
        }, 500);
    });

    // Guardar desde modal
    $('#btnGuardarEnvioModal').on('click', function() {
        const codigo = $('#modalCodigoProdHidden').val();
        const cantidadInput = $('#modalCantidadEnvio');
        const cantidad = cantidadInput.val().trim();
        const maxDisponible = parseInt($('#maxDisponible').text()) || 0;

        // Validación
        if (cantidad === '' || isNaN(parseFloat(cantidad)) || parseFloat(cantidad) < 0) {
            mostrarError('Ingresa una cantidad válida (mayor o igual a 0).');
            cantidadInput.focus().select();
            return;
        }

        const cantidadNum = parseFloat(cantidad);
        
        if (cantidadNum > maxDisponible) {
            mostrarError(`La cantidad no puede ser mayor a ${maxDisponible} unidades disponibles.`);
            cantidadInput.focus().select();
            return;
        }

        // Mostrar loading
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.html('<div class="spinner" style="width: 16px; height: 16px; border-width: 2px; margin-right: 0.5rem;"></div>Guardando...');
        $btn.prop('disabled', true);

        setTimeout(() => {
            actualizarEnvio(codigo, cantidadNum);
            
            // Restaurar botón
            $btn.html(originalText);
            $btn.prop('disabled', false);
            
            // Cerrar modal
            $('#envioModal').modal('hide');
            
            // REMOVER ESTA LÍNEA:
            // mostrarExito('Envío actualizado correctamente');
        }, 300);
    });
}

// Actualizar envío específico
function actualizarEnvio(codigo, cantidad) {
    const inputEnvio = $('#input-envio-' + codigo);
    const displayEnvio = $('.display-envio-' + codigo);
    const tarjeta = $(`.product-card[data-codigoprod="${codigo}"]`);
    const badge = $('#envio-badge-' + codigo);
    
    // Actualizar valores
    inputEnvio.val(cantidad);
    displayEnvio.text(cantidad);
    
    // Actualizar badge y estados visuales
    if (cantidad > 0) {
        badge.removeClass('zero');
        tarjeta.addClass('has-envio');
    } else {
        badge.addClass('zero');
        tarjeta.removeClass('has-envio');
    }
    
    // Animación
    tarjeta.addClass('envio-flash');
    setTimeout(() => {
        tarjeta.removeClass('envio-flash');
    }, 500);
    
    // Actualizar total
    actualizarTotalEnvio();
}

// Actualizar total de envío
function actualizarTotalEnvio() {
    const inputs = $('.envio-hidden-input');
    let total = 0;
    
    inputs.each(function() {
        const valor = parseInt($(this).val(), 10) || 0;
        total += valor;
    });
    
    const $contador = $('#conteoEnvio');
    const valorAnterior = parseInt($contador.text(), 10) || 0;
    
    // Actualizar valor
    $contador.text(total);
    
    // Animación si cambió el valor
    if (total !== valorAnterior) {
        $contador.addClass('envio-flash');
        setTimeout(() => {
            $contador.removeClass('envio-flash');
        }, 500);
    }
    
    // Mostrar/ocultar footer y actualizar botón
    const $footer = $('#formFooter');
    const $btnEnviar = $('#btnGuardarEnvio');
    
    if (total > 0) {
        $footer.show();
        $btnEnviar.prop('disabled', false);
    } else {
        $footer.hide();
        $btnEnviar.prop('disabled', true);
    }
}

// Configurar búsqueda
function configurarBusqueda() {
    $('#buscarProducto').on('input', function() {
        const termino = $(this).val().toLowerCase().trim();
        
        $('.product-card').each(function() {
            const codigo = $(this).data('codigoprod').toString().toLowerCase();
            const descripcion = $(this).data('descripcion').toString().toLowerCase();
            
            if (codigo.includes(termino) || descripcion.includes(termino)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Actualizar contador
        const productosVisibles = $('.product-card:visible').length;
        $('#contadorProductos').text(productosVisibles);
    });
}

// Configurar formulario de envío
function configurarFormularioEnvio() {
    $('#formEnvios').on('submit', function(e) {
        e.preventDefault();
        
        const total = parseInt($('#conteoEnvio').text(), 10) || 0;
        const nombreTienda = $('#selectTienda option:selected').text();
        
        if (total <= 0) {
            mostrarError('Debes registrar al menos un envío antes de procesar.');
            return;
        }
        
        // Confirmación
        Swal.fire({
            icon: 'question',
            title: 'Confirmar envío',
            text: `¿Desea procesar el envío de ${total} unidades a ${nombreTienda}?`,
            showCancelButton: true,
            confirmButtonText: 'Sí, procesar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                procesarEnvio();
            }
        });
    });
}

// Procesar envío
function procesarEnvio() {
    // Mostrar loading
    Swal.fire({
        title: 'Procesando envío...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const formData = new FormData($('#formEnvios')[0]);
    
    $.ajax({
        url: $('#formEnvios').attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Respuesta del servidor:', response); // Para debug
            
            try {
                const data = JSON.parse(response);
                console.log('Datos parseados:', data); // Para debug
                
                if (data.status === 'ok') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'El envío se procesó correctamente',
                        confirmButtonColor: '#007bff',
                        confirmButtonText: 'Ver Ticket'
                    }).then((result) => {
                        // Abrir ticket de envío en nueva ventana
                        if (data.archivo) {
                            console.log('Abriendo ticket con archivo:', data.archivo); // Para debug
                            const ticketUrl = 'generar_ticket.php?file=' + encodeURIComponent(data.archivo) + '&tipo=envio&autoprint=true';
                            console.log('URL del ticket:', ticketUrl); // Para debug
                            window.open(ticketUrl, '_blank');
                        } else {
                            console.error('No se recibió el nombre del archivo');
                        }
                        
                        // Limpiar formulario
                        limpiarFormularioEnvio();
                    });
                } else {
                    mostrarError('Error al procesar: ' + (data.message || 'Error desconocido'));
                }
            } catch(e) {
                console.error('Error al parsear JSON:', e);
                console.error('Respuesta recibida:', response);
                mostrarError('Error en la respuesta del servidor');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', status, error);
            mostrarError('Error de conexión al procesar el envío');
        }
    });
}

// Función para mostrar errores (asegúrate de que exista)
function mostrarError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje,
        confirmButtonColor: '#dc3545'
    });
}

// Función para limpiar formulario (asegúrate de que exista)
function limpiarFormularioEnvio() {
    // Resetear el formulario
    $('#formEnvios')[0].reset();
    
    // Limpiar selector de tienda
    $('#selectTienda').val('');
    
    // Limpiar todo el contenedor
    limpiarSeleccionTienda();
}

// Funciones de utilidad
function mostrarCargando() {
    $('#productosGrid').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Cargando productos...</p></div>');
}

function ocultarCargando() {
    // Se maneja en el success/error de AJAX
}

function mostrarExito(mensaje) {
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: mensaje,
        timer: 3000,
        showConfirmButton: false
    });
}

// Validación en tiempo real en el modal
$('#modalCantidadEnvio').on('input', function() {
    const valor = parseFloat($(this).val()) || 0;
    const max = parseInt($(this).attr('max')) || 0;
    const $btn = $('#btnGuardarEnvioModal');
    
    if (valor < 0 || valor > max) {
        $btn.prop('disabled', true);
        $(this).addClass('is-invalid');
    } else {
        $btn.prop('disabled', false);
        $(this).removeClass('is-invalid');
    }
});