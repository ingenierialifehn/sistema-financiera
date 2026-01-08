<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $db = getDB();
    $db->prepare("UPDATE prestamos SET estado = 'Activo' WHERE id = ?")->execute([$id]);

    // Auto asignar al usuario actual si no tiene
    $uid = $_SESSION['id_usuario'] ?? 1;
    $db->prepare("UPDATE prestamos SET asesor_creditos_id = ?, oficial_desembolsos_id = ? WHERE id = ? AND (asesor_creditos_id IS NULL OR asesor_creditos_id = 0)")->execute([$uid, $uid, $id]);

    header('Location: check_status.php');
}
?>