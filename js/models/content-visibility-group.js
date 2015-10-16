/* global Backbone, jQuery, _ */
var wsuContentVisibility = wsuContentVisibility || {};

(function (window, Backbone, $, _, wsuContentVisibility) {
	'use strict';

	wsuContentVisibility.group = Backbone.Model.extend({
		default: {
			groupID: '',
			groupName: '',
			memberCount: 0,
			memberList: '',
			selectedClass: ''
		}
	});

	wsuContentVisibility.group.prototype.sync = function () { return null; };
	wsuContentVisibility.group.prototype.fetch = function () { return null; };
	wsuContentVisibility.group.prototype.save = function () { return null; };
})(window, Backbone, jQuery, _, wsuContentVisibility);