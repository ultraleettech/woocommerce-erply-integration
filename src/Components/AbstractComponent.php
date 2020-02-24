<?php

namespace Ultraleet\WcErply\Components;

use Ultraleet\WcErply;
use Ultraleet\WcErply\Plugin;

/**
 * @mixin Plugin
 */
abstract class AbstractComponent
{
    public function __get($name)
    {
        return WcErply::$plugin->get($name);
    }

    public function __set($name, $value)
    {
        WcErply::$plugin->set($name, $value);
    }
}
