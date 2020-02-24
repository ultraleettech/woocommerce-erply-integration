<?php

namespace Ultraleet\WcErply\Components;

use Slim\Views\PhpRenderer;

class ViewRenderer
{
    private $renderer;

    public function __construct($templatePath)
    {
        $this->renderer = new PhpRenderer($templatePath);
    }

    public function render($template, $data = [])
    {
        return $this->renderer->fetch($template . '.php', $data);
    }
}
