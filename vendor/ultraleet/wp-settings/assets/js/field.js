/**
 * This class represents a settings field.
 *
 * @package ultraleet/wp-config
 */
(function(window) {
    /**
     * Class Field constructor.
     *
     * @constructor
     */
    function Field(config) {
        this.config = config;
    }

    /**
     * Getter for field config.
     *
     * @returns {*}
     */
    Field.prototype.getConfig = function() {
        return this.config;
    };

    /**
     * Setter for field config.
     *
     * @param value
     * @returns {Field}
     */
    Field.prototype.setConfig = function(value) {
        this.config = value;
        return this;
    };

    window.Field = Field;
})(window);
