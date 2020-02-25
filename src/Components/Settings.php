<?php

namespace Ultraleet\WcErply\Components;

use Ultraleet\WP\Settings\Settings as SettingsAPI;


/**
 * Plugin settings manager
 *
 * @mixin SettingsAPI
 */
class Settings extends AbstractComponent
{
    /** @var SettingsAPI */
    private $settingsApi;

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        $config = $this->sync->filterConfig($this->getConfig());
        $this->settingsApi = new SettingsAPI('wcerply', $config, [
            'pluginBaseFile' => ULTRALEET_WCERPLY_FILE,
            'assetsPath' => ULTRALEET_WCERPLY_ASSETS_URL,
            'scriptDependencies' => ['wcerply'],
            'jsonFormat' => "WcErply.setSettings(%s)",
            'isSettingsPage' => [$this->wp, 'isSettingsPage'],
        ]);

        // Settings sections enabled filters

        // Filters for getting options for various select, radio, and checkbox fields
        add_filter('wcerply_get_language_options', [$this, 'getLanguageOptions']);
        add_filter('wcerply_get_cron_schedule_options', [$this, 'getCronScheduleOptions']);
        add_filter('wcerply_get_warehouse_options', [$this->sync, 'getWarehouseOptions']);

        // Filters for default values of fields

        // Settings section save triggers
        add_action('wcerply_save_api_settings', [$this, 'onSaveApiSettings'], 10, 2);
        add_action('wcerply_save_schedule_settings', [$this, 'onSaveScheduleSettings'], 10, 2);
    }

    /**
     * Populate and return plugin settings config array.
     *
     * @return array
     */
    protected function getConfig(): array
    {
        $config = include ULTRALEET_WCERPLY_CONFIG_PATH . 'settings.php';

        return $config;
    }

    /**
     * Get options for a cron schedule select field.
     *
     * @return array
     */
    public function getCronScheduleOptions(): array
    {
        $schedules = wp_get_schedules();
        uasort($schedules, function ($a, $b) {
            if ($a['interval'] == $b['interval']) {
                return 0;
            }
            return ($a['interval'] < $b['interval']) ? -1 : 1;
        });
        return wp_list_pluck($schedules, 'display');
    }

    /**
     * Triggered when API settings are saved.
     *
     * @param $oldValue
     * @param $newValue
     *
     * @todo Extract class
     */
    public function onSaveApiSettings($oldValue, $newValue)
    {
        if ($oldValue == $newValue) {
            // TODO: Allow API recheck in case of a permission error; skip otherwise
            //return;
        }
        $userName = $newValue['username'];
        $password = $newValue['password'];
        $customerCode = $newValue['customer_code'];
        if ($userName && $password && $customerCode && $this->api->test()) {
            $this->sync->enable();
        } else {
            $this->sync->disable();
        }
    }

    /**
     * Triggered when schedule settings are saved.
     *
     * @param $oldValue
     * @param $newValue
     */
    public function onSaveScheduleSettings($oldValue, $newValue)
    {
        if ($newValue['enabled']) {
            $this->cron->schedule(true);
        } else {
            $this->cron->unschedule(false);
        }
    }

    /**
     * Proxy settings API method calls.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (!method_exists($this->settingsApi, $name)) {
            trigger_error('Call to undefined method ' . __CLASS__ . '::' . $name . '()', E_USER_ERROR);
        }
        return call_user_func_array([$this->settingsApi, $name], $arguments);
    }
}
