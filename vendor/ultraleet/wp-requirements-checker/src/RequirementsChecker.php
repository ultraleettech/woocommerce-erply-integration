<?php

namespace Ultraleet\WP;

/**
 * Class RequirementsChecker
 *
 * @package ultraleet/wp-requirements-checker
 */
class RequirementsChecker
{
    private $title = '';
    private $php = '7.2.0';
    private $wp = '4.9';
    private $file;
    private $plugins = [];

    /**
     * RequirementsChecker constructor.
     *
     * @param $args {
     *      @type string $title Title of your plugin.
     *      @type string $php Minimum required PHP version for your plugin.
     *      @type string $wp Minimum required WP version for your plugin.
     *      @type string $file Path to your plugin's main file.
     *      @type array $plugins Required plugins ('Plugin Title' => 'plugin-dir/plugin-file.php').
     * }
     */
    public function __construct($args)
    {
        foreach (['title', 'php', 'wp', 'file', 'plugins'] as $setting) {
            if (isset($args[$setting])) {
                $this->$setting = $args[$setting];
            }
        }
    }

    /**
     * Check if all requirements are met.
     *
     * In case requirements are not met, displays an admin notice about the mismatched version.
     * Check for the return value and only continue loading files that depend on given minimum versions
     * if this method returns true.
     *
     * @return bool
     */
    public function passes()
    {
        $passes = $this->configPasses() && $this->phpPasses() && $this->wpPasses() && $this->pluginsActive();
        if (! $passes) {
            add_action('admin_notices', [$this, 'deactivate']);
        }
        return $passes;
    }

    /**
     * Check if current version is at least the required version.
     *
     * @param string $currentVersion
     * @param string $requiredVersion
     * @return mixed
     */
    public static function isVersionAtLeast($currentVersion, $requiredVersion)
    {
        return version_compare($currentVersion, $requiredVersion, '>=');
    }

    /**
     * Deactivate the plugin when requirements are not met.
     */
    public function deactivate()
    {
        deactivate_plugins(plugin_basename($this->file));
    }

    /**
     * Check if required configuration arguments are set.
     *
     * @return bool
     */
    protected function configPasses()
    {
        if (empty($this->file) || empty($this->title)) {
            add_action('admin_notices', [$this, 'requiredConfigNotice']);
            return false;
        }
        return true;
    }

    /**
     * Display a notice when required configuration arguments are not set.
     */
    public function requiredConfigNotice()
    {
        echo '<div class="error">';
        echo "<p><strong>WP Requirements Checker</strong> requires the 'file' and 'title' arguments to be set!</p>";
        echo '</div>';
    }

    /**
     * Check for PHP version.
     *
     * @return bool
     */
    protected function phpPasses()
    {
        if (empty($this->php) || self::isVersionAtLeast(phpversion(), $this->php)) {
            return true;
        } else {
            add_action('admin_notices', [$this, 'phpVersionNotice']);
            return false;
        }
    }

    /**
     * Display a notice when PHP version requirement is not met.
     */
    public function phpVersionNotice()
    {
        echo '<div class="error">';
        echo "<p>The &#8220;" . esc_html(
                $this->title
            ) . "&#8221; plugin cannot run on PHP versions older than " . $this->php . '. Please contact your host and ask them to upgrade.</p>';
        echo '</div>';
    }

    /**
     * Check for WordPress version.
     *
     * @return bool
     */
    protected function wpPasses()
    {
        if (empty($this->wp) || self::isVersionAtLeast(get_bloginfo('version'), $this->wp)) {
            return true;
        } else {
            add_action('admin_notices', [$this, 'wpVersionNotice']);
            return false;
        }
    }

    /**
     * Display a notice when WordPress version requirement is not met.
     */
    public function wpVersionNotice()
    {
        echo '<div class="error">';
        echo "<p>The &#8220;" . esc_html(
                $this->title
            ) . "&#8221; plugin cannot run on WordPress versions older than " . $this->wp . '. Please update WordPress.</p>';
        echo '</div>';
    }

    /**
     * Check if all required plugins are active.
     *
     * @return bool
     */
    protected function pluginsActive()
    {
        foreach ($this->plugins as $title => $file) {
            if (! in_array($file, apply_filters('active_plugins', get_option('active_plugins')))) {
                update_option($this->getPluginNotActiveOptionName(), $title, false);
                add_action('admin_notices', [$this, 'pluginNotActiveNotice']);
                return false;
            }
        }
        return true;
    }

    /**
     * Generate unique option name from plugin dir name.
     *
     * @return string
     */
    protected function getPluginNotActiveOptionName()
    {
        return $this->getOptionPrefix() . '_plugin_not_active';
    }

    /**
     * Generate unique option prefix from plugin dir name.
     *
     * @return string
     */
    protected function getOptionPrefix()
    {
        $pluginBaseName = plugin_basename($this->file);
        $pluginDirName = explode('/', $pluginBaseName)[0];
        return str_replace('-', '_', $pluginDirName);
    }

    /**
     * Display a notice when one of the required plugins is not active.
     */
    public function pluginNotActiveNotice()
    {
        $requiredPlugin = esc_html(get_option($this->getPluginNotActiveOptionName()));
        echo '<div class="error">';
        echo "<p>The &#8220;" . esc_html(
                $this->title
            ) . "&#8221; plugin requires <strong>$requiredPlugin</strong> to be installed and activated.</p>";
        echo '</div>';
        delete_option($this->getPluginNotActiveOptionName());
    }
}
