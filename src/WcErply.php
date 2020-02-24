<?php

namespace Ultraleet;

use Exception;
use Ultraleet\WcErply\Plugin;

/**
 * Class WcErply
 *
 * Static proxy for container methods:
 * @method static get(string $name)
 * @method static has(string $name)
 * @method static set(string $name, $definition)
 */
class WcErply
{
    /**
     * @var Plugin
     */
    public static $plugin;

    /**
     * Get plugin version.
     *
     * @param bool $unique Useful for loading assets when in development mode.
     * @return string
     */
    public static function version(bool $unique = false): string
    {
        $version = ULTRALEET_WCERPLY_VERSION;
        if ($unique) {
            $version .= '-' . time();
        }
        return $version;
    }

    /**
     * Boot the plugin.
     *
     * @throws Exception
     */
    public static function boot(): Plugin
    {
        if (isset(self::$plugin)) {
            throw new Exception('WCERPLY plugin already initialized!');
        }
        self::$plugin = new Plugin;
        self::init();
        return self::$plugin;
    }

    /**
     * Initialize plugin components.
     */
    public static function init()
    {
        $plugin = self::$plugin;

        // Load ULWP
        $plugin->ulwp;

        // Setup WP integration
        $plugin->wp->boot();

        // Initialize scheduler
        $plugin->scheduler;
    }

    /**
     * Return plugin service container.
     *
     * @return Plugin
     */
    public static function plugin(): Plugin
    {
        return self::$plugin;
    }

    /**
     * Destroy the plugin.
     */
    public static function reset()
    {
        self::$plugin = null;
    }

    /**
     * Try to set PHP ini values for heavy processing.
     */
    public static function setPhpIniValues()
    {
        $date = date('Y-m-d');
        $values = [
            'max_execution_time' => 0,
            'error_log' => ULTRALEET_WCERPLY_VAR_PATH . "error-$date.log",
            'memory_limit' => -1,
        ];
        foreach ($values as $var => $value) {
            if (wp_is_ini_value_changeable($var)) {
                ini_set($var, $value);
            };
        }
    }

    /**
     * Mix in DI container methods.
     *
     * @param $name
     * @param $arguments
     */
    public static function __callStatic($name, $arguments)
    {
        call_user_func_array([self::$plugin, $name], $arguments);
    }
}
