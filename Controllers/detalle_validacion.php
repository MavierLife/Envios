<?php
if (empty($_GET['file'])) exit('Archivo no especificado');
$file = basename($_GET['file']);
$path = dirname(__DIR__) . '/ProdPendientes/' . $file;
if (!is_readable($path)) exit('No se puede leer el archivo.');

echo '<table class="table table-sm table-bordered">';
if (($h = fopen($path, 'r')) !== false) {
  $row = 0;
  while (($data = fgetcsv($h, 10000, ",")) !== false) {
    echo '<tr' . ($row++===0 ? ' class="table-primary"' : '') . '>';
    foreach ($data as $cell) {
      echo $row===1
           ? '<th>'.htmlspecialchars($cell).'</th>'
           : '<td>'.htmlspecialchars($cell).'</td>';
    }
    echo '</tr>';
  }
  fclose($h);
}

echo '</table>';