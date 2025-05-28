<?php
session_start();
require_once 'Config/Database.php';

// Detectar si es producción o envío
$file = isset($_GET['file']) ? basename($_GET['file']) : null;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'produccion'; // 'produccion' o 'envio'

if (!$file) {
    echo "<h3>Archivo no especificado</h3>";
    exit;
}

$productos = [];
$usuario = '';
$fecha = '';
$tiendaInfo = '';
$totalUnidades = 0;

if ($tipo === 'envio') {
    // Manejar archivos de envío
    $filePath = __DIR__ . '/EnviosRealizados/' . $file;
    if (!file_exists($filePath)) {
        echo "<h3>Archivo de envío no encontrado</h3>";
        exit;
    }
    
    if (($handle = fopen($filePath, 'r')) !== false) {
        // Leer encabezados
        $headers = fgetcsv($handle);
        
        // Índices para los campos de envío
        $codIndex = array_search('CodigoPROD', $headers);
        $descIndex = array_search('Descripcion', $headers);
        $cantIndex = array_search('CantidadEnviada', $headers);
        $exAntIndex = array_search('ExistenciaAnterior', $headers);
        $exNueIndex = array_search('ExistenciaNueva', $headers);
        $tiendaIndex = array_search('TiendaUUID', $headers);
        $userIndex = array_search('Usuario', $headers);
        $dateIndex = array_search('Fecha', $headers);
        
        // Leer datos
        while (($data = fgetcsv($handle)) !== false) {
            $productos[] = [
                'codigo' => $data[$codIndex],
                'descripcion' => $data[$descIndex],
                'cantidad' => $data[$cantIndex],
                'existencia_anterior' => $data[$exAntIndex],
                'existencia_nueva' => $data[$exNueIndex]
            ];
            
            // Guardar datos generales (tomamos los del primer registro)
            if (empty($usuario)) {
                $usuario = $data[$userIndex];
                $fecha = $data[$dateIndex];
                $tiendaInfo = 'Tienda: ' . substr($data[$tiendaIndex], 0, 8) . '...';
            }
            
            $totalUnidades += (int)$data[$cantIndex];
        }
        fclose($handle);
    }
} else {
    // Manejar archivos de producción (código original)
    $filePath = __DIR__ . '/ProdPendientes/' . $file;
    if (!file_exists($filePath)) {
        echo "<h3>Archivo de producción no encontrado</h3>";
        exit;
    }

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
            
            $totalUnidades += (int)$data[$prodIndex];
        }
        fclose($handle);
    }
}

// Formatear fecha para mostrar
$fechaFormateada = date('d/m/Y H:i', strtotime($fecha));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de <?php echo $tipo === 'envio' ? 'Envío' : 'Producción'; ?></title>
    <style>
        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            body {
                margin: 0;
                padding: 3mm;
                width: 80mm;
                font-family: 'Courier New', monospace;
                font-size: 8pt;
                min-height: auto;
                height: auto;
            }
            .ticket-footer {
                position: relative;
                bottom: auto;
                margin-bottom: 0;
            }
            .print-button {
                display: none !important;
            }
        }
        
        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 3mm;
            font-size: 8pt;
            height: auto;
        }
        
        .ticket-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }
        
        .ticket-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 1mm 0;
        }
        
        .ticket-info {
            margin: 2mm 0;
            font-size: 8pt;
        }
        
        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            margin: 3mm 0;
            font-size: 8pt;
        }
        
        .ticket-table th {
            border-bottom: 1px solid #000;
            text-align: left;
            padding: 0.5mm 0;
        }
        
        .ticket-table td {
            padding: 0.5mm 0;
        }
        
        .text-right {
            text-align: right;
            padding-right: 5mm;
        }
        
        .text-center {
            text-align: center;
        }
        
        .cantidad-col {
            min-width: 15mm;
            text-align: center;
        }
        
        .total-row {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 1mm;
        }
        
        @media screen {
            body {
                border: 1px solid #ccc;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                margin: 10mm auto;
            }
            
            .print-button {
                display: block;
                width: 80mm;
                margin: 3mm auto;
                padding: 1.5mm;
                background: #007bff;
                color: white;
                text-align: center;
                cursor: pointer;
                border: none;
                border-radius: 1.5mm;
                font-size: 9pt;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-header">
        <div class="ticket-title">QUALITY BREAD</div>
        <div>HelenStock - <?php echo $tipo === 'envio' ? 'Envío a Tienda' : 'Registro de Producción'; ?></div>
    </div>
    
    <div class="ticket-info">
        <div><strong>Folio:</strong> <?php echo str_replace([$tipo . '_', '.csv'], '', $file); ?></div>
        <div><strong>Fecha:</strong> <?php echo $fechaFormateada; ?></div>
        <div><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario); ?></div>
        <?php if ($tipo === 'envio' && $tiendaInfo): ?>
        <div><strong><?php echo htmlspecialchars($tiendaInfo); ?></strong></div>
        <?php endif; ?>
    </div>
    
    <table class="ticket-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="cantidad-col"><?php echo $tipo === 'envio' ? 'Env.' : 'Cant.'; ?></th>
                <?php if ($tipo === 'envio'): ?>
                <th class="cantidad-col">Stock</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $producto): ?>
            <tr>
                <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                <td class="cantidad-col"><?php echo htmlspecialchars($producto['cantidad']); ?></td>
                <?php if ($tipo === 'envio'): ?>
                <td class="cantidad-col"><?php echo htmlspecialchars($producto['existencia_nueva']); ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="<?php echo $tipo === 'envio' ? '3' : '2'; ?>">Total <?php echo $tipo === 'envio' ? 'enviado' : 'unidades'; ?>:</td>
                <td class="cantidad-col"><?php echo $totalUnidades; ?></td>
            </tr>
        </tbody>
    </table>
    
    <div class="ticket-footer">
        <p>*** Documento informativo ***</p>
        <p>HelenStock <?php echo date('Y'); ?></p>
    </div>
    
    <button class="print-button" id="btnImprimir">Imprimir Ticket</button>
    
    <script>
        window.onload = function() {
            document.getElementById('btnImprimir').addEventListener('click', function() {
                this.style.display = 'none';
                window.print();
                setTimeout(() => this.style.display = 'block', 1000);
            });
            
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