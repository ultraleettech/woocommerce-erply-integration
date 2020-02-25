<?php

namespace Ultraleet\WcErply\Synchronizers;

use Monolog\Logger;
use Ultraleet\WcErply;
use Ultraleet\WcErply\Plugin;
use Ultraleet\WP\Scheduler\Scheduler;
use Ultraleet\WcErply\Components\ErplyApi;
use Ultraleet\WcErply\Components\Settings;
use Ultraleet\WcErply\Components\ViewRenderer;
use WP_Error;

abstract class AbstractSynchronizer
{
    const DIRECTION_FROM = 'FROM';
    const DIRECTION_TO = 'TO';
    const DIRECTION_NONE = '';

    /**
     * @var ErplyApi
     */
    protected $api;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * @var ViewRenderer
     */
    protected $view;

    /**
     * @var Scheduler
     */
    protected $scheduler;

    protected $termMap;
    protected $vatRates;

    public function __construct(Plugin $container)
    {
        $this->api = $container->api;
        $this->logger = $container->logger;
        $this->settings = $container->settings;
        $this->view = $container->view;
        $this->scheduler = $container->scheduler;

        $this->init();
    }

    /**
     * Return an instance of the synchronizer object.
     *
     * Only to be used within static methods when an instance is needed.
     *
     * @return static
     * @throws \Exception
     */
    protected static function getInstance(): self
    {
        return WcErply::plugin()->sync->getSynchronizer(static::getName());
    }

    /**
     * Override to provide initialization (register hooks etc).
     */
    protected function init() {}

    /**
     * Return synchronizer display title.
     *
     * @return string
     */
    abstract static public function getTitle(): string;

    /**
     * Categorize synchronization direction.
     *
     * Possible values:
     * DIRECTION_FROM:  Imports Erply data into Woocommerce
     * DIRECTION_TO:    Exports Woocommerce data to Erply
     * @return string
     */
    public static function getDirection()
    {
        return static::DIRECTION_NONE;
    }

    /**
     * @return array
     */
    public static function getPrerequisites()
    {
        return [];
    }

    /**
     * Override to provide synchronizer-specific settings.
     *
     * Full configuration array is passed by reference, so we can manipulate it directly.
     *
     * @param array $config
     */
    public static function addSettingsConfig(array &$config)
    {
    }

    /**
     * Override to disable synchronizer conditionally (for instance, based on active license package).
     *
     * Those worker classes that return true will not be shown on active synchronizers page and will be inactive.
     *
     * @return bool
     */
    public static function disabled(): bool
    {
        return false;
    }

    /**
     * Whether or not a synchronizer is activated by site admin.
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        return in_array(
            static::getName(),
            WcErply::$plugin->settings->getSettingValue('workers', 'active', 'components')
        );
    }

    /**
     * Whether or not this worker is a troubleshooter.
     *
     * @return bool
     */
    public static function isTroubleshooter(): bool
    {
        return false;
    }

    /**
     * Return synchronizer name.
     *
     * Defaults to class name converted to snake_case format.
     *
     * @return string
     */
    public static function getName(): string
    {
        $class = static::class;
        $parts = explode('\\', $class);
        $className = end($parts);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    /**
     * Implement to register specific hooks needed by the synchronizer (such as data export to Erply).
     */
    public static function registerHooks() {}

    /**
     * Whether worker generates queue automatically via cron schedule.
     *
     * @return bool
     */
    public function hasCron(): bool
    {
        return false;
    }

    /**
     * List of actions to register for this worker.
     *
     * Array keys are action names (will be prefixed automatically so no need for unique names)
     * and values are method names to be called when processing.
     */
    public function getQueueActions(): array
    {
        return [];
    }

    /**
     * Generate and schedule tasks to run for this worker.
     */
    public function generateQueue()
    {

    }

    /**
     * Registers actions for scheduler to run for queued tasks.
     */
    public function registerSchedulerHooks()
    {
        foreach ($this->getQueueActions() as $name => $method) {
            add_action($this->getHookName($name), [$this, $method], 10, 1);
        }
    }

    protected function getHookName(string $actionName): string
    {
        $name = static::getName();
        return "wcerply_{$name}_$actionName";
    }

    protected function addQueueItem(string $actionName, $data)
    {
        $hook = $this->getHookName($actionName);
        $this->scheduler->schedule(static::getName(), $hook, $data);
    }

    protected function addQueue(string $actionName, array $dataRows)
    {
        $group = static::getName();
        $hook = $this->getHookName($actionName);
        $tasks = [];
        foreach ($dataRows as $data) {
            $tasks[] = [
                'group' => $group,
                'hook' => $hook,
                'data' => $data,
            ];
        }
        $this->scheduler->scheduleBulk($tasks);
    }

    /**
     * Return translated
     *
     * @return string
     */
    public function getDirectionLabel(): string
    {
        $labels = [
            self::DIRECTION_FROM => __('from', 'wcerply'),
            self::DIRECTION_TO => __('to', 'wcerply'),
        ];
        return $labels[static::getDirection()];
    }

    /**
     * Save the last sync time included in API response.
     *
     * @param array|int $response
     */
    protected function saveTimestamp($response)
    {
        $timeStamp = is_array($response) ? $response['status']['requestUnixTime'] : $response;
        $optionName = 'wcerply_last_sync_time_' . static::getName();
        update_option($optionName, $timeStamp, false);
    }

    /**
     * Return last sync time.
     *
     * @return mixed
     */
    public function getTimestamp()
    {
        $optionName = 'wcerply_last_sync_time_' . static::getName();
        return (int) get_option($optionName, 0);
    }

    /**
     * Load product category ID map(s) from the database.
     */
    protected function loadTermMap()
    {
        if (!isset($this->termMap)) {
            $this->termMap = get_option('wcerply_product_term_map', []);
        }
    }

    /**
     * Create VAT rates map based on active rates on Erply.
     *
     * @throws \Exception
     */
    protected function getVatRatesFromErply()
    {
        $rates = $this->api->request('getVatRates', [
            'active' => 1,
            'orderBy' => 'id',
            'orderByDir' => 'asc',
            'recordsOnPage' => 1000,
        ]);
        $this->vatRates = [];
        foreach ($rates['records'] as $record) {
            $this->vatRates[(string) $record['rate']] = [
                'id' => $record['id'],
                'name' => $record['name'],
                'code' => $record['code'],
            ];
        }
        $this->saveVatRatesMap();
    }

    /**
     * Get Erply VAT rate ID based on rate.
     *
     * @param $rate
     * @return int
     */
    protected function getVatRateId(string $rate): int
    {
        if (!array_key_exists($rate, $this->vatRates)) {
            try {
                $codes = array_map(
                    function ($item) {
                        return intval($item['code']);
                    },
                    $this->vatRates
                );
                $code = max($codes) + 1;
                $name = "$rate%";
                $result = $this->api->request(
                    'saveVatRate',
                    [
                        'name' => $name,
                        'code' => "$code",
                        'rate' => $rate,
                    ]
                );
                $this->vatRates[$rate] = [
                    'id' => $result['records'][0]['vatRateID'],
                    'name' => $name,
                    'code' => "$code",
                ];
                $this->saveVatRatesMap();
            } catch (\Exception $e) {
                // TODO: default VAT rate?
                return 1;
            }
        }
        return $this->vatRates[$rate]['id'];
    }

    /**
     * Load Erply VAT rates map from database.
     */
    protected function loadVatRatesMap()
    {
        $this->vatRates = get_option('wcerply_vat_rates_map', []);
    }

    /**
     * Save Erply VAT rates map to database.
     */
    protected function saveVatRatesMap()
    {
        update_option('wcerply_vat_rates_map', $this->vatRates, false);
    }

    /**
     * Save product category ID map(s) to database.
     */
    protected function saveTermMap()
    {
        update_option('wcerply_product_term_map', $this->termMap, false);
    }

    /**
     * Determine whether woocommerce brands plugin is active.
     *
     * @return bool
     */
    protected function isBrandsSupported(): bool
    {
        if (! isset($this->isBrandsSupported)) {
            $this->isBrandsSupported = is_plugin_active('woocommerce-brands/woocommerce-brands.php');
        }
        return $this->isBrandsSupported;
    }

    /**
     * Log query results based on whether it was a WP error.
     *
     * @param $message
     * @param $result
     */
    protected function logResult($message, $result)
    {
        if (is_wp_error($result)) {
            /** @var WP_Error $result */
            $this->logger->error($message, $result->errors);
        } else {
            $this->logger->debug($message, $result);
        }
    }

    /**
     * Sideload media image unless already added.
     *
     * @param $imageUrl
     * @return int|null Media ID
     */
    protected function fetchImage($imageUrl)
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        if ($id = $this->getImageAttachmentId($imageUrl)) {
            $this->logger->debug("Using media #$id for image: " . basename($imageUrl));
            return $id;
        }
        $logMessage = "Downloading image: $imageUrl: ";
        $media = media_sideload_image($imageUrl, 0);
        if (is_wp_error($media)) {
            $logMessage .= 'FAIL: ' . $media->get_error_message();
            $this->logger->debug($logMessage);
            return null;
        }
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => null,
            'post_parent' => 0,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'posts_per_page' => 1,
            'suppress_filters' => false,
        ]);
        $id = $attachments[0]->ID ?? null;
        if ($id) {
            add_post_meta($id, '_attachment_filename', basename($imageUrl), false);
            $logMessage .= "SUCCESS (id=$id)";
        } else {
            $logMessage .= 'FAIL';
        }
        $this->logger->debug($logMessage);
        return $id;
    }

    /**
     * Get attachment post ID by image file name.
     *
     * @param $imageUrl
     * @return mixed
     */
    private function getImageAttachmentId($imageUrl)
    {
        global $wpdb;
        $fileName = basename($imageUrl);
        $id = intval(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_attachment_filename' AND meta_value = %s",
                    $fileName
                )
            )
        );
        if ($id) {
            return $id;
        }
        $id = intval(
            $wpdb->get_var(
                $wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%/%s'", $fileName)
            )
        );
        return $id ?: null;
    }

    /**
     * Execute the synchronization.
     *
     * @deprecated
     */
    public function execute()
    {
        // DO NOTHING.
    }
}
