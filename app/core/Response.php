<?php
/**
 * Clase Response - Manejo centralizado de respuestas JSON
 */

class Response {
    
    /**
     * Enviar respuesta exitosa
     */
    public static function success($data = null, $message = 'Operación exitosa', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Enviar respuesta de error
     */
    public static function error($message = 'Error en la operación', $code = 400, $errors = null) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Respuesta de error de validación
     */
    public static function validationError($errors, $message = 'Error de validación') {
        self::error($message, 422, $errors);
    }
    
    /**
     * Respuesta de error de autenticación
     */
    public static function unauthorized($message = 'No autorizado') {
        self::error($message, 401);
    }
    
    /**
     * Respuesta de error de permisos
     */
    public static function forbidden($message = 'No tiene permisos para esta acción') {
        self::error($message, 403);
    }
    
    /**
     * Respuesta de recurso no encontrado
     */
    public static function notFound($message = 'Recurso no encontrado') {
        self::error($message, 404);
    }
    
    /**
     * Respuesta de error del servidor
     */
    public static function serverError($message = 'Error interno del servidor') {
        self::error($message, 500);
    }
}

