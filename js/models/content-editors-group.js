/* global Backbone, jQuery, _ */
var wsuContentEditors = wsuContentEditors || {};

( function( window, Backbone, $, _, wsuContentEditors ) {
    "use strict";

    wsuContentEditors.group = Backbone.Model.extend( {
        default: {
            groupID: "",
            groupName: "",
            memberCount: 0,
            memberList: "",
            selectedClass: ""
        }
    } );

    wsuContentEditors.group.prototype.sync = function() { return null; };
    wsuContentEditors.group.prototype.fetch = function() { return null; };
    wsuContentEditors.group.prototype.save = function() { return null; };
} )( window, Backbone, jQuery, _, wsuContentEditors );
