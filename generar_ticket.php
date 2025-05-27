<?php
session_start();
require_once 'Config/Database.php';

// Verificar si el archivo existe
$file = isset($_GET['file']) ? basename($_GET['file']) : null;
if (!$file || !file_exists(__DIR__ . '/ProdPendientes/' . $file)) {
    echo "<h3>Archivo no encontrado</h3>";
    exit;
}

// Leer datos del CSV
$filePath = __DIR__ . '/ProdPendientes/' . $file;
$productos = [];
$usuario = '';
$fecha = '';

if (($handle = fopen($filePath, 'r')) !== false) {
    // Leer encabezados
    $headers = fgetcsv($handle);
    
    // Índices para los campos
    $codIndex = array_search('CodigoPROD', $headers);
    $descIndex = array_search('Descripcion', $headers);
    $prodIndex = array_search('Produccion', $headers);
    $userIndex = array_search('Usuario', $headers);
    $dateIndex = array_search('Fecha', $headers);
    
    // Leer datos
    while (($data = fgetcsv($handle)) !== false) {
        $productos[] = [
            'codigo' => $data[$codIndex],
            'descripcion' => $data[$descIndex],
            'cantidad' => $data[$prodIndex]
        ];
        
        // Guardar usuario y fecha (tomamos los del primer registro)
        if (empty($usuario)) {
            $usuario = $data[$userIndex];
            $fecha = $data[$dateIndex];
        }
    }
    fclose($handle);
}

// Calcular total de unidades
$totalUnidades = 0;
foreach ($productos as $producto) {
    $totalUnidades += (int)$producto['cantidad'];
}

// Formatear fecha para mostrar
$fechaFormateada = date('d/m/Y H:i', strtotime($fecha));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Producción</title>
    <style>
        @media print {
            @page {
                margin: 0;
                size: 72mm auto;  /* Ancho de 72mm, altura automática */
            }
            body {
                margin: 0;
                padding: 5mm;
                width: 72mm;
                font-family: 'Courier New', monospace;
                font-size: 10pt;
            }
        }
        
        body {
            font-family: 'Courier New', monospace;
            width: 72mm;
            margin: 0 auto;
            padding: 5mm;
            font-size: 10pt;
        }
        
        .ticket-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5mm;
            margin-bottom: 5mm;
        }
        
        .ticket-title {
            font-size: 12pt;
            font-weight: bold;
            margin: 2mm 0;
        }
        
        .ticket-info {
            margin: 3mm 0;
        }
        
        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5mm 0;
        }
        
        .ticket-table th {
            border-bottom: 1px solid #000;
            text-align: left;
            padding: 1mm 0;
        }
        
        .ticket-table td {
            padding: 1mm 0;
        }
        
        .ticket-footer {
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 5mm;
            margin-top: 5mm;
            font-size: 9pt;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-row {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 2mm;
        }
        
        @media screen {
            body {
                border: 1px solid #ccc;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                margin: 10mm auto;
            }
            
            .print-button {
                display: block;
                width: 72mm;
                margin: 5mm auto;
                padding: 2mm;
                background: #007bff;
                color: white;
                text-align: center;
                cursor: pointer;
                border: none;
                border-radius: 2mm;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-header">
        <div class="ticket-title">GRUPO BENAVIDES</div>
        <div>HelenStock - Registro de Producción</div>
    </div>
    
    <div class="ticket-info">
        <div><strong>Folio:</strong> <?php echo str_replace('produccion_', '', $file); ?></div>
        <div><strong>Fecha:</strong> <?php echo $fechaFormateada; ?></div>
        <div><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario); ?></div>
    </div>
    
    <table class="ticket-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="text-right">Cant.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $producto): ?>
            <tr>
                <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                <td class="text-right"><?php echo htmlspecialchars($producto['cantidad']); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="2">Total unidades:</td>
                <td class="text-right"><?php echo $totalUnidades; ?></td>
            </tr>
        </tbody>
    </table>
    
    <div class="ticket-footer">
        <p>*** Documento informativo ***</p>
        <p>HelenStock <?php echo date('Y'); ?></p>
    </div>
    
    <button class="print-button" id="btnImprimir">Imprimir Ticket</button>
    
    <script>
        // Auto-imprimir cuando carga la página
        window.onload = function() {
            // Ocultamos el botón antes de imprimir
            document.getElementById('btnImprimir').addEventListener('click', function() {
                this.style.display = 'none';
                window.print();
                setTimeout(() => this.style.display = 'block', 1000);
            });
            
            // Auto-imprimir si viene desde el formulario
            <?php if (isset($_GET['autoprint']) && $_GET['autoprint'] === 'true'): ?>
            setTimeout(function() {
                document.getElementById('btnImprimir').style.display = 'none';
                window.print();
                setTimeout(() => document.getElementById('btnImprimir').style.display = 'block', 1000);
            }, 500);
            <?php endif; ?>
        };
    </script>
</body>
</html>