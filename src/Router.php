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
    protected string $serverBasePath = '';

    /**
     * @var string
     */
    protected string $includeBasePath = __DIR__;

    /**
     * @var closure|string
     */
    protected closure|string $error = '';

    /**
     * @var array
     */
    protected array $routes = [];

    /**
     * @var array
     */
    protected array $matchTypes = [
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++'
    ];

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->serverBasePath;
    }

    /**
     * @return void
     */
    public function setBasePath(string $serverBasePath)
    {
        $this->serverBasePath = $serverBasePath;
    }

    /**
     * @return string
     */
    public function getIncludePath(): string
    {
        return $this->includeBasePath;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setIncludePath(string $path)
    {
        $this->includeBasePath = $path;
    }

    /**
     * @return closure|string
     */
    public function getError(): string|closure
    {
        return $this->error;
    }

    /**
     * @param closure|string $callback
     * @return void
     */
    public function setError(closure|string $callback)
    {
        $this->error = $callback;
    }

    /**
     * @param string $pattern
     * @param closure|string $callback
     * @param string ...$methods
     * @return void
     */
    protected function addRoute(string $pattern, closure|string $callback, string ...$methods)
    {
        $pattern = $this->serverBasePath . $pattern;

        foreach ($methods as $method) {
            $method = strtoupper($method);
            $this->routes[$method][$pattern] = $callback;
        }
    }

    /**
     * @param string $pattern
     * @param closure|string $callback
     * @return void
     */
    public function get(string $pattern, closure|string $callback)
    {
        $this->addRoute($pattern, $callback, 'GET');
    }

    /**
     * @param string $pattern
     * @param closure|string $callback
     * @return void
     */
    public function post(string $pattern, closure|string $callback)
    {
        $this->addRoute($pattern, $callback, 'POST');
    }

    /**
     * @param string $pattern
     * @param closure|string $callback
     * @return void
     */
    public function put(string $pattern, closure|string $callback)
    {
        $this->addRoute($pattern, $callback, 'PUT');
    }

    /**
     * @param string $pattern
     * @param closure|string $callback
     * @return void
     */
    public function patch(string $pattern, closure|string $callback)
    {
        $this->addRoute($pattern, $callback, 'PATCH');
    }

    /**
     * @param string $pattern
     * @param closure|string $callback
     * @return void
     */
    public function delete(string $pattern, closure|string $callback)
    {
        $this->addRoute($pattern, $callback, 'DELETE');
    }

    /**
     * @param string $pattern
     * @param closure|string $callback
     * @return void
     */
    public function options(string $pattern, closure|string $callback)
    {
        $this->addRoute($pattern, $callback, 'OPTIONS');
    }

    /**
     * @param string $pattern
     * @param closure|string $callback
     * @return void
     */
    public function any(string $pattern, closure|string $callback)
    {
        $this->addRoute($pattern, $callback, 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');
    }

    /**
     * @param string $pattern
     * @param closure|string $callback
     * @param string ...$methods
     * @return void
     */
    public function custom(string $pattern, closure|string $callback, string ...$methods)
    {
        $this->addRoute($pattern, $callback, ...$methods);
    }

    /**
     * @return array
     */
    protected function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @return string
     */
    protected function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return mixed
     */
    public function phpInput(): array
    {
        if ($contents = file_get_contents('php://input') ?? '')
            return json_decode($contents, true) ?? [];

        return [];
    }

    /**
     * @return string
     */
    protected function getCurrentUri(): string
    {
        $uri = htmlspecialchars(rawurldecode($_SERVER['REQUEST_URI']));

        if (str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return '/' . trim($uri, '/');
    }

    /**
     * @param closure|string $callback
     * @param array $parameters
     * @return void
     */
    protected function runCallback(closure|string $callback, array $parameters = [])
    {
        if (is_callable($callback)) {
            call_user_func_array($callback, $parameters);
        } elseif (file_exists($this->includeBasePath . $callback)) {
            foreach ($parameters as $name => $value) $$name = $value;
            include_once $this->includeBasePath . $callback;
        } else {
            exit("404 | Callback Not Found");
        }
    }

    /**
     * @param string $baseRoute
     * @param closure $function
     * @param array ...$methods
     * @return void
     */
    public function mountPath(string $baseRoute, closure $function, array $methods = ['ANY'])
    {
        foreach ($methods as $method) {
            if ($method == $this->getRequestMethod() || $method == 'ANY') {
                if (str_starts_with($this->getCurrentUri(), $this->serverBasePath . $baseRoute)) {
                    $currentBaseRoute = $this->serverBasePath;
                    $this->serverBasePath .= $baseRoute;

                    call_user_func($function);

                    $this->serverBasePath = $currentBaseRoute;
                }

                break;
            }
        }


    }

    /**
     * @param string $route
     * @return string
     */
    protected function compileRoute(string $route): string
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = $this->matchTypes;

            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                $optional = $optional !== '' ? '?' : null;

                //Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . ')'
                    . $optional
                    . ')'
                    . $optional;

                $route = str_replace($block, $pattern, $route);
            }
        }

        return "`^$route$`u";
    }


    /**
     * @return void
     */
    public function matchRoute(): void
    {
        $requestedMethod = $this->getRequestMethod();
        $requestedUri = $this->getCurrentUri();

        if (isset($this->routes[$requestedMethod])) {
            foreach ($this->routes[$requestedMethod] as $routeUrl => $callback) {
                if (preg_match($this->compileRoute($routeUrl), $requestedUri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $this->runCallback($callback, $params);
                    return;
                }
            }
        }

        $this->runCallback($this->error);
        exit;
    }
}
