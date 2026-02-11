<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_FILES['logo'])) {
            throw new Exception("No se ha enviado ningún archivo");
        }

        $file = $_FILES['logo'];
        $allowed = ['image/png', 'image/jpeg', 'image/jpg'];

        if (!in_array($file['type'], $allowed)) {
            throw new Exception("Formato inválido. Solo PNG o JPG.");
        }

        $targetDir = __DIR__ . '/../../../public/admin/assets/img/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $targetFile = $targetDir . 'logo_empresa.png'; // Always save as fixed name for easy access in templates

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            echo json_encode(['success' => true, 'message' => 'Logo actualizado correctamente', 'url' => base_url('public/admin/assets/img/logo_empresa.png')]);
        } else {
            throw new Exception("Error al mover el archivo");
        }

    } else {
        throw new Exception("Método no permitido");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function base_url($path = '')
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))));
    // Fix logic based on real path structure if needed, but relative frontend path is usually safer
    return $protocol . $host . '/sistema-financiera/' . ltrim($path, '/');
}
?>