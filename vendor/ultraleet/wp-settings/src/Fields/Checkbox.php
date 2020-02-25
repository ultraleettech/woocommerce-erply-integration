<?php

namespace Ultraleet\WP\Settings\Fields;

class Checkbox extends AbstractField
{
    /**
     * Get the template name to load when rendering this field.
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'checkbox';
    }

    /**
     * @return string
     */
    protected function valueType()
    {
        return 'bool';
    }

    /**
     * @return array
     */
    protected function getRenderParams(): array
    {
        return array_merge(parent::getRenderParams(), [
            'description' => $this->config['description'] ?? '',
        ]);
    }
}
