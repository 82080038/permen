<?php

declare(strict_types=1);

namespace App\Core;

use App\Database\Database;
use App\Security\SecurityManager;

/**
 * Application bootstrap
 */
class App
{
    private static ?self $instance = null;

    private Database $db;
    private SecurityManager $security;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->security = new SecurityManager($this->db->getPdo());
        $this->setupErrorHandling();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function setupErrorHandling(): void
    {
        $env = $_ENV['APP_ENV'] ?? 'development';

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (!(error_reporting() & $errno)) {
                return false;
            }

            $types = [
                E_ERROR => 'Error',
                E_WARNING => 'Warning',
                E_PARSE => 'Parse Error',
                E_NOTICE => 'Notice',
                E_DEPRECATED => 'Deprecated',
            ];

            $type = $types[$errno] ?? 'Unknown Error';
            error_log("[$type] $errstr in $errfile on line $errline");

            return true;
        });

        set_exception_handler(function ($e) use ($env) {
            error_log("Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

            if ($env === 'production') {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Internal Server Error']);
            } else {
                throw $e;
            }
        });
    }

    public function database(): Database
    {
        return $this->db;
    }

    public function security(): SecurityManager
    {
        return $this->security;
    }
}
