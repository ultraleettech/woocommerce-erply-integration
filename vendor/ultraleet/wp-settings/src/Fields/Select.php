<?php

namespace Ultraleet\WP\Settings\Fields;

class Select extends AbstractField
{
    /**
     * Get the template name to load when rendering this field.
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'select';
    }

    /**
     * @inheritDoc
     */
    protected function getRenderParams(): array
    {
        $params = parent::getRenderParams();
        $params['options'] = self::filterIfCallbackOrFilter($this->config['options'], 'array');
        return $params;
    }
}
