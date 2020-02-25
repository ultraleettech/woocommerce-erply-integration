<?php

namespace Ultraleet\WP\Settings;

use Ultraleet\WP\Settings\Components\Page;
use Ultraleet\WP\Settings\Exceptions\MissingArgumentException;

/**
 * Ultraleet Wordpress settings API library main class.
 *
 * @package ultraleet/wp-settings
 */
class Settings
{
    protected $pluginBaseFile;
    protected $prefix;
    protected $assetsPath;
    protected $styleDependencies = [];
    protected $scriptDependencies = [];
    protected $isSettingsPageCallback;
    protected $jsonFormat;
    protected $initialConfig;
    protected $config;
    protected $options = [];

    /** @var Renderer */
    protected $renderer;

    /**
     * @var Page[]
     */
    private $pages;

    /**
     * Library constructor.
     *
     * @param string $prefix Unique identifier to prepend to option names. Usually derived from plugin name.
     * @param array $config Configuration array for all pages, sections, and individual fields.
     * @param array $args {
     *      @type string $pluginBaseFile The full path to the main plugin file.
     *      @type string $assetsPath Url path of the assets file, relative to which included assets on settings pages are located.
     *      @type array|string $styleDependencies Dependencies all style assets depend upon.
     *      @type array|string $scriptDependencies Dependencies all script assets depend upon.
     *      @type string $jsonFormat String format (containing %s for JSON data) for adding config to a settings page.
     *      @type callable $isSettingsPage Callback for determining whether we are on a settings page.
     * }
     */
    public function __construct(string $prefix, array $config, array $args)
    {
        $this->includes();

        $this->prefix = "{$prefix}_settings";
        $this->initialConfig = $config;
        $args = array_merge([
            'assetsPath' => '',
            'styleDependencies' => [],
            'scriptDependencies' => [],
            'jsonFormat' => $this->getDefaultJsonFormat(),
            'isSettingsPage' => '__return_false',
        ], $args);
        if (empty($args['pluginBaseFile'])) {
            throw new MissingArgumentException("Argument 'pluginBaseFile' is required.");
        }
        $this->pluginBaseFile = $args['pluginBaseFile'];
        $this->assetsPath = trailingslashit($args['assetsPath']);
        $this->styleDependencies = is_string($args['styleDependencies']) ? [$args['styleDependencies']] : $args['styleDependencies'];
        $this->scriptDependencies = is_string($args['scriptDependencies']) ? [$args['scriptDependencies']] : $args['scriptDependencies'];
        $this->isSettingsPageCallback = $args['isSettingsPage'];
        $this->jsonFormat = $args['jsonFormat'];

        if (is_admin()) {
            if ($this->isSettingsPage()) {
                add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets'], 100);
                add_action('admin_notices', [$this, 'adminNotices']);
            }
            add_action('wp_loaded', [$this, 'savePage']);
        }
    }

    /**
     * Load required files.
     */
    protected function includes()
    {
        $dir = dirname(__DIR__) . '/includes/';
        require_once $dir . 'constants.php';
    }

    /**
     * Get filtered configuration array.
     *
     * @return array
     */
    public function getConfig(): array
    {
        if (! isset($this->config)) {
            /**
             * Make sure initial configuration is available to the following filter hooks.
             */
            $this->config = $this->initialConfig;

            /**
             * Filters the config array so more settings and pages can be added dynamically.
             *
             * @param array Configuration array. Initially the configuration passed to settings API class constructor.
             */
            $this->config = apply_filters('ultraleet_wp_settings_config', $this->config);
        }
        return $this->config;
    }

    /**
     * Action: admin_enqueue_scripts
     *
     * @todo Add ULWP as a dependency when that library has been extracted.
     */
    public function enqueueAdminAssets()
    {
        wp_register_script(
            'ulwp-settings',
            ULTRALEET_WP_SETTINGS_ASSETS_URL . 'js/settings.js',
            [],
            ULTRALEET_WP_SETTINGS_ASSETS_VERSION
        );
        wp_enqueue_script('ulwp-settings');
        wp_register_script(
            'ulwp-settings-field',
            ULTRALEET_WP_SETTINGS_ASSETS_URL . 'js/field.js',
            ['ulwp-settings'],
            ULTRALEET_WP_SETTINGS_ASSETS_VERSION
        );
        wp_enqueue_script('ulwp-settings-field');

        foreach ($this->getPages() as $id => $page) {
            if ($this->isCurrentPage($id)) {
                $page->registerAllAssets();
            }
        }
    }

    /**
     * Return the value of a specified setting.
     *
     * @param string $field
     * @param string $section
     * @param string $page
     * @return mixed|string
     */
    public function getSettingValue(string $field, string $section, string $page = '')
    {
        $page = $this->getPageIndex($page);
        $optionName = $this->getOptionName($page, $section);
        $option = $this->getOption($optionName);
        return $option[$field] ?? $this->getPage($page)->getSection($section)->getField($field)->getDefaultValue();
    }

    /**
     * Fetch the settings section value.
     *
     * @param string $name
     * @return mixed
     */
    protected function getOption(string $name)
    {
        if (!isset($this->options[$name])) {
            $this->options[$name] = get_option($name, []);
        }
        return $this->options[$name];
    }

    /**
     * Return the index of the first page in config in case page is not specified.
     *
     * @param string $page
     * @return string
     */
    protected function getPageIndex(string $page = ''): string
    {
        return $page ?: current(array_keys($this->getConfig()));
    }

    /**
     * Render settings page.
     *
     * @param string $pageId
     * @return string
     */
    public function renderPage(string $pageId)
    {
        $renderer = $this->getRenderer();
        return $renderer->render('settings', [
            'content' => $this->getPage($pageId)->render(),
        ]);
    }

    /**
     * Get view renderer.
     *
     * @return Renderer
     */
    protected function getRenderer(): Renderer
    {
        if (!isset($this->renderer)) {
            $this->renderer = new Renderer(ULTRALEET_WP_SETTINGS_ASSETS_PATH . 'views' . DIRECTORY_SEPARATOR);
        }
        return $this->renderer;
    }

    /**
     * Get settings page objects.
     *
     * @return Page[]
     */
    public function getPages(): array
    {
        $pages = [];
        foreach ($this->getConfig() as $pageId => $config) {
            $pages[$pageId] = $this->pages[$pageId] ?? $this->getPage($pageId);
        }
        return $this->pages = $pages;
    }

    /**
     * @param string $pageId
     * @return Page
     */
    protected function getPage(string $pageId = '')
    {
        $pageId = $pageId ?: $this->getPageIndex();
        if (!isset($this->pages[$pageId])) {
            $config = $this->getConfig()[$pageId];
            if (! empty($config['assets'])) {
                $config['assets'] = $this->filterAssetsConfig($config['assets']);
            }
            $this->pages[$pageId] = new Page($pageId, $config, $this->prefix, $this->getRenderer(), $this);
        }
        return $this->pages[$pageId];
    }

    /**
     * Add plugin config to assets config.
     *
     * @param array $assets
     * @return array
     */
    protected function filterAssetsConfig(array $assets)
    {
        if (!empty($assets['styles'])) {
            $assets['styles'] = $this->filterStylesAndScriptsConfig($assets['styles'], $this->getStyleDependencies());
        }
        if (!empty($assets['scripts'])) {
            $assets['scripts'] = $this->filterStylesAndScriptsConfig($assets['scripts'], $this->getStyleDependencies());
        }
        if (isset($assets['json'])) {
            $assets['json']['format'] = $this->jsonFormat;
        }
        return $assets;
    }

    protected function filterStylesAndScriptsConfig(array $assets, array $dependencies): array
    {
        foreach ($assets as $handle => $config) {
            if (empty($assets[$handle]['path'])) {
                throw new MissingArgumentException("Asset path for '$handle' is required.");
            }
            $assets[$handle]['path'] = $this->filterAssetPath($assets[$handle]['path']);
            if (isset($config['dependencies'])) {
                $assets[$handle]['dependencies'] = $dependencies + $config['dependencies'];
            }
        }
        return $assets;
    }

    /**
     * @param string $path
     * @return string
     */
    protected function filterAssetPath(string $path = ''): string
    {
        return $this->assetsPath . $path;
    }

    /**
     * Add relevant admin notices.
     */
    public function adminNotices()
    {
        if (isset($_GET['updated'])) {
            $notice = __('Settings saved.');
            echo <<<HTML
<div class="notice notice-success is-dismissible">
    <p>
        <strong>$notice</strong>
    </p>
</div>
HTML;
        }
    }

    /**
     * Save all settings sections when a settings page form is submitted.
     */
    public function savePage()
    {
        if (! isset($_REQUEST['ultraleet_save_settings'])) {
            return;
        }
        $pageId = $this->getPageIndex($_GET['tab'] ?? '');
        check_admin_referer("save_settings_$pageId");
        foreach ($this->getPage($pageId)->getSections() as $sectionId => $section) {
            $section->saveSettings();
        }
        wp_safe_redirect(add_query_arg(['updated' => 1], $_SERVER['HTTP_REFERER']));
        exit;
    }

    /**
     * @param string $page
     * @param string $section
     * @return string
     */
    public function getOptionName(string $page, string $section): string
    {
        return $this->getPage($page)->getSection($section)->getOptionName();
    }

    /**
     * Determine whether we are currently on the specified settings page.
     *
     * Uses settings page callback provided on library construction.
     *
     * @param string $pageId
     * @return bool
     */
    protected function isCurrentPage(string $pageId)
    {
        if ($this->isSettingsPage()) {
            $currentPageId = $this->getPageIndex($_GET['tab'] ?? '');
            return $pageId == $currentPageId;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isSettingsPage(): bool
    {
        return call_user_func($this->isSettingsPageCallback);
    }

    /**
     * @return string
     */
    protected function getDefaultJsonFormat(): string
    {
        return "{$this->prefix}Settings = %s";
    }

    /**
     * @param string $assetsPath
     */
    public function setAssetsPath(string $assetsPath)
    {
        $this->assetsPath = $assetsPath;
    }

    /**
     * @return array
     */
    public function getStyleDependencies(): array
    {
        return $this->styleDependencies;
    }

    /**
     * Get core asset dependencies for settings pages.
     *
     * @return array
     */
    public function getScriptDependencies(): array
    {
        return $this->scriptDependencies;
    }

    /**
     * @return string
     */
    public function getJsonFormat()
    {
        return $this->jsonFormat;
    }

    /**
     * @return mixed
     */
    public function getPluginBaseFile()
    {
        return $this->pluginBaseFile;
    }
}
