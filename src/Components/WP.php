<?php

namespace Ultraleet\WcErply\Components;

use Exception;
use WC_Product;
use WC_Cache_Helper;
use Ultraleet\WcErply;
use Ultraleet\WcErply\Traits\RegistersWpHooks;

/**
 * Class WP
 */
class WP extends AbstractComponent
{
    use RegistersWpHooks;

    const CODE_INVALID_LICENSE = 1000;

    private $options = [];

    /**
     * Boot WP integration.
     */
    public function boot()
    {
        $this->registerActivationHook();
        $this->registerHook('init');
    }

    /**
     * Perform plugin activation tasks.
     */
    protected function registerActivationHook()
    {
        register_activation_hook(ULTRALEET_WCERPLY_FILE, function () {
            if (!is_dir(ULTRALEET_WCERPLY_VAR_PATH)) {
                mkdir(ULTRALEET_WCERPLY_VAR_PATH, 0700);
            }
            if (!$this->getInstallationId()) {
                $this->generateInstallationId();
            }
        });
    }

    /**
     * @return string|false
     */
    public function getInstallationId()
    {
        return get_option('wcerply_installation_id');
    }

    /**
     * Generates unique ID for the plugin installation.
     *
     * @throws Exception
     */
    public function generateInstallationId()
    {
        add_option('wcerply_installation_id', bin2hex(random_bytes(32)));
    }

    /**
     * Action: init
     */
    public function init()
    {
        // Initialize admin functionality
        if (is_admin()) {
            // Initialize settings
            $this->settings;

            // Register hooks
            $this->registerHook('admin_menu');
            $this->registerHook('admin_enqueue_scripts');
            $this->registerHook('admin_notices');
            $this->registerHook('plugin_action_links', 'filter', 'addPluginActions', 10, 2);
            $this->registerAjax();
        }

        // Initialize cron integration
        $this->cron->init();

        // Register other hooks
        $this->registerWoocommerceHooks();
        $this->sync->registerWorkerHooks();
    }

    /**
     * Action: admin_menu
     */
    public function admin_menu()
    {
        add_menu_page(
            __('Erply synchronization', 'wcerply'),
            __('Erply Integration', 'wcerply'),
            'manage_woocommerce',
            $this->router->generatePath('admin_index'),
            [$this->router, 'route'],
            'dashicons-update-alt',
            56
        );
        add_submenu_page(
            $this->router->generatePath('admin_index'),
            __('Erply synchronization', 'wcerply'),
            __('Synchronization', 'wcerply'),
            'manage_woocommerce',
            $this->router->generatePath('admin_index'),
            [$this->router, 'route']
        );
        /*
        add_submenu_page(
            $this->router->generatePath('admin_index'),
            __('Erply integration troubleshooting', 'wcerply'),
            __('Troubleshooting', 'wcerply'),
            'manage_woocommerce',
            $this->router->generatePath('admin_troubleshooting'),
            [$this->router, 'route']
        );
        */
        add_submenu_page(
            $this->router->generatePath('admin_index'),
            __('Erply integration settings', 'wcerply'),
            __('Settings', 'wcerply'),
            'manage_woocommerce',
            $this->router->generatePath('settings_page'),
            [$this->router, 'route']
        );
    }

    /**
     * Callback for the settings API to determine if we are currently on the settings page.
     *
     * @return bool
     */
    public function isSettingsPage()
    {
        return $this->router->isRoute('settings_page');
    }

    /**
     * Action: admin_enqueue_scripts
     *
     * Enqueues core scripts and styles for plugin admin.
     */
    public function admin_enqueue_scripts()
    {
        wp_register_script(
            'wcerply',
            ULTRALEET_WCERPLY_ASSETS_URL . 'js/wcerply.js',
            ['jquery'],
            WcErply::version(ULTRALEET_WCERPLY_DEBUG)
        );
        wp_enqueue_script('wcerply');
    }

    /**
     * Action: admin_notices
     */
    public function admin_notices()
    {
        if ($notice = get_transient('wcerply_license_notice')) {
            echo $this->view->render("notices/$notice");
            delete_transient('wcerply_license_notice');
        }
        if (!$this->sync->isActive(false)) {
            $error = get_option('wcerply_api_error');
            if ($error || ($_GET['page'] ?? '') !== 'wcerply-settings') {
                if ($error) {
                    echo $this->view->render('notices/api-error', $error);
                } else {
                    echo $this->view->render('notices/api-not-setup');
                }
            }
        }
    }

    /**
     * Filter: plugin_action_links
     *
     * Adds links to plugin actions.
     *
     * @param array $links
     * @param string $file
     * @return array
     */
    public function addPluginActions(array $links, string $file): array
    {
        if ($file === plugin_basename(ULTRALEET_WCERPLY_FILE)) {
            $route = $this->router->generatePath('settings_page');
            $link = '<a href="' . admin_url("admin.php?page=$route") . '">' . __('Settings') . '</a>';
            array_unshift($links, $link);
        }
        return $links;
    }

    /**
     * Register WC hooks.
     */
    private function registerWoocommerceHooks()
    {
        $this->registerHook('woocommerce_order_data_store_cpt_get_orders_query', 'filter', 'registerOrdersQueryCustomVars', 10, 2);
        $this->registerHook('woocommerce_product_type_changed', 'action', 'updateProductTypeCache', 10, 3);
        if (is_admin()) {
            $this->registerHook('woocommerce_product_options_general_product_data', 'action', 'adminShowErplyProductId');
        }
    }

    public function registerOrdersQueryCustomVars($query, $query_vars)
    {
        if (isset($query_vars['erply_invoice_id'])) {
            $metaQuery = [
                'key' => '_erply_invoice_id',
            ];
            if (!$query_vars['erply_invoice_id']) {
                $metaQuery['compare'] = 'NOT EXISTS';
            } else {
                $metaQuery['value'] = esc_attr($query_vars['erply_invoice_id']);
            }
            $query['meta_query'][] = $metaQuery;
        }
        return $query;
    }

    /**
     * Action: woocommerce_product_type_changed
     *
     * @param WC_Product $product
     * @param string $oldType
     * @param string $newType
     */
    public function updateProductTypeCache(WC_Product $product, string $oldType, string $newType)
    {
        $id = $product->get_id();
        $cacheKey = WC_Cache_Helper::get_cache_prefix('product_' . $id) . '_type_' . $id;
        wp_cache_set($cacheKey, $newType, 'products');

    }

    /**
     * Display product's erply ID if available
     */
    public function adminShowErplyProductId()
    {
        global $post;
        if ($productId = get_post_meta($post->ID, '_erply_product_id', true)) {
            echo $this->view->render('admin/product-data', ['productId' => $productId]);
        }
    }

    protected function registerAjax()
    {
        add_action('wp_ajax_' . $this->router->generatePath('admin_ajax_synchronize'), [$this->router, 'route']);
        $this->sync->registerAjaxHooks();

        /**
         * @deprecated ajax hooks
         */
        $this->registerHook('wp_ajax_wcerply_update_product_stock', 'action', 'ajaxUpdateProductStock');
        $this->registerHook('wp_ajax_wcerply_get_updated_products', 'action', 'ajaxGetUpdatedProducts');
        $this->registerHook('wp_ajax_wcerply_update_products', 'action', 'ajaxUpdateProducts');
        $this->registerHook('wp_ajax_wcerply_update_products_complete', 'action', 'ajaxUpdateProductsComplete');
        $this->registerHook('wp_ajax_wcerply_update_product_categories', 'action', 'ajaxUpdateProductCategories');
        $this->registerHook('wp_ajax_wcerply_export_customers', 'action', 'ajaxExportCustomers');
        $this->registerHook('wp_ajax_wcerply_export_order', 'action', 'ajaxExportOrder');
    }

    /**
     * @throws \Throwable
     */
    public function ajaxUpdateProductStock()
    {
        $records = json_decode(file_get_contents('php://input'), true);
        $processed = $this->sync->callSynchronizerMethod('import_product_stock', 'updateStock', [$records]);
        wp_send_json(['processed' => $processed]);
    }

    /**
     * @throws \Throwable
     */
    public function ajaxGetUpdatedProducts()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $page = (int) $input['page'];
        $results = $this->sync->callSynchronizerMethod('import_products', 'getPage', $page);
        wp_send_json($results);
    }

    /**
     * @throws \Throwable
     */
    public function ajaxUpdateProducts()
    {
        $records = json_decode(file_get_contents('php://input'), true);
        $results = $this->sync->callSynchronizerMethod('import_products', 'syncRecords', [$records]);
        wp_send_json($results);
    }

    /**
     * @throws \Throwable
     */
    public function ajaxUpdateProductsComplete()
    {
        $stats = json_decode(file_get_contents('php://input'), true);
        $this->sync->callSynchronizerMethod('import_products', 'syncComplete', [$stats]);
        wp_send_json(true);
    }

    /**
     * @throws \Throwable
     */
    public function ajaxUpdateProductCategories()
    {
        $records = json_decode(file_get_contents('php://input'), true);
        $stats = $this->sync->callSynchronizerMethod('import_categories', 'processRecords', [$records]);
        wp_send_json($stats);
    }

    /**
     * @throws \Throwable
     */
    public function ajaxExportCustomers()
    {
        $stats = $this->sync->callSynchronizerMethod('export_customers', 'exportCustomers');
        wp_send_json($stats);
    }

    /**
     * @throws \Throwable
     */
    public function ajaxExportOrder()
    {
        $orderId = (int) $_POST['orderId'];
        $success = $this->sync->callSynchronizerMethod('export_orders', 'exportOrder', [$orderId]);
        wp_send_json($success);
    }
}
