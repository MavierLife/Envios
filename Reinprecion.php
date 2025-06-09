<?php
session_start();
require_once 'Config/Database.php';

// Configurar zona horaria de El Salvador
date_default_timezone_set('America/El_Salvador');

// --- INICIO DE LA SECCIÓN PARA EVITAR CARGA DIRECTA SIN ESTILOS ---
// Si no es una solicitud AJAX Y se accede directamente a este archivo
if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    $currentPage = basename(__FILE__);
    $pageNameWithoutExtension = pathinfo($currentPage, PATHINFO_FILENAME); // obtiene "Reinprecion"
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

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                <i class="icon-undo2" style="color: #17a2b8;"></i>
                Reinpresión
            </h1>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="icon-printer"></i>
                        Módulo de Reinpresión
                    </h3>
                </div>
                <div class="panel-body">
                    <!-- Selector de tipo de reinpresión -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipoReinpresion">Seleccione el tipo de reinpresión:</label>
                                <select class="form-control" id="tipoReinpresion" name="tipoReinpresion">
                                    <option value="">-- Seleccione una opción --</option>
                                    <option value="envios">Envíos a Tiendas</option>
                                    <option value="producciones">Producciones</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Contenedor para mostrar archivos disponibles -->
                    <div class="row" id="archivosContainer" style="display: none;">
                        <div class="col-lg-12">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <i class="icon-folder-open"></i>
                                        Archivos Disponibles para Reinpresión
                                    </h3>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="tablaArchivos">
                                            <thead>
                                                <tr>
                                                    <th>Archivo</th>
                                                    <th>Fecha de Creación</th>
                                                    <th>Tamaño</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="listaArchivos">
                                                <!-- Los archivos se cargarán aquí dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Área de resultados -->
                    <div class="row" id="resultadosContainer" style="display: none;">
                        <div class="col-lg-12">
                            <div class="panel panel-success">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <i class="icon-checkmark"></i>
                                        Resultado de la Reinpresión
                                    </h3>
                                </div>
                                <div class="panel-body" id="resultadosContent">
                                    <!-- Los resultados se mostrarán aquí -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Manejar cambio en el selector de tipo
    $('#tipoReinpresion').change(function() {
        var tipo = $(this).val();
        
        if (tipo) {
            cargarArchivos(tipo);
            $('#archivosContainer').show();
            $('#resultadosContainer').hide();
        } else {
            $('#archivosContainer').hide();
            $('#resultadosContainer').hide();
        }
    });
});

function cargarArchivos(tipo) {
    $.ajax({
        url: 'Ajax/listar_archivos_reinpresion.php',
        type: 'POST',
        data: { tipo: tipo },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarArchivos(response.archivos);
            } else {
                mostrarError('Error al cargar archivos: ' + response.message);
            }
        },
        error: function() {
            mostrarError('Error de conexión al cargar archivos');
        }
    });
}

function mostrarArchivos(archivos) {
    var html = '';
    
    if (archivos.length === 0) {
        html = '<tr><td colspan="5" class="text-center">No hay archivos disponibles</td></tr>';
    } else {
        archivos.forEach(function(archivo) {
            html += '<tr>';
            html += '<td><i class="icon-file-text"></i> ' + archivo.nombre + archivo.estado + '</td>';
            html += '<td>' + archivo.fecha + '</td>';
            html += '<td>' + archivo.tamaño + '</td>';
            html += '<td>';
            html += '<button class="btn btn-primary btn-sm" onclick="reimprimir(\'' + archivo.ruta + '\', \'' + archivo.nombre + '\')">';
            html += '<i class="icon-printer"></i> Reimprimir';
            html += '</button>';
            html += '</td>';
            html += '</tr>';
        });
    }
    
    $('#listaArchivos').html(html);
}

function reimprimir(ruta, nombre) {
    // Mostrar loading
    $('#resultadosContent').html('<div class="text-center"><i class="icon-spinner spin"></i> Procesando reinpresión...</div>');
    $('#resultadosContainer').show();
    
    $.ajax({
        url: 'Ajax/procesar_reinpresion.php',
        type: 'POST',
        data: { 
            archivo: ruta,
            nombre: nombre
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (response.redirect) {
                    // Abrir la página de impresión en una nueva ventana
                    window.open(response.redirect, '_blank');
                    $('#resultadosContent').html(
                        '<div class="alert alert-success">' +
                        '<i class="icon-checkmark"></i> ' + response.message +
                        '</div>'
                    );
                } else {
                    $('#resultadosContent').html(
                        '<div class="alert alert-success">' +
                        '<i class="icon-checkmark"></i> ' + response.message +
                        '</div>'
                    );
                }
            } else {
                $('#resultadosContent').html(
                    '<div class="alert alert-danger">' +
                    '<i class="icon-cross"></i> Error: ' + response.message +
                    '</div>'
                );
            }
        },
        error: function() {
            $('#resultadosContent').html(
                '<div class="alert alert-danger">' +
                '<i class="icon-cross"></i> Error de conexión al procesar reinpresión' +
                '</div>'
            );
        }
    });
}

function mostrarError(mensaje) {
    $('#listaArchivos').html('<tr><td colspan="4" class="text-center text-danger">' + mensaje + '</td></tr>');
}
</script>