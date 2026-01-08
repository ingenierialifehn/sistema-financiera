<?php
/**
 * Debug endpoint para ver qué datos llegan al update
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Log everything
$logData = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'post' => $_POST,
    'files' => $_FILES,
    'get' => $_GET,
    'headers' => getallheaders(),
    'raw_input' => file_get_contents('php://input')
];

// Write to log file
file_put_contents(__DIR__ . '/debug_update.log', print_r($logData, true));

// Also return it
echo json_encode([
    'success' => true,
    'debug' => $logData
], JSON_PRETTY_PRINT);
?>