<?php
/**
 * Clase Validator - Validación y sanitización de datos
 */

class Validator {
    
    /**
     * Validar y sanitizar string
     */
    public static function string($value, $minLength = 0, $maxLength = 255, $required = true) {
        if ($required && empty($value)) {
            return false;
        }
        
        if (!empty($value)) {
            $value = trim($value);
            $length = mb_strlen($value);
            
            if ($minLength > 0 && $length < $minLength) {
                return false;
            }
            
            if ($maxLength > 0 && $length > $maxLength) {
                return false;
            }
        }
        
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar email
     */
    public static function email($value, $required = true) {
        if ($required && empty($value)) {
            return false;
        }
        
        if (!empty($value)) {
            $value = filter_var(trim($value), FILTER_VALIDATE_EMAIL);
            if ($value === false) {
                return false;
            }
        }
        
        return $value;
    }
    
    /**
     * Validar número
     */
    public static function number($value, $min = null, $max = null, $required = true) {
        if ($required && ($value === null || $value === '')) {
            return false;
        }
        
        if (!empty($value)) {
            if (!is_numeric($value)) {
                return false;
            }
            
            $value = floatval($value);
            
            if ($min !== null && $value < $min) {
                return false;
            }
            
            if ($max !== null && $value > $max) {
                return false;
            }
        }
        
        return $value;
    }
    
    /**
     * Validar entero
     */
    public static function integer($value, $min = null, $max = null, $required = true) {
        if ($required && ($value === null || $value === '')) {
            return false;
        }
        
        if (!empty($value)) {
            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                return false;
            }
            
            $value = intval($value);
            
            if ($min !== null && $value < $min) {
                return false;
            }
            
            if ($max !== null && $value > $max) {
                return false;
            }
        }
        
        return $value;
    }
    
    /**
     * Validar fecha
     */
    public static function date($value, $format = 'Y-m-d', $required = true) {
        if ($required && empty($value)) {
            return false;
        }
        
        if (!empty($value)) {
            $d = DateTime::createFromFormat($format, $value);
            if ($d && $d->format($format) === $value) {
                return $value;
            }
            return false;
        }
        
        return $value;
    }
    
    /**
     * Validar teléfono
     */
    public static function phone($value, $required = true) {
        if ($required && empty($value)) {
            return false;
        }
        
        if (!empty($value)) {
            // Eliminar espacios y caracteres especiales excepto + y números
            $value = preg_replace('/[^0-9+]/', '', trim($value));
            // Honduras: teléfonos pueden ser de 8 dígitos (permitir 8-15)
            if (strlen($value) < 8 || strlen($value) > 15) {
                return false;
            }
        }
        
        return $value;
    }
    
    /**
     * Validar DNI/RUC
     */
    public static function documento($value, $tipo = 'DNI', $required = true) {
        if ($required && empty($value)) {
            return false;
        }
        
        if (!empty($value)) {
            $value = preg_replace('/[^0-9]/', '', trim($value));
            
            if ($tipo === 'DNI') {
                // Aceptar DNI de 8 (PE) o 13 (HN - Identidad)
                $len = strlen($value);
                if (!in_array($len, [8, 13], true)) {
                    return false;
                }
            } elseif ($tipo === 'RUC') {
                if (strlen($value) !== 11) {
                    return false;
                }
            }
        }
        
        return $value;
    }
    
    /**
     * Sanitizar entrada para prevenir SQL Injection
     */
    public static function sanitize($value) {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar múltiples campos
     */
    public static function validate($data, $rules) {
        $errors = [];
        $validated = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $required = $rule['required'] ?? true;
            
            // Validar requerido
            if ($required && ($value === null || $value === '')) {
                $errors[$field] = "El campo {$field} es requerido";
                continue;
            }
            
            // Si no es requerido y está vacío, continuar
            if (!$required && ($value === null || $value === '')) {
                $validated[$field] = null;
                continue;
            }
            
            // Aplicar validación según tipo
            $type = $rule['type'] ?? 'string';
            $result = null;
            
            switch ($type) {
                case 'string':
                    $result = self::string($value, $rule['min'] ?? 0, $rule['max'] ?? 255, false);
                    break;
                case 'email':
                    $result = self::email($value, false);
                    break;
                case 'number':
                    $result = self::number($value, $rule['min'] ?? null, $rule['max'] ?? null, false);
                    break;
                case 'integer':
                    $result = self::integer($value, $rule['min'] ?? null, $rule['max'] ?? null, false);
                    break;
                case 'date':
                    $result = self::date($value, $rule['format'] ?? 'Y-m-d', false);
                    break;
                case 'phone':
                    $result = self::phone($value, false);
                    break;
                case 'documento':
                    $result = self::documento($value, $rule['tipo'] ?? 'DNI', false);
                    break;
            }
            
            if ($result === false) {
                $errors[$field] = $rule['message'] ?? "El campo {$field} no es válido";
            } else {
                $validated[$field] = $result;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validated
        ];
    }
}

