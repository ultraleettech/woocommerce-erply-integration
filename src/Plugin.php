<?php

namespace Ultraleet\WcErply;

use DI\Container;
use Monolog\Logger;
use DI\ContainerBuilder;
use Ultraleet\WP\WP as ULWP;
use Ultraleet\WcErply\Components\WP;
use Ultraleet\WP\Scheduler\Scheduler;
use Psr\Container\ContainerInterface;
use Ultraleet\WcErply\Components\Cron;
use Ultraleet\WcErply\Components\ErplyApi;
use Ultraleet\WcErply\Components\Settings;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Ultraleet\WcErply\Components\ViewRenderer;
use Ultraleet\WcErply\Components\Router\Router;
use Ultraleet\WcErply\Components\Synchronization;

/**
 * Class Plugin
 *
 * @package Ultraleet\WcErply
 *
 * @property ULWP $ulwp
 * @property ErplyApi $api
 * @property Synchronization $sync
 * @property ViewRenderer $view
 * @property Logger $logger
 * @property WP $wp
 * @property Cron $cron
 * @property Router $router
 * @property Scheduler $scheduler
 * @property Settings $settings
 */
class Plugin implements ContainerInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * Plugin constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->configure();
    }

    /**
     * @throws \Exception
     */
    private function configure()
    {
        $container = new ContainerBuilder();
        $container->addDefinitions(ULTRALEET_WCERPLY_PATH . 'config/plugin.php');

        if (! WP_DEBUG) {
            $container->enableCompilation(ULTRALEET_WCERPLY_VAR_PATH . 'cache');
        }

        $this->container = $container->build();
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            return null;
        }

        return $this->container->get($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * @param string $id
     * @param $definition
     */
    public function set(string $id, $definition)
    {
        $this->container->set($id, $definition);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function __get($name)
    {
        if ($component = $this->get($name)) {
            return $component;
        }

        throw new \Exception("Property '{$name}' not found in WcErply plugin instance");
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }
}
