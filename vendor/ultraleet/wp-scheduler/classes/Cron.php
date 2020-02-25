<?php

namespace Ultraleet\WP\Scheduler;

class Cron
{
    /**
     * Filter: cron_schedules
     *
     * @param array $schedules
     * @return array
     */
    public function addCronSchedule(array $schedules)
    {
        $schedules['every_minute'] = [
            'interval' => 60, // in seconds
            'display' => __('Every minute'),
        ];
        return $schedules;
    }

    /**
     * Schedule cron hook.
     */
    public function schedule()
    {
        if (!wp_next_scheduled(Scheduler::CRON_HOOK)) {
            $schedule = apply_filters('ultraleet_scheduler_run_schedule', Scheduler::CRON_SCHEDULE);
            wp_schedule_event(time(), $schedule, Scheduler::CRON_HOOK);
        }
    }

    /**
     * Unschedule cron hook.
     */
    public function unschedule()
    {
        if ($timestamp = wp_next_scheduled(Scheduler::CRON_HOOK)) {
            wp_unschedule_event($timestamp, Scheduler::CRON_HOOK);
        }
    }
}
