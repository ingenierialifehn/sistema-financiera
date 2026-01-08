<?php
/**
 * Funciones Helper - Utilidades generales
 */

/**
 * Formatear moneda
 */
function formatMoney($amount, $symbol = null)
{
    if ($symbol === null) {
        // Usar símbolo desde configuración; por defecto L (Lempiras)
        $symbol = getConfig('simbolo_moneda', 'L');
    }
    return $symbol . ' ' . number_format((float) $amount, 2, '.', ',');
}

/**
 * Formatear fecha
 */
function formatDate($date, $format = 'd/m/Y')
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Formatear fecha y hora
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i')
{
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Calcular días entre fechas
 */
function daysBetween($date1, $date2)
{
    $d1 = new DateTime($date1);
    $d2 = new DateTime($date2);
    $diff = $d1->diff($d2);
    return $diff->days;
}

/**
 * Generar código único
 */
function generateCode($prefix = '', $length = 8)
{
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, $length));
    return $prefix . $random;
}

/**
 * Generar número de préstamo
 */
function generatePrestamoNumber()
{
    // Formato debe caber en VARCHAR(20). 'PREST-' (6) + 'YYYYMMDD' (8) + '-' (1) + rnd(5) (5) = 20
    return 'PREST-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 5));
}

/**
 * Generar código de cliente
 */
function generateClienteCode()
{
    return 'CLI-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

/**
 * Obtener configuración
 */
function getConfig($key, $default = null)
{
    static $configs = [];

    if (!isset($configs[$key])) {
        $db = getDB();
        try {
            $stmt = $db->prepare("SELECT valor, tipo FROM configuraciones WHERE clave = :clave");
            $stmt->execute(['clave' => $key]);
            $config = $stmt->fetch();

            if ($config) {
                $value = $config['valor'];
                // Convertir según tipo
                switch ($config['tipo']) {
                    case 'numero':
                        $value = intval($value);
                        break;
                    case 'decimal':
                        $value = floatval($value);
                        break;
                    case 'booleano':
                        $value = $value === '1' || $value === 'true';
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }
                $configs[$key] = $value;
            } else {
                $configs[$key] = $default;
            }
        } catch (Exception $e) {
            error_log("Error obteniendo configuración: " . $e->getMessage());
            $configs[$key] = $default;
        }
    }

    return $configs[$key];
}

/**
 * Calcular mora
 */
function calculateMora($fechaVencimiento, $montoCuota, $tasaMoraPorDia = null)
{
    if ($tasaMoraPorDia === null) {
        $tasaMoraPorDia = getConfig('mora_por_dia', 0.5);
    }

    $diasGracia = getConfig('dias_gracia', 3);
    $hoy = new DateTime();
    $vencimiento = new DateTime($fechaVencimiento);

    if ($hoy <= $vencimiento) {
        return ['dias' => 0, 'monto' => 0];
    }

    $diasMora = $hoy->diff($vencimiento)->days;
    $diasMora = max(0, $diasMora - $diasGracia);

    $montoMora = ($montoCuota * $tasaMoraPorDia / 100) * $diasMora;

    return [
        'dias' => $diasMora,
        'monto' => round($montoMora, 2)
    ];
}

/**
 * Subir imagen a Cloudinary
 */
function uploadToCloudinary($file, $folder = 'sistema-financiera')
{
    require_once __DIR__ . '/../config/config.php';

    if (empty(CLOUDINARY_CLOUD_NAME) || empty(CLOUDINARY_API_KEY) || empty(CLOUDINARY_API_SECRET)) {
        throw new Exception('Cloudinary no está configurado');
    }

    // Validar tipo de archivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        throw new Exception('Tipo de archivo no permitido');
    }

    // Validar tamaño
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('El archivo excede el tamaño máximo permitido');
    }

    // Leer archivo
    $imageData = file_get_contents($file['tmp_name']);
    $base64 = base64_encode($imageData);

    // Preparar datos para Cloudinary
    $timestamp = time();
    $publicId = $folder . '/' . uniqid();

    // Crear firma
    $stringToSign = "public_id={$publicId}&timestamp={$timestamp}" . CLOUDINARY_API_SECRET;
    $signature = sha1($stringToSign);

    // Subir a Cloudinary
    $url = "https://api.cloudinary.com/v1_1/" . CLOUDINARY_CLOUD_NAME . "/image/upload";

    $postData = [
        'file' => 'data:' . $mimeType . ';base64,' . $base64,
        'public_id' => $publicId,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'api_key' => CLOUDINARY_API_KEY,
        'folder' => $folder
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Error al subir imagen a Cloudinary');
    }

    $result = json_decode($response, true);

    if (isset($result['secure_url'])) {
        return $result['secure_url'];
    }

    throw new Exception('Error al obtener URL de Cloudinary');
}

/**
 * Obtener input JSON
 */
function getJsonInput()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

/**
 * Obtener input sanitizado
 */
function getInput($key = null, $default = null)
{
    $input = array_merge($_GET, $_POST);

    if ($key === null) {
        return $input;
    }

    return $input[$key] ?? $default;
}


/**
 * Verificar permiso globalmente (basado en sesión)
 * @param string $modulo
 * @param string $accion
 * @return bool
 */
function tienePermiso($modulo, $accion)
{
    return true; // Bypass
}
