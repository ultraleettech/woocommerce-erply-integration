/**
 * WcErply plugin core JS class.
 *
 * @package Ultraleet\WcErply
 */
(function(window, $) {
    /**
     * Class WcErply constructor.
     *
     * @constructor
     */
    function WcErply() {
        this.variables = {};
        this.settings = {};
    }

    /**
     * Getter for plugin variables.
     *
     * @param name
     * @returns {*}
     */
    WcErply.prototype.get = function(name) {
        return this.variables[name];
    };

    /**
     * Setter for plugin variables.
     *
     * @param name
     * @param value
     * @returns {WcErply}
     */
    WcErply.prototype.set = function(name, value) {
        this.variables[name] = value;
        return this;
    };

    /**
     * Getter for page settings.
     *
     * @param name
     * @returns {*}
     */
    WcErply.prototype.getSetting = function(name) {
        return this.settings[name];
    };

    /**
     * Setter for page settings.
     *
     * @param name
     * @param value
     * @returns {WcErply}
     */
    WcErply.prototype.setSetting = function(name, value) {
        this.settings[name] = value;
        return this;
    };

    /**
     * Bulk setter for page settings.
     *
     * @param values
     * @returns {WcErply}
     */
    WcErply.prototype.setSettings = function(values) {
        var self = this;
        $.each(values, function(name, value) {
            self.setSetting(name, value);
        });
        return this;
    };

    /**
     * Create singleton instance of the class.
     */
    if (!window.hasOwnProperty('WcErply')) {
        window.WcErply = new WcErply();
    }
})(window, jQuery);
