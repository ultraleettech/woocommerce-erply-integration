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
        $page = $_GET['tab'] ?? '';
        return $settings = $this->container->settings->renderPage($page);
    }
}
