<?php

namespace Ultraleet\WP\Assets;

/**
 * Ultraleet assets manager.
 *
 * @package ultraleet/wp
 */
class Manager
{
    public function __construct()
    {
        $prefix = is_admin() ? 'admin' : 'wp';
        $hook = "{$prefix}_enqueue_scripts";
        add_action($hook, [$this, 'boot'], ULTRALEET_EARLIEST_PRIORITY);
        add_action($hook, [$this, 'init']);
        add_action("{$prefix}_print_footer_scripts", [$this, 'loadRequireJs'], ULTRALEET_LATEST_PRIORITY);
    }

    /**
     * This will be called in one of the *_enqueue_scripts actions if asset management is enabled, using earliest priority.
     */
    public function boot()
    {
        wp_register_script(
            'ulwp-bootstrap',
            $this->getAssetUrl('js/bootstrap.js'),
            [],
            $this->getAssetVersion(),
            false
        );
        wp_enqueue_script('ulwp-bootstrap');
    }

    /**
     * This will be called in one of the *_enqueue_scripts actions if asset management is enabled, using normal priority.
     */
    public function init()
    {
    }

    public function loadRequireJs()
    {
        $min = (defined('WP_DEBUG') && WP_DEBUG) ? '' : '.min';
        $url = $this->getAssetUrl("plugins/require.js/require$min.js");
        $baseUrl = $this->getAssetUrl('js');
        echo "<script type='text/javascript' src='$url'></script>\n";
        echo "<script type='text/javascript'>\n";

echo <<<JS
    require.config({
        baseUrl: '$baseUrl',
        paths: {
        },
        waitSeconds: 15
    });
    if (typeof jQuery === 'function') {
        define('jquery', function () { return jQuery; });
    }
    for (var i = 0; i < window.queueForRequire.length; i++) {
        require(window.queueForRequire[i].deps, window.queueForRequire[i].callback);
    }
JS;

        echo "</script>\n";
    }

    protected function getAssetUrl(string $relativePath): string
    {
        return trailingslashit(ULTRALEET_WP_ASSETS_URL) . ltrim($relativePath, '/');
    }

    protected function getAssetVersion(): string
    {
        return ULTRALEET_WP_VERSION . (defined('WP_DEBUG') && WP_DEBUG ? '-' . time() : '');
    }
}
