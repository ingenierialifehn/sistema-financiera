<?php
require_once __DIR__ . '/../config/database.php';

echo "=== DEBUG INFO ===\n";

try {
    $db = getDB();

    // 1. Columnas usuarios
    echo "\n[Columnas Usuarios]\n";
    $stmt = $db->query("SHOW COLUMNS FROM usuarios");
    foreach ($stmt->fetchAll() as $col) {
        echo "- " . $col['Field'] . "\n";
    }

    // 2. Tabla Puestos (si existe)
    echo "\n[Tabla Puestos]\n";
    try {
        $stmt = $db->query("SELECT * FROM puestos");
        foreach ($stmt->fetchAll() as $row) {
            echo "ID: " . ($row['id_puesto'] ?? $row['id']) . " | Nombre: " . ($row['nombre_puesto'] ?? $row['nombre']) . "\n";
        }
    } catch (Exception $e) {
        echo "Error leyendo puestos: " . $e->getMessage() . "\n";
    }

    // 3. Tabla Roles COMPLETA
    echo "\n[Tabla Roles]\n";
    try {
        $stmt = $db->query("SELECT * FROM roles");
        foreach ($stmt->fetchAll() as $row) {
            echo "ID: " . ($row['id_rol'] ?? $row['id']) . " | Nombre: " . ($row['nombre_rol'] ?? $row['nombre']) . "\n";
        }
    } catch (Exception $e) {
        echo "Error leyendo roles: " . $e->getMessage() . "\n";
    }

    // 4. Verificar Colaboradores
    echo "\n[Tabla Colaboradores]\n";
    try {
        $stmt = $db->query("SHOW COLUMNS FROM colaboradores");
        foreach ($stmt->fetchAll() as $col) {
            echo "- " . $col['Field'] . "\n";
        }

        // Ver si hay puestos en colaboradores
        echo "\nMuestra Colaboradores:\n";
        $stmt = $db->query("SELECT * FROM colaboradores LIMIT 5");
        foreach ($stmt->fetchAll() as $row) {
            print_r($row);
        }

    } catch (Exception $e) {
        echo "Tabla colaboradores no existe o error: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Error General: " . $e->getMessage();
}
?>