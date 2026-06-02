<?php

/**
 * Centralized Error Handling
 * Standardizes error responses and logging
 */
class ErrorHandler
{
    private static $logFile = '/tmp/erp_errors.log';

    /**
     * Handle exceptions with proper logging and response
     */
    public static function handle($exception, $isDevelopment = false)
    {
        $statusCode = 500;
        $message = $isDevelopment ? $exception->getMessage() : 'An error occurred';
        $details = null;

        // Map exception types to status codes
        if ($exception instanceof ValidationException) {
            $statusCode = 422;
            $message = 'Validation failed';
            $details = $exception->errors;
        } elseif ($exception instanceof AuthException) {
            $statusCode = 401;
            $message = 'Authentication failed';
        } elseif ($exception instanceof PermissionException) {
            $statusCode = 403;
            $message = 'Permission denied';
        } elseif ($exception instanceof NotFoundException) {
            $statusCode = 404;
            $message = 'Resource not found';
        }

        // Log error
        self::log($exception, $statusCode);

        // Return standardized response
        return self::response($statusCode, $message, $details);
    }

    /**
     * Log error with context
     */
    private static function log($exception, $statusCode)
    {
        $context = [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $statusCode,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null,
            'ip_address' => self::getClientIp(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'trace' => $exception->getTraceAsString()
        ];

        $logEntry = json_encode($context) . "\n";
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Get client IP address
     */
    private static function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        // Validate IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        return 'invalid';
    }

    /**
     * Standardized error response
     */
    public static function response($statusCode, $message, $details = null)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);

        $response = [
            'status' => $statusCode,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($details !== null) {
            $response['details'] = $details;
        }

        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Success response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);

        $response = [
            'status' => $statusCode,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}

class AuthException extends Exception {}
class PermissionException extends Exception {}
class NotFoundException extends Exception {}

?>
