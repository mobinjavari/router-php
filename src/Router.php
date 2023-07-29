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
     * @var array
     */
    protected array $routes = [];

    /**
     * @var array|string[]
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
     * @param string $pattern
     * @param closure $function
     * @param string ...$methods
     * @return void
     */
    protected function addRoute(string $pattern, closure $function, string ...$methods)
    {
        $pattern = $this->serverBasePath . $pattern;

        foreach ($methods as $method) {
            $method = strtoupper($method);
            $this->routes[$method][$pattern] = $function;
        }
    }

    /**
     * @return array
     */
    protected function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @return mixed
     */
    protected function getRequestMethod(): mixed
    {
        return $_SERVER['REQUEST_METHOD'];
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
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public function get(string $pattern, closure $function)
    {
        $this->addRoute($pattern, $function, 'GET');
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public function post(string $pattern, closure $function)
    {
        $this->addRoute($pattern, $function, 'POST');
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public function put(string $pattern, closure $function)
    {
        $this->addRoute($pattern, $function, 'PUT');
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public function patch(string $pattern, closure $function)
    {
        $this->addRoute($pattern, $function, 'PATCH');
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public function delete(string $pattern, closure $function)
    {
        $this->addRoute($pattern, $function, 'DELETE');
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public function options(string $pattern, closure $function)
    {
        $this->addRoute($pattern, $function, 'OPTIONS');
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @return void
     */
    public function any(string $pattern, closure $function)
    {
        $this->addRoute($pattern, $function, 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');
    }

    /**
     * @param string $pattern
     * @param closure $function
     * @param string ...$methods
     * @return void
     */
    public function custom(string $pattern, closure $function, string ...$methods)
    {
        $this->addRoute($pattern, $function, ...$methods);
    }

    /**
     * @param string $baseRoute
     * @param closure $function
     * @return void
     */
    public function mountPath(string $baseRoute, closure $function)
    {
        if (str_starts_with($this->getCurrentUri(), $this->serverBasePath . $baseRoute)) {
            $currentBaseRoute = $this->serverBasePath;

            $this->serverBasePath .= $baseRoute;

            call_user_func($function);

            $this->serverBasePath = $currentBaseRoute;
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
     * @param closure|string $functionError
     * @return void
     */
    public function matchRoute(closure|string $functionError): void
    {
        $requestedMethod = $this->getRequestMethod();
        $requestedUri = $this->getCurrentUri();

        if (isset($this->routes[$requestedMethod])) {
            foreach ($this->routes[$requestedMethod] as $routeUrl => $function) {
                if (preg_match($this->compileRoute($routeUrl), $requestedUri, $matches)) {
                    // Pass the captured parameter values as named arguments to the target function
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY); // Only keep named subpattern matches
                    call_user_func_array($function, $params);
                    return;
                }
            }
        }

        if (is_string($functionError)) exit($functionError);
        else call_user_func($functionError);
    }
}
