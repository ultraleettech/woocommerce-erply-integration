<?php

namespace Ultraleet\WP\Settings\Helpers;

class Template
{
    /**
     * Converts attribute array into HTML tag attributes.
     *
     * @param array $attributes Array of key and value pairs.
     * @return string
     */
    public static function attributes(array $attributes): string
    {
        $htmlAttributes = [];
        foreach ($attributes as $attributeName => $attributeValue) {
            $attributeValue = esc_attr($attributeValue);
            $htmlAttributes[] = "$attributeName=\"$attributeValue\"";
        }
        return implode(' ', $htmlAttributes);
    }
}
