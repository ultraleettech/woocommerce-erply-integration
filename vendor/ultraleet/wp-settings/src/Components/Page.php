<?php

namespace Ultraleet\WP\Settings\Components;

use Ultraleet\WP\Settings\Renderer;
use Ultraleet\WP\Settings\Settings;
use Ultraleet\WP\Settings\Exceptions\MissingArgumentException;
use Ultraleet\WP\Settings\Traits\SupportsOptionalCallbacksAndFilters;

/**
 * Class Page
 *
 * Represent a page of settings (divided into sections).
 *
 * @todo Refactor: extract asset management into separate class.
 */
class Page extends AbstractComponent
{
    use SupportsOptionalCallbacksAndFilters;

    protected $id;
    protected $title;
    protected $config;
    protected $prefix;

    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * @var Settings
     */
    protected $api;

    /**
     * @var Section[]
     */
    protected $sections;

    /**
     * Page constructor.
     *
     * @param string $id
     * @param array $config
     * @param string $prefix
     * @param Renderer $renderer
     * @param Settings $api
     */
    public function __construct(string $id, array $config, string $prefix, Renderer $renderer, Settings $api)
    {
        $this->id = $id;
        $this->title = $config['title'];
        $this->config = $config;
        $this->prefix = "{$prefix}_$id";
        $this->renderer = $renderer;
        $this->api = $api;
    }

    /**
     * Register configured page assets as well as specific field assets configured on the page..
     */
    public function registerAllAssets()
    {
        if (!empty($this->config['assets'])) {
            $this->registerAssets($this->config['assets']);
        }
        $this->registerFieldAssets();
    }

    /**
     * Register assets required by field types used on the page.
     */
    protected function registerFieldAssets()
    {
        $config = [];
        foreach ($this->getSections() as $section) {
            foreach ($section->getFields() as $field) {
                $type = $field->getType();
                if (! array_key_exists($type, $config) && ! empty($field->getAssetsConfig())) {
                    $config[$type] = $field->getAssetsConfig();
                }
            }
        }
        foreach ($config as $type => $assets) {
            if (isset($assets['json'])) {
                $assets['json']['handle'] = 'ulwp-settings-field';
                $assets['json']['format'] = "ULWP.settings.addField('$type', %s)";
            }
            $this->registerAssets($assets);
        }
    }

    /**
     * Register assets from specified configuration.
     *
     * @param array $assets
     */
    protected function registerAssets(array $assets)
    {
        if (!empty($assets['styles'])) {
            $this->enqueueStyles($assets['styles']);
        }
        if (!empty($assets['scripts'])) {
            $this->enqueueScripts($assets['scripts']);
        }
        if (isset($assets['inline'])) {
            $this->enqueueInlineScript($assets['inline']);
        }
        if (isset($assets['json'])) {
            $this->enqueueJsonScript($assets['json']);
        }
    }

    /**
     * Enqueues stylesheets configured for this page.
     *
     * @param array $styles
     */
    protected function enqueueStyles(array $styles)
    {
        $defaultConfig = [
            'dependencies' => [],
            'media' => 'screen',
        ];
        foreach ($styles as $handle => $config) {
            $config = array_merge($defaultConfig, $config);
            wp_enqueue_style(
                $handle,
                $config['path'],
                $config['dependencies'],
                ULTRALEET_WP_SETTINGS_ASSETS_VERSION,
                $config['media']
            );
        }
    }

    /**
     * Enqueues scripts configured for this page.
     *
     * @param array $scripts
     */
    protected function enqueueScripts(array $scripts)
    {
        $defaultConfig = [
            'dependencies' => [],
            'inFooter' => false,
        ];
        foreach ($scripts as $handle => $config) {
            $config = array_merge($defaultConfig, $config);
            wp_enqueue_script(
                $handle,
                $config['path'],
                $config['dependencies'],
                ULTRALEET_WP_SETTINGS_ASSETS_VERSION,
                $config['inFooter']
            );
        }
    }

    /**
     * @param $config
     */
    protected function enqueueInlineScript($config): void
    {
        $defaults = [
            'handle' => 'jquery',
            'script' => '',
            'position' => 'after',
        ];
        $config = array_merge($defaults, $config);
        $script = 'jQuery(function($){' . $config['script'] . '});';
        wp_add_inline_script($config['handle'], $script, $config['position']);
    }

    /**
     * @param $config
     */
    protected function enqueueJsonScript($config): void
    {
        $defaults = [
            'position' => 'after',
        ];
        $config = array_merge($defaults, $config);
        if (empty($config['handle'])) {
            throw new MissingArgumentException(
                "JSON configuration for page '{$this->id}' is missing a 'handle' argument."
            );
        }
        $data = $this->printJsonScript($config['format'], $config['data']);
        wp_add_inline_script($config['handle'], $data, $config['position']);
    }

    /**
     * Render JSON configuration used by scripts on this page.
     *
     * @param string $format
     * @param $data
     * @return string
     */
    protected function printJsonScript(string $format, $data)
    {
        return sprintf($format, $this->filterJsonData($data));
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function filterJsonData($data)
    {
        return json_encode(self::filterIfCallbackOrFilter($data));
    }

    /**
     * Renders the settings page.
     *
     * @return string
     */
    public function render(): string
    {
        $content = [];
        foreach ($this->getSections() as $id => $section) {
            $content[$id] = $section->render();
        }
        return $this->renderer->render('page', [
            'title' => $this->title,
            'pages' => $this->api->getPages(),
            'currentPageId' => $this->id,
            'sectionContent' => $content,
        ]);
    }

    /**
     * @param string $id
     * @return Section|null
     */
    public function getSection(string $id)
    {
        $sections = $this->getSections();
        return $sections[$id] ?? null;
    }

    /**
     * @return Section[]
     */
    public function getSections()
    {
        if (!isset($this->sections)) {
            $this->sections = [];
            foreach ($this->config['sections'] as $id => $config) {
                if ($this->isSectionEnabled($id)) {
                    $this->sections[$id] = new Section($id, $config, $this->prefix, $this->renderer);
                }
            }
        }
        return $this->sections;
    }

    /**
     * @param string $section
     * @return bool
     */
    protected function isSectionEnabled(string $section): bool
    {
        $value = $this->config['sections'][$section]['enabled'] ?? true;
        if (is_bool($value)) {
            return $value;
        } elseif (is_callable($value)) {
            return (bool) call_user_func($value);
        } elseif (is_string($value) && has_filter($value)) {
            return (bool) apply_filters($value, true);
        }
        return (bool) $value;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }
}
