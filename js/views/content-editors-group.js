/* global Backbone, jQuery, _ */
var wsuContentEditors = wsuContentEditors || {};

( function( window, Backbone, $, _, wsuContentEditors ) {
    "use strict";

    wsuContentEditors.groupView = Backbone.View.extend( {

        // Cache the template function for a single item.
        template: _.template( $( "#ad-group-template" ).html() ),

        /**
         * Render the output of a group item in its list.
         *
         * @returns {wsuContentEditors.groupView}
         */
        render: function() {
            this.$el.html( this.template( this.model.attributes ) );
            return this;
        }
    } );
} )( window, Backbone, jQuery, _, wsuContentEditors );
