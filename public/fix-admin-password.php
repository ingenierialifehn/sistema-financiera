<?php
/**
 * Script para corregir la contraseña del admin
 * Ejecutar una vez y luego eliminar este archivo por seguridad
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

echo "<h2>Corregir Contraseña del Admin</h2>";

try {
    $db = getDB();
    
    // Generar nuevo hash para 'admin123'
    $newPassword = 'admin123';
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    echo "<p>Generando nuevo hash para: <strong>$newPassword</strong></p>";
    echo "<p>Hash generado: <code>$passwordHash</code></p>";
    
    // Actualizar contraseña del admin
    $stmt = $db->prepare("UPDATE usuarios SET password = :password WHERE usuario = 'admin'");
    $stmt->execute(['password' => $passwordHash]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Contraseña actualizada exitosamente</p>";
        
        // Verificar que funciona
        $stmt = $db->prepare("SELECT password FROM usuarios WHERE usuario = 'admin'");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (password_verify($newPassword, $user['password'])) {
            echo "<p style='color: green;'>✓ Verificación: La contraseña funciona correctamente</p>";
            echo "<p><strong>Ahora puedes hacer login con:</strong></p>";
            echo "<ul>";
            echo "<li>Usuario: <strong>admin</strong></li>";
            echo "<li>Password: <strong>admin123</strong></li>";
            echo "</ul>";
            echo "<p><a href='login.php'>Ir al Login</a></p>";
        } else {
            echo "<p style='color: red;'>✗ Error: La verificación falló</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No se pudo actualizar (puede que no exista el usuario admin)</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p style='color: red;'><strong>⚠ IMPORTANTE: Elimina este archivo después de usarlo por seguridad</strong></p>";

?>

