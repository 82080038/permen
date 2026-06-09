<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Standardized API Response Handler
 * 
 * Provides consistent JSON response format across all API endpoints.
 * All responses include: success (bool), message (string), data (mixed), timestamp (string)
 */
class ApiResponse
{
    /**
     * Send success response
     * 
     * @param array|object|null $data Response data
     * @param string $message Success message (in Indonesian)
     * @param int $code HTTP status code (default: 200)
     */
    public static function success($data = null, string $message = '', int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'timestamp' => date('c'),
                'request_id' => uniqid('req_', true)
            ]
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send error response
     * 
     * @param string $message Error message (in Indonesian)
     * @param int $code HTTP status code (default: 400)
     * @param array $errors Detailed error messages per field (optional)
     * @param string $errorCode Machine-readable error code (optional)
     */
    public static function error(string $message, int $code = 400, array $errors = [], string $errorCode = ''): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => $errorCode ?: 'ERROR_' . $code,
                'details' => $errors
            ],
            'meta' => [
                'timestamp' => date('c'),
                'request_id' => uniqid('req_', true)
            ]
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send paginated response
     * 
     * @param array $items Items for current page
     * @param int $total Total items count
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @param string $message Success message
     */
    public static function paginated(array $items, int $total, int $page, int $perPage, string $message = ''): void
    {
        self::success([
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
                'has_next' => ($page * $perPage) < $total,
                'has_prev' => $page > 1
            ]
        ], $message);
    }
    
    /**
     * Send created response (HTTP 201)
     * 
     * @param array|object|null $data Created resource data
     * @param string $message Success message
     */
    public static function created($data = null, string $message = 'Data berhasil dibuat'): void
    {
        self::success($data, $message, 201);
    }
    
    /**
     * Send no content response (HTTP 204)
     */
    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }
    
    /**
     * Common error shortcuts
     */
    
    public static function unauthorized(string $message = 'Autentikasi diperlukan'): void
    {
        self::error($message, 401, [], 'UNAUTHORIZED');
    }
    
    public static function forbidden(string $message = 'Akses ditolak'): void
    {
        self::error($message, 403, [], 'FORBIDDEN');
    }
    
    public static function notFound(string $message = 'Data tidak ditemukan'): void
    {
        self::error($message, 404, [], 'NOT_FOUND');
    }
    
    public static function validationError(array $errors, string $message = 'Validasi gagal'): void
    {
        self::error($message, 422, $errors, 'VALIDATION_ERROR');
    }
    
    public static function rateLimit(string $message = 'Terlalu banyak permintaan. Silakan coba lagi nanti.'): void
    {
        self::error($message, 429, [], 'RATE_LIMIT');
    }
    
    public static function serverError(string $message = 'Terjadi kesalahan server'): void
    {
        self::error($message, 500, [], 'SERVER_ERROR');
    }
}
