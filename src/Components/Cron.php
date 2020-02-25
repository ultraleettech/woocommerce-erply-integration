<?php

namespace Ultraleet\WcErply\Components;

use Ultraleet\WcErply;
use Ultraleet\WcErply\Traits\RegistersWpHooks;

class Cron extends AbstractComponent
{
    use RegistersWpHooks;

    /**
     * WP init action hook
     */
    public function init()
    {
        $this->registerHook('cron_schedules', 'filter', 'addCronSchedules', 9999);
        $this->registerHook('wcerply_queue', 'action', 'run');

        $this->schedule();
        register_deactivation_hook(ULTRALEET_WCERPLY_FILE, [$this, 'unschedule']);

        if (wp_doing_cron() && $this->sync->isActive()) {
            $this->sync->registerHooks();
            WcErply::setPhpIniValues();
        }
    }

    /**
     * Filter: cron_schedules
     *
     * @param array $schedules
     * @return array
     */
    public function addCronSchedules(array $schedules): array
    {
        $pluginSchedules = [
            'twice_hourly' => [
                'interval' => 30 * 60,
                'display' => esc_html__('Twice Hourly', 'wcerply'),
            ],
            'two_hours' => [
                'interval' => 2 * 60 * 60,
                'display' => esc_html__('Every Two Hours', 'wcerply'),
            ],
            'three_hours' => [
                'interval' => 3 * 60 * 60,
                'display' => esc_html__('Every Three Hours', 'wcerply'),
            ],
            'four_hours' => [
                'interval' => 4 * 60 * 60,
                'display' => esc_html__('Every Four Hours', 'wcerply'),
            ],
            'six_hours' => [
                'interval' => 6 * 60 * 60,
                'display' => esc_html__('Every Six Hours', 'wcerply'),
            ],
            'eight_hours' => [
                'interval' => 8 * 60 * 60,
                'display' => esc_html__('Every Eight Hours', 'wcerply'),
            ],
        ];
        $intervals = wp_list_pluck($pluginSchedules, 'interval');
        return array_merge(
            array_filter(
                $schedules,
                function ($schedule) use ($intervals) {
                    return !in_array($schedule['interval'], $intervals);
                }
            ),
            $pluginSchedules
        );
    }

    /**
     * Action: wcerply_queue
     *
     * Generates synchronization queue.
     *
     * @throws \Exception
     */
    public function run()
    {
        if (!$this->sync->isActive() || get_transient('wcerply_generating_queue')) {
            return;
        }
        $this->logger->debug('Generating synchronization queue...');
        set_transient('wcerply_generating_queue', true, 600);
        $this->sync->generateQueue();
        delete_transient('wcerply_generating_queue');
        $this->logger->debug('Synchronization queue generated.');
    }

    /**
     * Schedule events if not already added.
     * @param bool $reschedule
     */
    public function schedule($reschedule = false)
    {
        if ($reschedule) {
            $this->unschedule(false);
        }
        if (! wp_next_scheduled('wcerply_queue') && $this->settings->getSettingValue('enabled', 'schedule', 'cron')) {
            $verb = $reschedule ? 'Rescheduling' : 'Scheduling';
            $this->logger->debug("$verb cron event for generating queue.");
            $time = $this->settings->getSettingValue('start', 'schedule', 'cron');
            $timestamp = strtotime("today $time");
            if ($timestamp < time()) {
                $timestamp = strtotime("tomorrow $time");
            }
            wp_schedule_event(
                $timestamp,
                $this->settings->getSettingValue('frequency', 'schedule', 'cron'),
                'wcerply_queue'
            );
        }
    }

    /**
     * Unschedule events upon plugin deactivation.
     * @param bool $deactivation
     */
    public function unschedule($deactivation = true)
    {
        if ($timestamp = wp_next_scheduled('wcerply_queue')) {
            $noun = $deactivation ? 'Plugin' : 'Synchronization';
            $this->logger->debug("$noun deactivated, unscheduling cron event.");
            wp_unschedule_event($timestamp, 'wcerply_queue');
        }
    }
}
