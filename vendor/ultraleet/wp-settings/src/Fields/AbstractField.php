<?php

namespace Ultraleet\WP\Settings\Fields;

use Ultraleet\WP\Settings\Renderer;
use Ultraleet\WP\Settings\Exceptions\NoValueException;
use Ultraleet\WP\Settings\Traits\SupportsOptionalCallbacksAndFilters;

/**
 * Option field base class.
 */
abstract class AbstractField
{
    use SupportsOptionalCallbacksAndFilters;

    protected $id;
    protected $config;
    protected $prefix;
    protected $name;
    protected $value;

    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * AbstractField constructor.
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
        $this->prefix = $prefix;
        $this->name = "{$prefix}[$id]";
        $this->renderer = $renderer;
    }

    /**
     * Get the template name to load when rendering this field.
     *
     * @return string
     */
    abstract protected function getTemplateName(): string;

    /**
     * Override for fields that have different value types (such as array for multiple choice fields).
     *
     * @return string
     */
    protected function valueType()
    {
        return 'string';
    }

    /**
     * Override for field types that need a different default value than an empty string/array/object.
     *
     * @return mixed
     */
    protected function default()
    {
        $default = null;
        settype($default, $this->valueType());
        return $default;
    }

    /**
     * Override and return false for valueless fields (such as section headings).
     *
     * @return bool
     */
    public function hasValue(): bool
    {
        return true;
    }

    /**
     * Set parameters to send to the render function.
     *
     * Adds as much available data from configuration as possible.
     * Field classes should extend this and add their own parameters.
     *
     * @return array
     */
    protected function getRenderParams(): array
    {
        $params = [];
        if ($this->hasValue()) {
            $params['label'] = $this->config['label'] ?? str_replace('_', ' ', ucfirst($this->id));
            $params['name'] = $this->name;
            $params['attributes'] = [
                'id' => $this->config['id'] ?? str_replace('_', '-', "{$this->prefix}-{$this->id}"),
                'class' => "setting_{$this->config['type']} {$this->prefix}_{$this->id}",
            ];
            $params['value'] = $this->getValue();
        }
        return $params;
    }

    /**
     * @return array
     */
    public function getAssetsConfig(): array
    {
        return [];
    }

    /**
     * Renders the field and returns the HTML string.
     *
     * @return string
     */
    final public function render(): string
    {
        $templateName = $this->getTemplateName();
        return $this->renderer->render("fields/$templateName", $this->getRenderParams());
    }

    /**
     * @return mixed|string
     */
    public function getValue()
    {
        if (! $this->hasValue()) {
            $name = str_replace('_', ' ', ucfirst($this->config['type']));
            throw new NoValueException("$name field does not support values.");
        }
        return $this->value ?? $this->getDefaultValue();
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        if (! isset($this->config['default'])) {
            return $this->default();
        }
        return static::filterIfCallbackOrFilter($this->config['default'], $this->valueType());
    }

    /**
     * @param $value
     */
    public function setValue($value)
    {
        if (! $this->hasValue()) {
            $name = str_replace('_', ' ', ucfirst($this->config['type']));
            throw new NoValueException("$name field does not support values.");
        }
        settype($value, $this->valueType());
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->config['type'];
    }
}
