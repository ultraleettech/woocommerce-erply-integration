/**
 * Ultraleet WP Settings core class.
 *
 * @package ultraleet/wp-fields
 */
(function(window) {
    /**
     * Class Settings constructor.
     *
     * @constructor
     */
    function Settings() {
        this.fields = {};
    }

    /**
     * Getter for page fields.
     *
     * @param name
     * @returns {*}
     */
    Settings.prototype.getField = function(name) {
        return this.fields[name];
    };

    /**
     * Setter for page fields.
     *
     * @param name
     * @param config
     * @returns {Settings}
     */
    Settings.prototype.addField = function(name, config) {
        this.fields[name] = new Field(config);
        return this;
    };

    /**
     * Bulk setter for page fields.
     *
     * @param values
     * @returns {Settings}
     */
    Settings.prototype.setFields = function(values) {
        var self = this;
        $.each(values, function(name, value) {
            self.addField(name, value);
        });
        return this;
    };

    /**
     * Create singleton instance of the class.
     */
    if (!window.hasOwnProperty('ULWP')) {
        window.ULWP = {};
    }
    if (!window.ULWP.hasOwnProperty('settings')) {
        window.ULWP.settings = new Settings();
    }
})(window);
