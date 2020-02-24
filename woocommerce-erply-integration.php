<?php
/*
Plugin name: Ultraleet Woocommerce Erply Integration
Description: Enables integration between your WooCommerce shop and Erply POS software.
Version: 1.0.0
Author: Rene Aavik
*/

use Ultraleet\WcErply;
use Ultraleet\WP\RequirementsChecker;

// define constants
define('ULTRALEET_WCERPLY_VERSION', '1.0.0');
define('ULTRALEET_WCERPLY_FULL', false);
define('ULTRALEET_WCERPLY_FILE', __FILE__);
define('ULTRALEET_WCERPLY_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ULTRALEET_WCERPLY_SRC_PATH', ULTRALEET_WCERPLY_PATH . 'src' . DIRECTORY_SEPARATOR);
define('ULTRALEET_WCERPLY_VIEW_PATH', ULTRALEET_WCERPLY_PATH . 'view' . DIRECTORY_SEPARATOR);
define('ULTRALEET_WCERPLY_CONFIG_PATH', ULTRALEET_WCERPLY_PATH . 'config' . DIRECTORY_SEPARATOR);
define('ULTRALEET_WCERPLY_VAR_PATH', wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . 'wcerply' . DIRECTORY_SEPARATOR);
define('ULTRALEET_WCERPLY_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets/');
define('ULTRALEET_WCERPLY_DEBUG', defined('WP_DEBUG') && WP_DEBUG);
define('ULTRALEET_WCERPLY_PLUGIN_ID', 'wcerply');

// setup autoload
require_once('vendor/autoload.php');

// check PHP and WP version
$requirementsChecker = new RequirementsChecker([
    'title' => 'Woocommerce Erply Integration',
    'file' => __FILE__,
    'php' => '7.2',
    'wp' => '4.9',
    'plugins' => [
        'WooCommerce' => 'woocommerce/woocommerce.php',
    ]
]);
if ($requirementsChecker->passes()) {
    // init plugin
    $plugin = WcErply::boot();
}
