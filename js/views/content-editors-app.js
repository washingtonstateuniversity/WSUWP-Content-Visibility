/* global Backbone, jQuery, _ */
var wsuContentEditors = wsuContentEditors || {};

( function ( window, Backbone, $, _, wsuContentEditors ) {
    'use strict';

    wsuContentEditors.appView = Backbone.View.extend({
        // We provide this container by adding a meta box in WordPress.
        el: '#wsuwp-content-editors-box',

        // Setup the events used in the overall application view.
        events: {
            'click #wsu-group-manage': 'openGroupModal',
            'click #wsu-group-search': 'searchGroups',
            'click #wsu-save-groups': 'saveGroups',
            'click #wsu-cancel-groups': 'cancelGroupSearch',
            'click .ad-group-overlay-close': 'closeGroups',
            'click .ad-group-single': 'toggleMemberList',
            'click .ad-group-select': 'toggleGroupSelection'
        },

        /**
         * Contains the groups currently selected.
         */
        groups: [],

        /**
         * Container for any group changes during a view.
         */
        groups_modified: [],

        /**
         * Initialize the view by grabbing the list of current AD groups from the DOM. These
         * groups are also stored in a "modified" array that allows us to track changes before
         * they are sent to the server.
         */
        initialize: function() {
            // Convert the JSON object we receive from the document to an array.
            this.groups = $.map(window.wsuContentEditorGroups, function(el) { return el; });
            this.groups_modified = this.groups;
        },

        /**
         * Create a view for individual groups retrieved via our group search.
         *
         * @param group Group to add to the list.
         * @param area  Area representing the list group is being added to.
         */
        addOne: function( group, area ) {
            var view = new wsuContentEditors.groupView({ model: group });

            $('#ad-' + area + '-group-list').find('.ad-group-results').append( view.render().el );
        },

        /**
         * Open the modal overlay that will be used to add, remove, and search for
         * AD groups that are added as editors to the page.
         *
         * @param evt
         */
        openGroupModal: function(evt) {
            evt.preventDefault();

            this.getCurrentGroups();
            this.toggleOverlay();
        },

        /**
         * Retrieve a list of current groups assigned to this post from the server.
         */
        getCurrentGroups: function() {
            var data = {
                'action': 'wsuwp_get_ad_groups',
                '_ajax_nonce' : wsuContentEditorGroups_nonce,
                'post_id': $('#post_ID').val()
            };

            var response_data;

            $.post(ajaxurl, data, function(response) {
                if ( response['success'] === false ) {
                    // @todo output response.data in an error message template.
                } else {
                    var new_groups = [];
                    response_data = $.parseJSON( response );
                    $( response_data).each( function( item ) {
                        var group = new wsuContentEditors.group( {
                            groupID: response_data[ item ].dn,
                            groupName: response_data[ item ].display_name,
                            memberCount: response_data[ item ].member_count,
                            memberList: response_data[ item ].member,
                            selectedClass: response_data[ item ].selected_class
                        });
                        new_groups.push( response_data[ item ].dn );
                        wsuContentEditors.app.addOne( group, 'current' );
                    });
                    this.groups = new_groups;
                }
            });
        },

        /**
         * Search Active Directory for groups matching the given parameters.
         *
         * @param evt
         */
        searchGroups: function(evt) {
            evt.preventDefault();

            this.showSearchList();

            var data = {
                'action': 'wsuwp_ad_group_check',
                '_ajax_nonce' : wsuContentEditorGroups_nonce,
                'post_id': $('#post_ID').val(),
                'ad_group': $('#wsu-group-visibility').val()
            };

            var response_data;

            $.post(ajaxurl, data, function(response) {
                if ( response['success'] === false ) {
                    // @todo Output response.data in an error message template.
                } else {
                    response_data = $.parseJSON( response );
                    $( response_data).each( function( item ) {
                        var group = new wsuContentEditors.group( {
                            groupID: response_data[ item ].dn,
                            groupName: response_data[ item ].display_name,
                            memberCount: response_data[ item ].member_count,
                            memberList: response_data[ item ].member,
                            selectedClass: response_data[ item ].selected_class
                        });
                        wsuContentEditors.app.addOne( group, 'find' );
                    });
                }
            });
        },

        /**
         * If Cancel is clicked, bail out of any search results that have
         * been processed thus far.
         *
         * @param evt
         */
        cancelGroupSearch: function(evt) {
            evt.preventDefault();

            this.groups_modified = this.groups;
            this.clearCurrentList();
            this.getCurrentGroups();
            this.showCurrentList();
            this.clearSearchList();
            this.$('#wsu-group-visibility').val('');
        },

        /**
         * Save any selected groups from the found groups results list. This is
         * treated as a close action for that group list, so clear the search
         * results as well.
         *
         * @param evt
         */
        saveGroups: function(evt) {
            evt.preventDefault();

            var data = {
                'action': 'wsuwp_ad_group_save',
                '_ajax_nonce' : wsuContentEditorGroups_nonce,
                'post_id': $('#post_ID').val(),
                'ad_groups': this.groups_modified
            };

            $.post(ajaxurl, data, this.saveGroupResponse);
        },

        /**
         * Check the response sent from the server after saving a new list of
         * groups. Once the response is verified, go back to the current list.
         *
         * @todo actually handle the response properly.
         *
         * @param response
         */
        saveGroupResponse: function( response ) {
            wsuContentEditors.app.getCurrentGroups();
            wsuContentEditors.app.showCurrentList();
            wsuContentEditors.app.clearCurrentList();
            wsuContentEditors.app.clearSearchList();
            wsuContentEditors.app.$('#wsu-group-visibility').val('');
        },

        /**
         * Show and hide the overlay used to display results from
         * the AD group search.
         */
        toggleOverlay: function() {
            var $overlay = $('.ad-group-overlay');

            if ( $overlay.hasClass('ad-group-overlay-open') ) {
                $overlay.removeClass('ad-group-overlay-open');
            } else {
                $overlay.addClass('ad-group-overlay-open');
            }
        },

        /**
         * Show and hide the group members area below each group in a list.
         *
         * @param evt
         */
        toggleMemberList: function(evt) {
            var $target = $(evt.target);

            if ( $target.is( '.ad-group-select' ) ) {
                return;
            }

            if ( ! $target.is( '.ad-group-single' ) ) {
                $target = $target.parents('.ad-group-single');
            }

            var $target_members = $target.find('.ad-group-members');

            if ( $target_members.hasClass('ad-group-members-open') ) {
                $target_members.removeClass('ad-group-members-open');
            } else {
                $target_members.addClass('ad-group-members-open');
            }
        },

        /**
         * Toggle the visual indicator showing a selected group in a list.
         *
         * @param evt
         */
        toggleGroupSelection: function(evt) {
            var $target = $(evt.target),
                data = $target.data('group-id'); // string representing the group selection.

            if ( $.inArray( data, this.groups_modified ) > -1 ) {
                this.groups_modified = _.without( this.groups_modified, data);
                $target.removeClass('ad-group-selected');
            } else {
                this.groups_modified.push(data);
                $target.addClass('ad-group-selected');
            }
        },

        /**
         * Remove any search results when an action is taken to hide the search view.
         */
        clearSearchList: function() {
            this.$('#ad-find-group-list .ad-group-results').html('');
        },

        /**
         * Remove any values from the current group lists when hiding the modal to
         * prep for possible future use.
         */
        clearCurrentList: function() {
            this.$('#ad-current-group-list .ad-group-results').html('');
        },

        /**
         * Show the search list after a search. If the search results tab is already
         * open, clear the current results to make room for the new search results.
         */
        showSearchList: function() {
            if ( $('#ad-find-group-list').hasClass('ad-group-list-open') ) {
                this.clearSearchList();
            } else {
                this.toggleLists();
            }
        },

        /**
         * Force the current groups list to appear and hide the search results.
         */
        showCurrentList: function() {
            var $current_group = $('#ad-current-group-list'),
                $find_group = $('#ad-find-group-list');

            if ( $find_group.hasClass('ad-group-list-open') ) {
                $find_group.removeClass('ad-group-list-open');
                $find_group.find('.ad-group-tab').removeClass('ad-current-tab');
                $current_group.addClass('ad-group-list-open');
                $current_group.find('.ad-group-tab').addClass('ad-current-tab');
            }
        },

        /**
         * Toggle the display of the current and search lists of AD groups inside
         * the overlay.
         */
        toggleLists: function() {
            $('.ad-group-list').each(function(){
                if ( $(this).hasClass('ad-group-list-open') ) {
                    $(this).removeClass('ad-group-list-open');
                    $(this).find('.ad-group-tab').removeClass('ad-current-tab');
                } else {
                    $(this).addClass('ad-group-list-open');
                    $(this).find('.ad-group-tab').addClass('ad-current-tab');
                }
            });
        },

        /**
         * Remove the overlay from the screen and clear the list of groups
         * when the close button is pressed.
         */
        closeGroups: function() {
            this.toggleOverlay();
            this.clearSearchList();
            this.clearCurrentList();
            this.$('#wsu-group-visibility').val('');
        }
    });
} )( window, Backbone, jQuery, _, wsuContentEditors );
