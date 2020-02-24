<?php

namespace Ultraleet\WP\Settings\Components;

use Ultraleet\WP\Settings\Renderer;
use Ultraleet\WP\Settings\Fields\AbstractField;

/**
 * Class Section
 *
 * Represents a section of settings on an options page.
 */
class Section extends AbstractComponent
{
    protected $id;
    protected $config;
    protected $optionName;
    protected $settings;

    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * @var AbstractField[]
     */
    protected $fields = [];

    /**
     * Section constructor.
     *
     * @param string $id
     * @param array $config
     * @param string $prefix
     * @param Renderer $renderer
     */
    public function __construct(string $id, array $config, string $prefix, Renderer $renderer)
    {
        $this->id = $id;
        $this->config = $config;
        $this->optionName = "{$prefix}_$id";
        $this->renderer = $renderer;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $content = [];
        foreach ($this->getFields() as $id => $field) {
            $content[$id] = $field->render();
        }
        return $this->renderer->render('section', ['fieldContent' => $content]);
    }

    /**
     * @return AbstractField[]
     */
    public function getFields()
    {
        foreach (array_keys($this->config['fields']) as $id) {
            $this->getField($id);
        }
        return $this->fields;
    }

    /**
     * Retrieve a settings field instance.
     *
     * @param string $id
     * @return AbstractField
     */
    public function getField(string $id): AbstractField
    {
        if (! isset($this->fields[$id])) {
            $settings = $this->getSettings();
            $config = $this->config['fields'][$id];
            $config['type'] = $config['type'] ?? 'text';
            $className = str_replace('_', '', ucwords($config['type'], '_'));
            $class = str_replace('AbstractField', $className, AbstractField::class);
            /** @var AbstractField $field */
            $field = new $class($id, $config, $this->optionName, $this->renderer);
            if ($field->hasValue()) {
                $field->setValue($settings[$id] ?? $field->getDefaultValue());
            }
            $this->fields[$id] = $field;
        }
        return $this->fields[$id];
    }

    /**
     * Get setting values from the database.
     *
     * @return array
     */
    public function getSettings(): array
    {
        if (! isset($this->settings)) {
            $this->settings = get_option($this->optionName, []);
        }
        return $this->settings;
    }

    /**
     * Save settings
     */
    public function saveSettings()
    {
        $oldSettings = $this->getSettings();
        $optionName = $this->optionName;
        if (isset($_POST[$optionName])) {
            $newSettings = $_POST[$optionName];
            update_option($optionName, $newSettings, false);
            if (isset($this->config['onSave'])) {
                is_callable($this->config['onSave']) ? call_user_func(
                    $this->config['onSave'],
                    $oldSettings,
                    $newSettings,
                    $optionName
                ) : do_action(
                    $this->config['onSave'],
                    $oldSettings,
                    $newSettings,
                    $optionName
                );
            }
        }
    }

    /**
     * @return string
     */
    public function getOptionName(): string
    {
        return $this->optionName;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
