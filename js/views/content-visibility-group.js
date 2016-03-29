/* global Backbone, _ */
var wsuContentVisibility = wsuContentVisibility || {};

( function( window, Backbone, $, _, wsuContentVisibility ) {
	"use strict";

	wsuContentVisibility.groupView = Backbone.View.extend( {

		// Cache the template function for a single item.
		template: _.template( $( "#visibility-group-template" ).html() ),

		/**
		 * Render the output of a group item in its list.
		 *
		 * @returns {wsuContentVisibility.groupView}
		 */
		render: function() {
			this.$el.html( this.template( this.model.attributes ) );
			return this;
		}
	} );
} )( window, Backbone, jQuery, _, wsuContentVisibility );
