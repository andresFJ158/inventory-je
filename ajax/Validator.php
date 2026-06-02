<?php

/**
 * Input Validation Framework
 * Prevents SQL injection, XSS, and invalid data entry
 */
class Validator
{
    public static function required($value, $fieldName = 'Field')
    {
        if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
            throw new Exception("$fieldName is required");
        }
        return true;
    }

    public static function string($value, $fieldName = 'Field', $min = 0, $max = 255, $required = true)
    {
        if ($required) self::required($value, $fieldName);
        if ($value === null || $value === '') return '';

        $value = trim((string)$value);
        if (strlen($value) < $min) {
            throw new Exception("$fieldName must be at least $min characters");
        }
        if (strlen($value) > $max) {
            throw new Exception("$fieldName must not exceed $max characters");
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function integer($value, $fieldName = 'Field', $required = true)
    {
        if ($required) self::required($value, $fieldName);
        if ($value === null || $value === '') return null;

        $int = intval($value);
        if ((string)$int !== (string)$value && !ctype_digit((string)$value)) {
            throw new Exception("$fieldName must be a valid integer");
        }
        return $int;
    }

    public static function money($value, $fieldName = 'Field', $required = true)
    {
        if ($required) self::required($value, $fieldName);
        if ($value === null || $value === '') return null;

        $float = floatval($value);
        if ($float < 0) {
            throw new Exception("$fieldName cannot be negative");
        }
        return round($float, 2);
    }

    public static function email($value, $fieldName = 'Field', $required = true)
    {
        if ($required) self::required($value, $fieldName);
        if ($value === null || $value === '') return null;

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("$fieldName must be a valid email address");
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function phone($value, $fieldName = 'Field', $required = false)
    {
        if ($required) self::required($value, $fieldName);
        if ($value === null || $value === '') return null;

        $phone = preg_replace('/[^0-9+\-\s()]/', '', $value);
        if (strlen(preg_replace('/[^0-9]/', '', $phone)) < 6) {
            throw new Exception("$fieldName must be a valid phone number");
        }
        return $phone;
    }

    public static function date($value, $fieldName = 'Field', $required = true)
    {
        if ($required) self::required($value, $fieldName);
        if ($value === null || $value === '') return null;

        $d = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            throw new Exception("$fieldName must be a valid date (YYYY-MM-DD)");
        }
        return $value;
    }

    public static function enum($value, $fieldName = 'Field', $allowed = [])
    {
        if (!in_array($value, $allowed)) {
            throw new Exception("$fieldName must be one of: " . implode(', ', $allowed));
        }
        return $value;
    }

    public static function percentage($value, $fieldName = 'Field', $required = true)
    {
        $num = self::money($value, $fieldName, $required);
        if ($num === null) return null;
        if ($num < 0 || $num > 100) {
            throw new Exception("$fieldName must be between 0 and 100");
        }
        return $num;
    }

    public static function url($value, $fieldName = 'Field', $required = true)
    {
        if ($required) self::required($value, $fieldName);
        if ($value === null || $value === '') return null;

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new Exception("$fieldName must be a valid URL");
        }
        return $value;
    }

    public static function array($value, $fieldName = 'Field', $required = true)
    {
        if ($required) self::required($value, $fieldName);
        if ($value === null) return [];

        if (!is_array($value)) {
            throw new Exception("$fieldName must be an array");
        }
        return $value;
    }

    public static function json($value, $fieldName = 'Field', $required = true)
    {
        if ($required) self::required($value, $fieldName);
        if ($value === null || $value === '') return null;

        $decoded = json_decode($value, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("$fieldName must be valid JSON: " . json_last_error_msg());
        }
        return $decoded;
    }

    public static function nit($value, $fieldName = 'Field', $required = false)
    {
        if (!$required && ($value === null || $value === '')) return null;

        $nit = preg_replace('/[^0-9]/', '', $value);
        if (strlen($nit) < 5 || strlen($nit) > 12) {
            throw new Exception("$fieldName must be a valid NIT (5-12 digits)");
        }
        return $nit;
    }

    public static function dni($value, $fieldName = 'Field', $required = true)
    {
        if ($required) self::required($value, $fieldName);
        if ($value === null || $value === '') return null;

        $dni = preg_replace('/[^0-9]/', '', $value);
        if (strlen($dni) < 5 || strlen($dni) > 12) {
            throw new Exception("$fieldName must be a valid DNI (5-12 digits)");
        }
        return $dni;
    }

    /**
     * Batch validation - validate multiple fields at once
     */
    public static function batch($data, $rules)
    {
        $errors = [];
        foreach ($rules as $field => $ruleFns) {
            try {
                foreach ($ruleFns as $ruleFn) {
                    $ruleFn($data[$field] ?? null, $field);
                }
            } catch (Exception $e) {
                $errors[$field] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
        return true;
    }
}

class ValidationException extends Exception
{
    public $errors = [];

    public function __construct($errors)
    {
        $this->errors = $errors;
        parent::__construct("Validation failed");
    }
}

?>
