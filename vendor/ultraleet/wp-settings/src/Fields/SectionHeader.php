<?php

namespace Ultraleet\WP\Settings\Fields;

class SectionHeader extends AbstractField
{
    /**
     * This is not a value field.
     *
     * @return bool
     */
    public function hasValue(): bool
    {
        return false;
    }

    /**
     * Get the template name to load when rendering this field.
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'section-header';
    }

    /**
     * @return array
     */
    protected function getRenderParams(): array
    {
        $params = parent::getRenderParams();
        $params['title'] = $this->config['title'] ?? false;
        $params['text'] = $this->config['text'] ?? false;
        return $params;
    }
}
