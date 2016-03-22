/* global Backbone, jQuery, _ */
var wsuADVisibility = wsuADVisibility || {};

(function (window, Backbone, $, _, wsuADVisibility) {
    'use strict';

    wsuADVisibility.groupView = Backbone.View.extend({
        // Cache the template function for a single item.
        template: _.template( $('#ad-group-template').html() ),

        /**
         * Render the output of a group item in its list.
         *
         * @returns {wsuADVisibility.groupView}
         */
        render: function() {
            this.$el.html( this.template( this.model.attributes ) );
            return this;
        }
    });
})(window, Backbone, jQuery, _, wsuADVisibility);
