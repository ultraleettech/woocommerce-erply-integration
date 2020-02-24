<?php

namespace Ultraleet\WcErply\Controllers;

use Ultraleet\WcErply;

abstract class AbstractController
{
    protected $container;
    protected $logger;

    public function __construct()
    {
        $this->container = WcErply::$plugin;
        $this->logger = $this->container->logger;
    }

    protected function render(string $template, array $data = []): string
    {
        return $this->container->view->render($template, $data);
    }
}
