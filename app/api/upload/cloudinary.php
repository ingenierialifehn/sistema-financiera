<?php
/**
 * API para subir imágenes a Cloudinary desde el cliente
 * Acepta base64 y lo sube a Cloudinary
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

// Verificar autenticación
AuthMiddleware::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = getJsonInput();

if (!isset($data['image']) || empty($data['image'])) {
    Response::error('No se proporcionó imagen', 400);
}

$base64Data = $data['image'];
$folder = $data['folder'] ?? 'sistema-financiera';

// Validar que sea base64 de imagen
if (strpos($base64Data, 'data:image') !== 0) {
    Response::error('Formato de imagen inválido. Debe ser base64 con prefijo data:image', 400);
}

try {
    // Extraer datos de la imagen
    list($type, $base64Data) = explode(';', $base64Data);
    list(, $base64Data) = explode(',', $base64Data);
    $imageData = base64_decode($base64Data);
    
    if ($imageData === false) {
        throw new Exception('Error al decodificar imagen base64');
    }
    
    // Validar tamaño (máximo 5MB)
    if (strlen($imageData) > MAX_UPLOAD_SIZE) {
        Response::error('La imagen excede el tamaño máximo permitido (5MB)', 400);
    }
    
    // Validar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_buffer($finfo, $imageData);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        Response::error('Tipo de imagen no permitido. Solo se permiten: JPEG, PNG, WebP', 400);
    }
    
    // Obtener credenciales de Cloudinary
    $cloudName = getConfig('cloudinary_cloud_name', CLOUDINARY_CLOUD_NAME);
    $apiKey = getConfig('cloudinary_api_key', CLOUDINARY_API_KEY);
    $apiSecret = getConfig('cloudinary_api_secret', CLOUDINARY_API_SECRET);
    
    if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
        Response::error('Cloudinary no está configurado correctamente', 500);
    }
    
    // Preparar datos para Cloudinary
    $timestamp = time();
    $publicId = $folder . '/' . uniqid('pago_' . time() . '_');
    
    // Crear firma
    $stringToSign = "public_id={$publicId}&timestamp={$timestamp}" . $apiSecret;
    $signature = sha1($stringToSign);
    
    // Crear archivo temporal
    $tempFile = tmpfile();
    $tempPath = stream_get_meta_data($tempFile)['uri'];
    file_put_contents($tempPath, $imageData);
    
    // Preparar datos para upload
    $postData = [
        'file' => new CURLFile($tempPath, $mimeType),
        'public_id' => $publicId,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'api_key' => $apiKey,
        'folder' => $folder
    ];
    
    // Subir a Cloudinary
    $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Cerrar archivo temporal
    fclose($tempFile);
    
    if ($httpCode !== 200) {
        error_log("Error Cloudinary HTTP $httpCode: $response");
        Response::error('Error al subir imagen a Cloudinary', 500);
    }
    
    if (!empty($error)) {
        error_log("Error Cloudinary cURL: $error");
        Response::error('Error de conexión con Cloudinary', 500);
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['secure_url'])) {
        error_log("Respuesta Cloudinary inválida: $response");
        Response::error('Error al obtener URL de Cloudinary', 500);
    }
    
    // Registrar actividad
    $user = AuthMiddleware::requireAuth();
    Auth::logActivity($user['id'], 'upload', 'comprobante', 'Imagen subida a Cloudinary: ' . $result['secure_url']);
    
    Response::success([
        'url' => $result['secure_url'],
        'public_id' => $result['public_id'] ?? null,
        'width' => $result['width'] ?? null,
        'height' => $result['height'] ?? null,
        'format' => $result['format'] ?? null,
        'bytes' => $result['bytes'] ?? null
    ], 'Imagen subida exitosamente');
    
} catch (Exception $e) {
    error_log("Error en upload Cloudinary: " . $e->getMessage());
    Response::error('Error al procesar imagen: ' . $e->getMessage(), 500);
}

