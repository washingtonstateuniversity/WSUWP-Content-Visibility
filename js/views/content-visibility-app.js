/* global Backbone, jQuery, _ */
var wsuContentViewers = wsuContentViewers || {};

( function ( window, Backbone, $, _, wsuContentViewers ) {
	'use strict';

	wsuContentViewers.appView = Backbone.View.extend({

		/**
		 * Primary container to work with. We add this via meta box.
		 */
		el: '#wsuwp-content-viewers-box',

		/**
		 * Setup the events we manage in this plugin.
		 */
		events: {
			'click #visibility-group-manage': 'openGroupModal',
			'click #visibility-group-search': 'searchGroups',
			'click #visibility-save-groups': 'saveGroups',
			'click #visibility-cancel-groups': 'cancelGroupSearch',
			'click .visibility-group-overlay-close': 'closeGroups',
			'click .visibility-group-single': 'toggleMemberList',
			'click .visibility-group-select': 'toggleGroupSelection',
			'keydown #visibility-search-term' : 'searchGroups'
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
		 * Initialize the view by grabbing the list of current groups from the DOM. These
		 * groups are also stored in a "modified" array that allows us to track changes before
		 * they are sent to the server.
		 *
		 * @since 0.1.0
		 */
		initialize: function() {
			// Convert the JSON object we receive from the document to an array.
			this.groups = $.map(window.wsuContentViewerGroups, function(el) { return el; });
			this.groups_modified = this.groups;
		},

		/**
		 * Create a view for individual groups retrieved via our group search.
		 *
		 * @since 0.1.0
		 *
		 * @param group Group to add to the list.
		 * @param area  Area representing the list group is being added to.
		 */
		addOne: function( group, area ) {
			var view;

			view = new wsuContentViewers.groupView( { model: group } );

			$('#visibility-' + area + '-group-list').find('.visibility-group-results').append( view.render().el );
		},

		/**
		 * Open the modal overlay that will be used to add, remove, and search for
		 * groups having visibility access to this post.
		 *
		 * @since 0.1.0
		 */
		openGroupModal: function() {
			this.getCurrentGroups();
			this.toggleOverlay();
		},

		/**
		 * Retrieve a list of current groups assigned to this post from the server.
		 *
		 * @since 0.1.0
		 */
		getCurrentGroups: function() {
			var data = {
				'action': 'get_content_visibility_groups',
				'_ajax_nonce' : wsuContentViewerGroups_nonce,
				'post_id': $('#post_ID').val()
			};

			var response_data;

			$.post(ajaxurl, data, function(response) {
				if ( response['success'] === false ) {
					// @todo output response.data in an error message template.
				} else {
					var new_groups = [];
					response_data = response['data'];
					$( response_data ).each( function( item ) {
						var group = new wsuContentViewers.group( {
							groupID: response_data[ item ].id,
							groupName: response_data[ item ].display_name,
							memberCount: response_data[ item ].member_count,
							memberList: response_data[ item ].member_list,
							selectedClass: response_data[ item ].selected_class
						});
						new_groups.push( response_data[ item ].id );
						wsuContentViewers.app.addOne( group, 'current' );
					});
					this.groups = new_groups;
				}
			});
		},

		/**
		 * Remove the group results spinner class if it is currently assigned.
		 *
		 * @since 0.1.0
		 */
		removeSearchSpinner: function() {
			var $vis_group_results = $('.visibility-group-results');

			if ( $vis_group_results.hasClass('pending-results' )) {
				$vis_group_results.removeClass('pending-results');
			}
		},

		/**
		 * Show the group results spinner class if it isn't yet assigned.
		 *
		 * @since 0.1.0
		 */
		showSearchSpinner: function() {
			var $vis_group_results = $('.visibility-group-results');

			if ( false === $vis_group_results.hasClass('pending-results') ) {
				$vis_group_results.addClass('pending-results');
			}
		},

		/**
		 * Send search terms to the server to look for groups matching the given parameters.
		 *
		 * @since 0.1.0
		 *
		 * @param {object} e A click or keydown event.
		 */
		searchGroups: function(e) {
			// Watch for and handle enter key initialized searches.
			if ( 'keydown' === e.type && 13 === e.keyCode ) {
				e.preventDefault();
				e.stopPropagation();
			} else if ( 'keydown' === e.type ) {
				return true;
			}

			this.showSearchSpinner();
			this.showSearchList();

			var data = {
				'action': 'search_content_visibility_groups',
				'_ajax_nonce' : wsuContentViewerGroups_nonce,
				'post_id': $('#post_ID').val(),
				'visibility_group': $('#visibility-search-term').val()
			};

			var response_data;

			$.post(ajaxurl, data, function(response) {
				wsuContentViewers.app.removeSearchSpinner();

				if ( response['success'] === false ) {
					$('.visibility-group-results').html('<div class="no-group-results">Error: ' + response['data'] + '</div>' );
				} else {
					response_data = response['data'];

					if ( 0 === response_data.length ) {
						$('.visibility-group-results').html('<div class="no-group-results">No matching results...</div>' );
					} else {
						$( response_data).each( function( item ) {
							var group = new wsuContentViewers.group( {
								groupID: response_data[ item ].id,
								groupName: response_data[ item ].display_name,
								memberCount: response_data[ item ].member_count,
								memberList: response_data[ item ].member_list,
								selectedClass: response_data[ item ].selected_class
							});
							wsuContentViewers.app.addOne( group, 'find' );
						});
					}
				}
			});
		},

		/**
		 * If Cancel is clicked, bail out of any search results that have
		 * been processed thus far.
		 *
		 * @since 0.1.0
		 */
		cancelGroupSearch: function() {
			this.groups_modified = this.groups;
			this.clearCurrentList();
			this.getCurrentGroups();
			this.showCurrentList();
			this.clearSearchList();
			this.$('#visibility-search-term').val('');
		},

		/**
		 * Save any selected groups from the found groups results list. This is
		 * treated as a close action for that group list, so clear the search
		 * results as well.
		 *
		 * @since 0.1.0
		 */
		saveGroups: function() {
			var data = {
				'action': 'set_content_visibility_groups',
				'_ajax_nonce' : wsuContentViewerGroups_nonce,
				'post_id': $('#post_ID').val(),
				'visibility_groups': this.groups_modified
			};

			$.post(ajaxurl, data, this.saveGroupResponse);
		},

		/**
		 * Check the response sent from the server after saving a new list of
		 * groups. Once the response is verified, go back to the current list.
		 *
		 * @todo actually handle the response properly.
		 *
		 * @since 0.1.0
		 *
		 * @param response
		 */
		saveGroupResponse: function( response ) {
			wsuContentViewers.app.getCurrentGroups();
			wsuContentViewers.app.showCurrentList();
			wsuContentViewers.app.clearCurrentList();
			wsuContentViewers.app.clearSearchList();
			wsuContentViewers.app.$('#visibility-search-term').val('');
		},

		/**
		 * Show and hide the overlay used to display results from the group search.
		 *
		 * @since 0.1.0
		 */
		toggleOverlay: function() {
			var $overlay = $('.visibility-group-overlay');

			if ( $overlay.hasClass('visibility-group-overlay-open') ) {
				$overlay.removeClass('visibility-group-overlay-open');
			} else {
				$overlay.addClass('visibility-group-overlay-open');
			}
		},

		/**
		 * Show and hide the group members area below each group in a list.
		 *
		 * @since 0.1.0
		 *
		 * @param {object} e Click event.
		 */
		toggleMemberList: function(e) {
			var $target = $(e.target);

			if ( $target.is( '.visibility-group-select' ) ) {
				return;
			}

			if ( ! $target.is( '.visibility-group-single' ) ) {
				$target = $target.parents('.visibility-group-single');
			}

			var $target_members = $target.find('.visibility-group-members');

			if ( $target_members.hasClass('visibility-group-members-open') ) {
				$target_members.removeClass('visibility-group-members-open');
			} else {
				$target_members.addClass('visibility-group-members-open');
			}
		},

		/**
		 * Toggle the visual indicator showing a selected group in a list.
		 *
		 * @since 0.1.0
		 *
		 * @param {object} e Click event.
		 */
		toggleGroupSelection: function(e) {
			var $target = $(e.target),
				data = $target.data('group-id'); // string representing the group selection.

			if ( $.inArray( data, this.groups_modified ) > -1 ) {
				this.groups_modified = _.without( this.groups_modified, data);
				$target.removeClass('visibility-group-selected');
			} else {
				this.groups_modified.push(data);
				$target.addClass('visibility-group-selected');
			}
		},

		/**
		 * Remove any search results when an action is taken to hide the search view.
		 *
		 * @since 0.1.0
		 */
		clearSearchList: function() {
			this.$('#visibility-find-group-list .visibility-group-results').html('');
		},

		/**
		 * Remove any values from the current group lists when hiding the modal to
		 * prep for possible future use.
		 *
		 * @since 0.1.0
		 */
		clearCurrentList: function() {
			this.$('#visibility-current-group-list .visibility-group-results').html('');
		},

		/**
		 * Show the search list after a search. If the search results tab is already
		 * open, clear the current results to make room for the new search results.
		 *
		 * @since 0.1.0
		 */
		showSearchList: function() {
			if ( $('#visibility-find-group-list').hasClass('visibility-group-list-open') ) {
				this.clearSearchList();
			} else {
				this.toggleLists();
			}
		},

		/**
		 * Force the current groups list to appear and hide the search results.
		 *
		 * @since 0.1.0
		 */
		showCurrentList: function() {
			var $current_group = $('#visibility-current-group-list'),
				$find_group = $('#visibility-find-group-list');

			if ( $find_group.hasClass('visibility-group-list-open') ) {
				$find_group.removeClass('visibility-group-list-open');
				$find_group.find('.visibility-group-tab').removeClass('visibility-current-tab');
				$current_group.addClass('visibility-group-list-open');
				$current_group.find('.visibility-group-tab').addClass('visibility-current-tab');
			}
		},

		/**
		 * Toggle the display of the current and search lists of groups inside
		 * the overlay.
		 *
		 * @since 0.1.0
		 */
		toggleLists: function() {
			$('.visibility-group-list').each(function(){
				if ( $(this).hasClass('visibility-group-list-open') ) {
					$(this).removeClass('visibility-group-list-open');
					$(this).find('.visibility-group-tab').removeClass('visibility-current-tab');
				} else {
					$(this).addClass('visibility-group-list-open');
					$(this).find('.visibility-group-tab').addClass('visibility-current-tab');
				}
			});
		},

		/**
		 * Remove the overlay from the screen and clear the list of groups
		 * when the close button is pressed.
		 *
		 * @since 0.1.0
		 */
		closeGroups: function() {
			this.toggleOverlay();
			this.clearSearchList();
			this.clearCurrentList();
			this.$('#visibility-search-term').val('');
		}
	});
} )( window, Backbone, jQuery, _, wsuContentViewers );
