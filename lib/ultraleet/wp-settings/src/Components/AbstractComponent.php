<?php

namespace Ultraleet\WP\Settings\Components;

abstract class AbstractComponent
{
    abstract public function render(): string;

    public function __get(string $name)
    {
        $method = 'get' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new \Error(sprintf('Method %s not found in class %s!', $method, __CLASS__));
        }
        return $method();
    }

    public function __set(string $name, $value)
    {
        $method = 'set' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new \Error(sprintf('Method %s not found in class %s!', $method, __CLASS__));
        }
        $method($value);
    }
}
