<?php

namespace Ultraleet\WP;

use Ultraleet\WP\Assets\Manager;

/**
 * Class WP
 *
 * Main class of the ULWP library.
 *
 * The plugin using it should instantiate it as soon as possible and pass its configuration to the constructor.
 *
 * @package ultraleet/wp
 */
class WP
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Manager
     */
    protected $assetsManager;

    /**
     * WP constructor.
     *
     * @param array $config {
     *      @type string $pluginDir Plugin root path.
     *      @type callable $includeAssets Callback to determine whether or not to include ULWP scripts and styles on the
     *            page. Typically this could be something as simple as 'is_admin' if your plugin only needs the admin
     *            area.
     * }
     */
    public function __construct(array $config = [])
    {
        $this->includes();
        $this->configure($config);

        add_action('plugins_loaded', [$this, 'boot'], ULTRALEET_EARLY_PRIORITY);
    }

    /**
     * Include static resources such as constants and functions.
     */
    protected function includes()
    {
        $dir = dirname(__DIR__) . '/includes/';
        require_once $dir . 'constants.php';
        require_once $dir . 'functions.php';
    }

    /**
     * Simple storage for now.
     *
     * @param array $config
     */
    protected function configure(array $config)
    {
        $defaults = [
            'pluginDir' => dirname(__FILE__, 5),
            'includeAssets' => '__return_true',
        ];
        $this->config = array_merge($defaults, $config);
    }

    /**
     * Boot up the library.
     *
     * This will be called right after all plugins have been loaded.
     */
    public function boot()
    {
        // do stuff we need to do this early

        // Most hooks will be registered on init.
        add_action('init', [$this, 'init'], ULTRALEET_EARLY_PRIORITY);
    }

    /**
     * Initialize hooks & perform other init tasks.
     */
    public function init()
    {
        if ($this->config['includeAssets']()) {
            $this->getAssetsManager();
        }
    }

    /**
     * @return Manager
     */
    public function getAssetsManager(): Manager
    {
        if (! isset($this->assetsManager)) {
            $this->assetsManager = new Manager();
        }
        return $this->assetsManager;
    }
}
