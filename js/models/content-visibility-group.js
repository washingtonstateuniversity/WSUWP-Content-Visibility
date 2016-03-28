/* global Backbone, jQuery, _ */
var wsuContentViewers = wsuContentViewers || {};

( function ( window, Backbone, $, _, wsuContentViewers ) {
	'use strict';

	wsuContentViewers.group = Backbone.Model.extend({
		default: {
			groupID: '',
			groupName: '',
			memberCount: 0,
			memberList: '',
			selectedClass: ''
		}
	});

	wsuContentViewers.group.prototype.sync = function () { return null; };
	wsuContentViewers.group.prototype.fetch = function () { return null; };
	wsuContentViewers.group.prototype.save = function () { return null; };
})(window, Backbone, jQuery, _, wsuContentViewers);
