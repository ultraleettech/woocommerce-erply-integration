<?php

return [
    // PAGE: GENERAL
    'general' => [
        'title' => __('General', 'wcerply'),
        'sections' => [
            'api' => [
                'fields' => [
                    'header' => [
                        'type' => 'section_header',
                        'title' => __('Erply API Settings', 'wcerply'),
                        'text' => __('Enter your Erply API information below.', 'wcerply'),
                    ],
                    'username' => [
                        'label' => __('Username', 'wcerply'),
                        'id' => 'erply-username',
                    ],
                    'password' => [
                        'type' => 'password',
                        'label' => __('Password', 'wcerply'),
                        'id' => 'erply-password',
                    ],
                    'customer_code' => [
                        'label' => __('Customer code', 'wcerply'),
                        'id' => 'erply-customer-code',
                    ],
                ],
                'onSave' => 'wcerply_save_api_settings',
            ],
        ],
    ],

    // PAGE: SCHEDULING
    'cron' => [
        'title' => __('Scheduling', 'wcerply'),
        'sections' => [
            'schedule' => [
                'fields' => [
                    'header' => [
                        'type' => 'section_header',
                        'text' => __('Choose the scheduling frequency as well as the start time of the schedule for automatic synchronization of data with Erply.', 'wcerply'),
                    ],
                    'enabled' => [
                        'type' => 'checkbox',
                        'label' => __('Enabled', 'wcerply'),
                        'description' => __('Enable automatic synchronization with Erply'),
                    ],
                    'frequency' => [
                        'type' => 'select',
                        'label' => __('Frequency', 'wcerply'),
                        'options' => 'wcerply_get_cron_schedule_options',
                        'default' => 'daily',
                    ],
                    'start' => [
                        'type' => 'time_picker',
                        'label' => __('Start time', 'wcerply'),
                        'default' => '00:00',
                    ],
                ],
                'onSave' => 'wcerply_save_schedule_settings',
            ],
        ]
    ],
];
