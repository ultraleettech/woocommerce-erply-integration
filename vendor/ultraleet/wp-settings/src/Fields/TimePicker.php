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
                    'path' => ULTRALEET_WP_SETTINGS_ASSETS_URL . 'plugins/jquery-timepicker/jquery.timepicker.min.css',
                    'dependencies' => ['jquery-ui-style'],
                ],
            ],
            'scripts' => [
                'jquery-timepicker' => [
                    'path' => ULTRALEET_WP_SETTINGS_ASSETS_URL . 'plugins/jquery-timepicker/jquery.timepicker.min.js',
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
