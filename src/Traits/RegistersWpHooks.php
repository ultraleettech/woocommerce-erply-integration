<?php

namespace Ultraleet\WcErply\Traits;

trait RegistersWpHooks
{
    /**
     * Register an action/filter hook.
     *
     * @param string $name Action/filter name to register
     * @param string $type 'action'|'filter'
     * @param null $method Method name in this class. Defaults to $name
     */
    private function registerHook(string $name, string $type = 'action', $method = null, $priority = 10, $args = 1)
    {
        $function = 'add_' . $type;
        $function($name, [$this, $method ?? $name], $priority, $args);
    }
}
