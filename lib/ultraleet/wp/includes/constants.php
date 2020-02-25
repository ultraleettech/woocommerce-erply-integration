<?php

/**
 * Version constants.
 */
define('ULTRALEET_WP_VERSION', '0.1.0');

/**
 * Path constants.
 */
define('ULTRALEET_WP_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('ULTRALEET_WP_ASSETS_PATH', ULTRALEET_WP_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('ULTRALEET_WP_ASSETS_URL', plugin_dir_url(ULTRALEET_WP_ASSETS_PATH . 'index.php'));


/**
 * Action priority constants.
 *
 * ULTRALEET_EARLIEST_PRIORITY: Should beat absolutely all other hooks.
 * ULTRALEET_EARLY_PRIORITY: Priority slightly lower than 0 to beat all other hooks except for the the negative priority ones.
 * ULTRALEET_LATEST_PRIORITY: All other hooks should be finished by then.
 */
defined('ULTRALEET_EARLIEST_PRIORITY') || define('ULTRALEET_EARLIEST_PRIORITY', -1337*1337); // -1787569
defined('ULTRALEET_EARLY_PRIORITY') || define('ULTRALEET_EARLY_PRIORITY', -1337/31337); // -.04266522002744359702587995021859
defined('ULTRALEET_LATEST_PRIORITY') || define('ULTRALEET_LATEST_PRIORITY', 1337*1337); // 1787569
