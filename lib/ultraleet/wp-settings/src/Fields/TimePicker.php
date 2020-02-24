<?php

namespace Ultraleet\WP\Settings\Fields;

class TimePicker extends Text
{
    /**
     * @return array
     */
    public function getAssetsConfig(): array
    {
        $defaults = [
            'timeFormat' => 'HH:mm',
            'dropdown' => true,
            'dynamic' => false,
        ];
        $options = array_merge($defaults, $this->config['pluginOptions'] ?? []);
        return [
            'styles' => [
                'jquery-timepicker' => [
                    'path' => '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css',
                    'dependencies' => ['jquery-ui-style'],
                ],
            ],
            'scripts' => [
                'jquery-timepicker' => [
                    'path' => '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js',
                    'dependencies' => ['jquery-ui-core'],
                ],
            ],
            'inline' => [
                'script' => '$(".setting_time_picker").timepicker(ULWP.settings.getField("time_picker").getConfig());',
            ],
            'json' => [
                'data' => self::filterIfCallbackOrFilter($options, 'array'),
            ],
        ];
    }
}
