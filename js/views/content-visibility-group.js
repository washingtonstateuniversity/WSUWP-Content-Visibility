/* global Backbone, jQuery, _ */
var wsuContentViewers = wsuContentViewers || {};

( function ( window, Backbone, $, _, wsuContentViewers ) {
	'use strict';

	wsuContentViewers.groupView = Backbone.View.extend({
		// Cache the template function for a single item.
		template: _.template( $('#visibility-group-template').html() ),

		/**
		 * Render the output of a group item in its list.
		 *
		 * @returns {wsuContentViewers.groupView}
		 */
		render: function() {
			this.$el.html( this.template( this.model.attributes ) );
			return this;
		}
	});
})(window, Backbone, jQuery, _, wsuContentViewers);
