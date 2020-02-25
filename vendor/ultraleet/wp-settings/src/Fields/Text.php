<?php

namespace Ultraleet\WP\Settings\Fields;

class Text extends AbstractField
{
    /**
     * Get the template name to load when rendering this field.
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'input';
    }
}
