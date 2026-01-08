<?php
$file = 'public/admin/prestamo_detalle.php';
$content = file_get_contents($file);

// Reemplazar todos los espacios en toLocaleString
$content = str_replace(', { minimumFractionDigits: 2 }', ', {minimumFractionDigits: 2}', $content);
$content = str_replace(', {minimumFractionDigits: 2}', ', {minimumFractionDigits: 2}', $content);

file_put_contents($file, $content);
echo "✓ Espacios eliminados en toLocaleString\n";
