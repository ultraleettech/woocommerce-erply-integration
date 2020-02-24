<?php

if (! defined('ULTRALEET_WP_VERSION')) {
    /**
     * Ultraleet WP library version.
     */
    define('ULTRALEET_WP_VERSION', '1.0.0');

    /**
     * Get library version.
     *
     * @return bool
     */
    function ulwp_version()
    {
        return ULTRALEET_WP_VERSION;
    }

    /**
     * Get a term from a given taxonomy that has the exact name provided.
     *
     * @param string $name
     * @param string $taxonomy
     * @param string $output
     * @param string $filter
     * @return array|\WP_Error|\WP_Term|null
     */
    function ulwp_get_term_by_name(string $name, string $taxonomy, string $output = OBJECT, string $filter = 'raw')
    {
        global $wpdb;

        $format = "SELECT * FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE t.name = %s AND tt.taxonomy = %s LIMIT 0, 1";
        $query = $wpdb->prepare($format, $name, $taxonomy);
        $row = $wpdb->get_row($query);

        return $row ? get_term($row, $taxonomy, $output, $filter) : null;
    }

    /**
     * Get URL of a given path that is inside a plugin's directory hierarchy.
     *
     * Useful in libraries that are unaware of their exact location.
     *
     * @param string $pluginFile Full path to the plugin's main file.
     * @param string $subDir Full path to the directory we want the URL for.
     * @return string URL of subdirectory.
     */
    function ulwp_plugin_dir_url_from_subdir(string $pluginFile, string $subDir): string
    {
        $pluginPath = plugin_dir_path($pluginFile);
        $pluginUrl = plugin_dir_url($pluginFile);
        $relativePath = str_replace($pluginPath, '', $subDir);
        return trailingslashit($pluginUrl . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath));
    }
}
