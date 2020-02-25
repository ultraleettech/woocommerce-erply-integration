<?php

namespace Ultraleet\WcErply\Components;

use Ultraleet\WcErply;
use Ultraleet\WcErply\Synchronizers\AbstractSynchronizer;
use Ultraleet\WcErply\Synchronizers\ImportCategories;
use Ultraleet\WcErply\Synchronizers\ImportProducts;
use Ultraleet\WcErply\Troubleshooters\ImportMissingProducts;
use Ultraleet\WcErply\Troubleshooters\AbstractTroubleshooter;
use Ultraleet\WcErply\Troubleshooters\RemoveDuplicateProducts;

class Synchronization extends AbstractComponent
{
    const REFRESH_WAREHOUSES_INTERVAL = 60*60*24; // 1 day

    protected $synchronizers = [];
    protected $hooks;

    /**
     * Get list of all synchronizer and troubleshooter classes
     *
     * @return array
     *
     * @todo Add filter for add-ons
     */
    public function synchronizers(): array
    {
        return [
            // Importers
            ImportCategories::class,
            ImportProducts::class,

            // Troubleshooters
            RemoveDuplicateProducts::class,
            ImportMissingProducts::class,
        ];
    }

    /**
     * Return whether or not synchronization is active.
     *
     * @param bool $includeLicense
     * @return bool
     */
    public function isActive(bool $includeLicense = true): bool
    {
        return get_option('wcerply_sync_enabled');
    }

    /**
     * Enable synchronization.
     */
    public function enable()
    {
        update_option('wcerply_sync_enabled', true, false);
    }

    /**
     * Disable synchronization.
     */
    public function disable()
    {
        update_option('wcerply_sync_enabled', false, false);
    }

    /**
     * Add synchronization configuration to settings.
     *
     * @param array $config
     * @return array
     */
    public function filterConfig(array $config): array
    {
        $options = [];
        foreach ($this->getEnabledSynchronizerClasses() as $class) {
            if (AbstractTroubleshooter::DIRECTION_TROUBLESHOOTER !== $class::getDirection()) {
                $options[$class::getName()] = $class::getTitle();
            }
        }
        $config['components'] = [
            'title' => __('Components', 'wcerply'),
            'sections' => [
                'active' => [
                    'fields' => [
                        'header' => [
                            'type' => 'section_header',
                            'text' => __('Choose which components you wish to actively synchronize with Erply.', 'wcerply'),
                        ],
                        'workers' => [
                            'type' => 'checkbox_list',
                            'label' => __('Active components', 'wcerply'),
                            'id' => 'active-components',
                            'options' => $options,
                        ],
                    ],
                ],
            ],
            'assets' => [
                'scripts' => [
                    'wcerply-settings-components' => [
                        'path' => 'js/settings/components.js',
                    ],
                ],
                'json' => [
                    'handle' => 'wcerply',
                    'data' => [
                        'prerequisites' => $this->getAllSynchronizerPrerequisites(),
                    ],
                    'position' => 'after',
                ],
            ],
        ];
        add_filter('ultraleet_wp_settings_config', [$this, 'addSynchronizerConfig']);
        return $config;
    }

    /**
     *
     *
     * @param array $config
     * @return array
     */
    public function addSynchronizerConfig(array $config): array
    {
        foreach ($this->getActiveSynchronizerClasses() as $class) {
            $class::addSettingsConfig($config);
        }
        return $config;
    }

    /**
     * Registers synchronizer specific hooks (non-scheduler).
     */
    public function registerWorkerHooks()
    {
        foreach ($this->getActiveSynchronizerClasses(false) as $class) {
            $class::registerHooks();
        }
    }

    /**
     * Register needed hooks as well as synchronizer specific hooks (scheduler hooks only).
     */
    public function registerHooks()
    {
        add_action('ultraleet_scheduler_before_run', [$this, 'registerSchedulerHooks']);
        add_action('ultraleet_scheduler_after_run', [$this, 'clearSchedulerHooks']);

        $this->registerWorkerSchedulerHooks();
    }

    /**
     * Registers scheduler initialization hooks.
     */
    public function registerSchedulerHooks()
    {
        foreach ($this->getHooks() as $args) {
            $type = array_shift($args);
            $function = "add_$type";
            call_user_func_array($function, $args);
        }
    }

    /**
     * Get hooks needed by various synchronizers.
     *
     * @return array
     *
     * @todo Refactor: extract synchronizer-specific hooks into synchronizer classes.
     */
    protected function getHooks(): array
    {
        if (! isset($this->hooks)) {
            $this->hooks = [
                // Include drafted items in post translations.
                [
                    'filter',
                    'user_has_cap',
                    /**
                     * @param array $capabilities
                     * @param array $caps
                     * @param array $args
                     * @return array
                     */
                    function (array $capabilities, array $caps, array $args) {
                        if ('read_private_posts' == $args[0]) {
                            $capabilities = array_merge($capabilities, $caps);
                            $capabilities['read_private_posts'] = true;
                        }
                        return $capabilities;
                    },
                    10,
                    4
                ],
            ];
        }
        return $this->hooks;
    }

    /**
     * Clears scheduler initialization hooks.
     */
    public function clearSchedulerHooks()
    {
        foreach ($this->getHooks() as $args) {
            $type = array_shift($args);
            $function = "remove_$type";
            call_user_func_array($function, $args);
        }
    }

    /**
     * Registers scheduler hooks for each worker that uses task queue.
     */
    protected function registerWorkerSchedulerHooks()
    {
        foreach ($this->getSynchronizers() as $worker) {
            $worker->registerSchedulerHooks();
        }
    }

    /**
     * Register ajax hooks for troubleshooters.
     */
    public function registerAjaxHooks()
    {
        foreach ($this->getTroubleshooterClasses() as $troubleshooter) {
            $route = 'wcerply/admin/ajax/troubleshoot/' . $troubleshooter::getName();
            add_action("wp_ajax_$route", [$this->router, 'route']);
        }
    }

    /**
     * @param string $type
     * @return AbstractSynchronizer
     * @throws \Exception
     */
    public function getSynchronizer(string $type): AbstractSynchronizer
    {
        if (! isset($this->synchronizers[$type])) {
            $className = null;
            foreach ($this->getEnabledSynchronizerClasses() as $class) {
                if ($type == $class::getName()) {
                    $className = $class;
                    break;
                }
            }
            if ($className) {
                return $this->synchronizers[$type] = new $className(WcErply::$plugin);
            }
        }
        throw new \Exception("Synchronizer '$type' not found!", 404);
    }

    /**
     * @return AbstractSynchronizer[]
     * @throws \Exception
     */
    public function getSynchronizers(): array
    {
        foreach ($this->getEnabledSynchronizerClasses() as $class) {
            $type = $class::getName();
            if (! isset($this->synchronizers[$type])) {
                $this->getSynchronizer($type);
            }
        }
        return $this->synchronizers;
    }

    /**
     * @param bool $includeTroubleshooters
     * @return AbstractSynchronizer[]
     * @throws \Exception
     */
    public function getActiveSynchronizers(bool $includeTroubleshooters = true): array
    {
        $objects = [];
        foreach ($this->getActiveSynchronizerClasses($includeTroubleshooters) as $class) {
            $objects[] = $this->getSynchronizer($class::getName());
        }
        return $objects;
    }

    /**
     * @param bool $includeTroubleshooters
     * @return AbstractSynchronizer[]
     */
    public function getEnabledSynchronizerClasses(bool $includeTroubleshooters = true): array
    {
        return array_filter($this->synchronizers(), function ($className) use ($includeTroubleshooters) {
            /** @var AbstractSynchronizer $className */
            return ! $className::disabled() && ($includeTroubleshooters || ! $className::isTroubleshooter());
        });
    }

    /**
     * @param bool $includeTroubleshooters
     * @return AbstractSynchronizer[]
     */
    public function getActiveSynchronizerClasses(bool $includeTroubleshooters = true): array
    {
        return array_filter($this->getEnabledSynchronizerClasses($includeTroubleshooters), function ($className) {
            /** @var AbstractSynchronizer $className */
            return $className::isActive();
        });
    }

    /**
     * Returns an array containing all enabled synchronizers' dependencies.
     *
     * @return array
     */
    public function getAllSynchronizerPrerequisites(): array
    {
        $prerequisites = [];
        foreach ($this->getEnabledSynchronizerClasses() as $class) {
            $prerequisites[$class::getName()] = $class::getPrerequisites();
        }
        return $prerequisites;
    }

    /**
     * @return AbstractTroubleshooter[]
     * @throws \Exception
     */
    public function getTroubleshooters(): array
    {
        return array_filter($this->getSynchronizers(), function (AbstractSynchronizer $synchronizer) {
            return AbstractTroubleshooter::DIRECTION_TROUBLESHOOTER === $synchronizer->getDirection();
        });
    }

    /**
     * @return array
     */
    public function getTroubleshooterClasses(): array
    {
        return array_filter($this->synchronizers(), function ($className) {
            /** @var AbstractTroubleshooter $className */
            return AbstractTroubleshooter::DIRECTION_TROUBLESHOOTER === $className::getDirection();
        });
    }

    /**
     * Generates queue for all active synchronizers.
     *
     * @param bool $doingCron
     * @throws \Exception
     */
    public function generateQueue(bool $doingCron = true)
    {
        foreach ($this->getActiveSynchronizers() as $worker) {
            $this->generateWorkerQueue($worker, $doingCron);
        }
    }

    /**
     * Generate queue for a synchronizer/troubleshooter.
     *
     * Also recursively generates queue for prerequisite synchronizers first.
     *
     * @param AbstractSynchronizer $worker
     * @param bool $doingCron
     * @throws \Exception
     */
    public function generateWorkerQueue(AbstractSynchronizer $worker, bool $doingCron)
    {
        static $processed = [];
        if (!$doingCron || $worker->hasCron()) {
            foreach ($worker->getPrerequisites() as $name) {
                if (in_array($name, $processed)) {
                    continue;
                }
                $this->generateWorkerQueue($this->getSynchronizer($name), $doingCron);
            }
            $worker->generateQueue();
            $processed[] = $worker->getName();
        }
    }

    /**
     * Filter available warehouses to id => name pairs.
     *
     * @return array
     * @throws \Exception
     */
    public function getWarehouseOptions(): array
    {
        return wp_list_pluck($this->getWarehouses(), 'name', 'warehouseID');

    }

    /**
     * Get all available warehouses from Erply.
     *
     * @return array
     * @throws \Exception
     */
    public function getWarehouses(): array
    {
        $transientName = apply_filters('wcerply_warehouses_transient_name', 'wcerply_warehouse_records');
        $params = apply_filters('wcerply_get_warehouses_params', []);
        if (! $warehouses = get_transient($transientName)) {
            $response = $this->api->request('getWarehouses', $params);
            $warehouses = $response['records'] ?? [];
            set_transient($transientName, $warehouses, self::REFRESH_WAREHOUSES_INTERVAL);
        }
        return $warehouses;
    }

    // DEPRECATED METHODS:

    /**
     * Handle synchronization admin main page execution and generation.
     *
     * @param string $type
     * @throws \Exception
     *
     * @deprecated
     */
    public function page($type = null)
    {
        $type = $type ?: filter_input(INPUT_GET,'type');
        if ($type) {
            $this->runSynchronizer($type);
            return;
        }
        echo $this->view->render('index');

        /*
        $synchronizers = $this->getSynchronizers();
        echo $this->view->render('synchronize', [
            'importers' => array_filter($synchronizers, function(AbstractSynchronizer $object) {
                return $object->getDirection() == AbstractSynchronizer::DIRECTION_FROM;
            }),
            'exporters' => array_filter($synchronizers, function(AbstractSynchronizer $object) {
                return $object->getDirection() == AbstractSynchronizer::DIRECTION_TO;
            }),
        ]);
        */
    }

    /**
     * Execute a specific synchronization process.
     *
     * @param string $type
     * @throws \Exception
     *
     * @deprecated
     */
    public function runSynchronizer(string $type)
    {
        ini_set('max_execution_time', 0);
        ini_set('display_errors', WP_DEBUG);

        $worker = $this->getSynchronizer($type);
        $message = sprintf(
            __('Starting synchronization %1$s Erply: %2$s', 'wcerply'),
            $worker->getDirectionLabel(),
            $worker->getTitle()
        );
        $this->logger->info($message);
        $worker->execute();
        $message = sprintf(
            __('%s synchronized.', 'wcerply'),
            $worker->getTitle()
        );
    }

    /**
     * Call a method on a synchronizer with specified type and return the results.
     *
     * Used in ajax query callbacks.
     *
     * @param string $type
     * @param string $method
     * @param $args
     * @return mixed
     * @throws \Throwable
     *
     * @deprecated
     */
    public function callSynchronizerMethod(string $type, string $method, $args = [])
    {
        $date = date('Y-m-d');
        ini_set('max_execution_time', 0);
        ini_set('error_log', ULTRALEET_WCERPLY_VAR_PATH . "error-$date.log");

        $args = is_array($args) ? $args : [$args];
        try {
            $sync = $this->getSynchronizer($type);
            $result = call_user_func_array([$sync, $method], $args);
        } catch (\Throwable $e) {
            $this->logger->error($e);
            throw $e;
        }
        return $result;
    }
}
