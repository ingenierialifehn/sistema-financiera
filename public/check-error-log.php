<?php
/**
 * Verificar logs de error de PHP
 */

echo "<h2>Logs de Error de PHP</h2>";

$logFile = ini_get('error_log');
if (empty($logFile)) {
    $logFile = 'php_errors.log';
}

echo "<p>Ruta del log: <strong>" . $logFile . "</strong></p>";

// Intentar leer el log de XAMPP
$possibleLogs = [
    'C:/xampp/php/logs/php_error_log',
    'C:/xampp/apache/logs/error.log',
    'C:/xampp/php/php_error_log',
    __DIR__ . '/../php_errors.log'
];

echo "<h3>Últimas líneas de logs:</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 500px; overflow: auto;'>";

foreach ($possibleLogs as $logPath) {
    if (file_exists($logPath) && is_readable($logPath)) {
        echo "=== $logPath ===\n";
        $lines = file($logPath);
        if ($lines) {
            $lastLines = array_slice($lines, -20); // Últimas 20 líneas
            echo implode('', $lastLines);
        }
        echo "\n\n";
    }
}

echo "</pre>";

?>

