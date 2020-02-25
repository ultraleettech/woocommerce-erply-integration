<?php

namespace Ultraleet\WcErply\Components\Router;

use http\Exception as httpException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Ultraleet\WcErply\Components\AbstractComponent;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Ultraleet\WcErply\Controllers\AbstractController;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;

class Router extends AbstractComponent
{
    protected static $controllers = [];

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var UrlGenerator
     */
    protected $generator;

    /**
     * Initialize router.
     *
     * @throws AnnotationException
     */
    public function __construct()
    {
        $this->autoloadAnnotations();
        $this->loadRoutes();
    }

    /**
     * Make sure annotation classes are autoloaded.
     *
     * @todo Better solution, need to avoid using deprecated functionality.
     */
    protected function autoloadAnnotations()
    {
        $loader = include ULTRALEET_WCERPLY_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        AnnotationRegistry::registerLoader([$loader, 'loadClass']);
    }

    /**
     * @throws AnnotationException
     */
    protected function loadRoutes()
    {
        $path = ULTRALEET_WCERPLY_SRC_PATH . 'Controllers' . DIRECTORY_SEPARATOR;
        $loader = new AnnotationDirectoryLoader(
            new FileLocator($path),
            new AnnotatedRouteControllerLoader(
                new AnnotationReader()
            )
        );
        $this->routes = $loader->load($path);
        $this->routes->addPrefix('wcerply');
    }

    /**
     * Request context getter.
     *
     * @return RequestContext
     */
    protected function getContext(): RequestContext
    {
        if (!isset($this->context)) {
            $this->context = new RequestContext();
        }
        return $this->context;
    }

    /**
     * Generate path from a given route (with optional parameters added).
     *
     * @param $route
     * @param array $params
     * @return string
     */
    public function generatePath($route, $params = [])
    {
        $generator = $this->getGenerator();
        $path = trim($generator->generate($route, $params, UrlGenerator::ABSOLUTE_PATH), '/');
        return str_replace('/', '-', $path);
    }

    /**
     * Url generator getter.
     *
     * @return UrlGenerator
     */
    protected function getGenerator(): UrlGenerator
    {
        if (!isset($this->generator)) {
            $this->generator = new UrlGenerator($this->routes, $this->getContext());
        }
        return $this->generator;
    }

    /**
     * Resolve current path in 'page' query variable and echo the rendered result.
     *
     * @todo More abstract request/result handling.
     */
    public function route()
    {
        try {
            $path = $this->getPath();
            $matcher = new UrlMatcher($this->routes, $this->getContext());
            $result = $matcher->match($path);

            $bits = explode('::', $result['_controller']);
            [$controller, $action] = [$this->getController($bits[0]), $bits[1]];

            $result = call_user_func_array(
                [$controller, $action],
                array_filter(
                    $result,
                    function ($value, $key) {
                        return $key[0] !== '_';
                    },
                    ARRAY_FILTER_USE_BOTH
                )
            );
        } catch (httpException $e) {
            if (defined('DOING_AJAX')) {
                wp_send_json(
                    [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ],
                    500
                );
            } else {
                throw $e;
            }
        } catch (\Throwable $e) {
            if (defined('DOING_AJAX')) {
                wp_send_json(
                    [
                        'status' => 'error',
                        'code' => $e->getCode(),
                        'message' => $e->getMessage(),
                        'debug' => (string) $e,
                    ],
                    $e->getCode()
                );
            } else {
                throw $e;
            }
        }
        echo $result;
    }

    /**
     * Try to fetch route path from the request environment.
     *
     * @param bool $raw Whether to return as a raw request param or convert to actual route.
     * @return string
     */
    protected function getPath(bool $raw = false)
    {
        if (defined('DOING_AJAX')) {
            $path = filter_input(INPUT_GET, 'action');
        } elseif (isset($_GET['page'])) {
            $path = filter_input(INPUT_GET, 'page');
        } else {
            throw new \RuntimeException('Trying to resolve a route but no path scenario was found!');
        }
        return $raw ? $path : '/' . str_replace('-', '/', $path);
    }

    /**
     * Controller getter.
     *
     * @param $class
     * @return AbstractController
     */
    protected function getController($class): AbstractController
    {
        if (!isset(static::$controllers[$class])) {
            static::$controllers[$class] = new $class;
        }
        return static::$controllers[$class];
    }

    /**
     * Determine whether current route matches given route.
     *
     * @param string $route
     * @return bool
     */
    public function isRoute(string $route): bool
    {
        try {
            $path = $this->getPath(true);
            return $path == $route || $path == $this->generatePath($route);
        } catch (\RuntimeException $exception) {
            return false;
        }
    }
}
