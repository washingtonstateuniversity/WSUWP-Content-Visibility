/* global Backbone, jQuery, _ */
var wsuADVisibility = wsuADVisibility || {};

(function (window, Backbone, $, _, wsuADVisibility) {
    'use strict';

    wsuADVisibility.group = Backbone.Model.extend({
        default: {
            groupID: '',
            groupName: '',
            memberCount: 0,
            memberList: '',
            selectedClass: ''
        }
    });

    wsuADVisibility.group.prototype.sync = function () { return null; };
    wsuADVisibility.group.prototype.fetch = function () { return null; };
    wsuADVisibility.group.prototype.save = function () { return null; };
})(window, Backbone, jQuery, _, wsuADVisibility);
