<?php

namespace Ultraleet\WcErply\Controllers;

use Symfony\Component\Routing\Annotation\Route;

/**
 * Settings controller.
 */
class SettingsController extends AbstractController
{
    /**
     * @Route("settings", name="settings_page")
     */
    public function settings()
    {
        $page = filter_input(INPUT_GET, 'tab') ?? '';
        return $settings = $this->container->settings->renderPage($page);
    }
}
