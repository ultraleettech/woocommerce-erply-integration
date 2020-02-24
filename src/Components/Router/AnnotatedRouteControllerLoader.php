<?php

namespace Ultraleet\WcErply\Components\Router;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;

class AnnotatedRouteControllerLoader extends AnnotationClassLoader
{
    /**
     * Configures the _controller default parameter of a given Route instance.
     *
     * @param mixed $annot The annotation class instance
     */
    protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, $annot)
    {
        if ('__invoke' === $method->getName()) {
            $route->setDefault('_controller', $class->getName());
        } else {
            $route->setDefault('_controller', $class->getName().'::'.$method->getName());
        }
    }
}
