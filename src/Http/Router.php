<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Simple Router for SKD CAT-BKN Application
 * 
 * Provides basic routing capabilities for cleaner URL handling
 * and centralized request dispatching.
 * 
 * Usage:
 *   $router = new Router();
 *   $router->get('/user/dashboard', 'UserController@dashboard');
 *   $router->post('/api/submit', 'ApiController@submit');
 *   $router->dispatch();
 */
class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $basePath = '/permen';
    
    /**
     * Register GET route
     */
    public function get(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    /**
     * Register POST route
     */
    public function post(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    /**
     * Register PUT route
     */
    public function put(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }
    
    /**
     * Register DELETE route
     */
    public function delete(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }
    
    /**
     * Add a route
     */
    private function addRoute(string $method, string $path, $handler, array $middleware): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->normalizePath($path),
            'handler' => $handler,
            'middleware' => $middleware,
            'regex' => $this->pathToRegex($path)
        ];
        return $this;
    }
    
    /**
     * Register global middleware
     */
    public function middleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }
    
    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->getCurrentPath();
        
        // Handle preflight OPTIONS
        if ($method === 'OPTIONS') {
            $this->sendCorsHeaders();
            exit;
        }
        
        // Find matching route
        $route = $this->findRoute($method, $path);
        
        if (!$route) {
            $this->sendNotFound();
            return;
        }
        
        // Execute global middleware
        foreach ($this->middleware as $middleware) {
            $result = $middleware();
            if ($result === false) {
                return; // Middleware stopped execution
            }
        }
        
        // Execute route middleware
        foreach ($route['middleware'] as $middleware) {
            $result = $middleware();
            if ($result === false) {
                return;
            }
        }
        
        // Execute handler
        $this->executeHandler($route['handler'], $route['params'] ?? []);
    }
    
    /**
     * Find matching route for method and path
     */
    private function findRoute(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            // Exact match
            if ($route['path'] === $path) {
                return $route;
            }
            
            // Regex match for parameterized routes
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                $route['params'] = $params;
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Execute route handler
     */
    private function executeHandler($handler, array $params): void
    {
        // String handler: 'Controller@method' or 'file.php'
        if (is_string($handler)) {
            if (str_contains($handler, '@')) {
                // Controller@method format
                [$controller, $method] = explode('@', $handler);
                $controllerClass = "App\\Controllers\\$controller";
                
                if (!class_exists($controllerClass)) {
                    throw new \RuntimeException("Controller not found: $controller");
                }
                
                $instance = new $controllerClass();
                
                if (!method_exists($instance, $method)) {
                    throw new \RuntimeException("Method not found: $method");
                }
                
                $instance->$method(...$params);
            } else {
                // Direct file include
                $file = __DIR__ . '/../../pages/' . $handler;
                if (file_exists($file)) {
                    require $file;
                } else {
                    throw new \RuntimeException("File not found: $handler");
                }
            }
            return;
        }
        
        // Callable handler
        if (is_callable($handler)) {
            $handler(...$params);
            return;
        }
        
        throw new \RuntimeException("Invalid handler");
    }
    
    /**
     * Convert path to regex pattern
     */
    private function pathToRegex(string $path): string
    {
        // Replace {param} with regex capture group
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Normalize path
     */
    private function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }
    
    /**
     * Get current request path
     */
    private function getCurrentPath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Remove base path
        if (str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }
        
        return $this->normalizePath($uri);
    }
    
    /**
     * Send 404 response
     */
    private function sendNotFound(): void
    {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Route not found'
        ]);
    }
    
    /**
     * Send CORS headers
     */
    private function sendCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    }
    
    /**
     * Set base path
     */
    public function setBasePath(string $path): self
    {
        $this->basePath = $path;
        return $this;
    }
    
    /**
     * Get all registered routes (for debugging)
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
