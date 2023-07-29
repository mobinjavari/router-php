<?php

/**
 * @author https://github.com/mobinjavari
 * @info The PHP Simple Router Class
 * @version 1.0
 * @license GPL-3
 */

class Router
{
    /**
     * @var string
     */
    private static string $requestedMethod = '';

    /**
     * @var string
     */
    private static string $requestedUri = '';

    /**
     * @var string
     */
    private static string $serverBasePath = '';

    /**
     * @var array
     */
    protected static array $routes = [];

    /**
     * @param string $pattern
     * @param closure $function
     * @param array $methods
     * @return void
     */
    protected static function addRoute(string $pattern, closure $function, array $methods = ['GET'])
    {
        foreach ($methods as $method) {
            $method = strtoupper($method);
            self::$routes[$method][$pattern] = $function;
        }
    }

    /**
     * @return array
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * @return mixed
     */
    public static function getRequestMethod(): mixed
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return string
     */
    public static function getCurrentUri(): string
    {
        $uri = substr(rawurldecode($_SERVER['REQUEST_URI']), strlen(self::$serverBasePath));

        if (str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return '/' . trim($uri, '/');
    }

    /**
     * @return string
     */
    public static function getBasePath()
    {
        return self::$serverBasePath;
    }

    /**
     * @return void
     */
    public static function setBasePath(string $serverBasePath)
    {
        self::$serverBasePath = $serverBasePath;
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public static function get(string $pattern, closure $function)
    {
        self::addRoute($pattern, $function, ['GET']);
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public static function post(string $pattern, closure $function)
    {
        self::addRoute($pattern, $function, ['POST']);
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public static function put(string $pattern, closure $function)
    {
        self::addRoute($pattern, $function, ['PUT']);
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public static function patch(string $pattern, closure $function)
    {
        self::addRoute($pattern, $function, ['PATCH']);
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public static function delete(string $pattern, closure $function)
    {
        self::addRoute($pattern, $function, ['DELETE']);
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public static function options(string $pattern, closure $function)
    {
        self::addRoute($pattern, $function, ['OPTIONS']);
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public static function any(string $pattern, closure $function)
    {
        self::addRoute($pattern, $function, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']);
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @param array $methods
     * @return void
     */
    public static function custom(string $pattern, closure $function, array $methods = ['GET'])
    {
        self::addRoute($pattern, $function, $methods);
    }

    /**
     * @param string $baseRoute
     * @param closure $function
     * @return void
     */
    public static function mountPath(string $baseRoute, closure $function)
    {
        $currentBaseRoute = self::$serverBasePath;

        self::$serverBasePath .= $baseRoute;

        call_user_func($function);

        self::$serverBasePath = $currentBaseRoute;
    }

    /**
     * @throws Exception
     */
    public static function matchRoute(closure $functionError): void
    {
        self::$requestedMethod = self::getRequestMethod();
        self::$requestedUri = self::getCurrentUri();

        if (isset(self::$routes[self::$requestedMethod])) {
            foreach (self::$routes[self::$requestedMethod] as $routeUrl => $function) {
                // Use named sub patterns in the regular expression pattern to capture each parameter value separately
                $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $routeUrl);
                if (preg_match('#^' . $pattern . '$#', self::$requestedUri, $matches)) {
                    // Pass the captured parameter values as named arguments to the target function
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY); // Only keep named subpattern matches
                    call_user_func_array($function, $params);
                    return ;
                }
            }
        }

        call_user_func($functionError);
    }
}