/* global Backbone, jQuery, _ */
var wsuContentVisibility = wsuContentVisibility || {};

(function (window, Backbone, $, _, wsuContentVisibility) {
	'use strict';

	wsuContentVisibility.appView = Backbone.View.extend({
		// We provide this container by adding a meta box in WordPress.
		el: '#wsuwp-content-visibility',

		// Setup the events used in the overall application view.
		events: {
			'click #visibility-group-manage': 'openGroupModal',
			'click #visibility-group-search': 'searchGroups',
			'click #visibility-save-groups': 'saveGroups',
			'click #visibility-cancel-groups': 'cancelGroupSearch',
			'click .visibility-group-overlay-close': 'closeGroups',
			'click .visibility-group-single': 'toggleMemberList',
			'click .visibility-group-select': 'toggleGroupSelection',
			'keydown #visibility-search-term' : 'searchGroups',

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
			this.groups = $.map(window.wsuVisibilityGroups, function(el) { return el; });
			this.groups_modified = this.groups;
		},

		/**
		 * Create a view for individual groups retrieved via our group search.
		 *
		 * @param group Group to add to the list.
		 * @param area  Area representing the list group is being added to.
		 */
		addOne: function( group, area ) {
			var view = new wsuContentVisibility.groupView({ model: group });

			$('#visibility-' + area + '-group-list').find('.visibility-group-results').append( view.render().el );
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
				'action': 'get_content_visibility_groups',
				'_ajax_nonce' : wsuVisibilityGroups_nonce,
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
						var group = new wsuContentVisibility.group( {
							groupID: response_data[ item ].id,
							groupName: response_data[ item ].display_name,
							memberCount: response_data[ item ].member_count,
							memberList: response_data[ item ].member_list,
							selectedClass: response_data[ item ].selected_class
						});
						new_groups.push( response_data[ item ].id );
						wsuContentVisibility.app.addOne( group, 'current' );
					});
					this.groups = new_groups;
				}
			});
		},

		removeSearchSpinner: function() {
			var $vis_group_results = $('.visibility-group-results');

			if ( $vis_group_results.hasClass('pending-results' )) {
				$vis_group_results.removeClass('pending-results');
			}
		},

		showSearchSpinner: function() {
			var $vis_group_results = $('.visibility-group-results');

			if ( false === $vis_group_results.hasClass('pending-results') ) {
				$vis_group_results.addClass('pending-results');
			}
		},

		/**
		 * Search Active Directory for groups matching the given parameters.
		 *
		 * @param evt
		 */
		searchGroups: function(evt) {
			// Watch for and handle enter key initialized searches.
			if ( 'keydown' === evt.type && 13 === evt.keyCode ) {
				evt.preventDefault();
				evt.stopPropagation();
			} else if ( 'keydown' === evt.type ) {
				return true;
			}

			this.showSearchSpinner();
			this.showSearchList();

			var data = {
				'action': 'search_content_visibility_groups',
				'_ajax_nonce' : wsuVisibilityGroups_nonce,
				'post_id': $('#post_ID').val(),
				'visibility_group': $('#visibility-search-term').val()
			};

			var response_data;

			$.post(ajaxurl, data, function(response) {
				wsuContentVisibility.app.removeSearchSpinner();

				if ( response['success'] === false ) {
					$('.visibility-group-results').html('<div class="no-group-results">Error: ' + response['data'] + '</div>' );
				} else {
					response_data = response['data'];

					if ( 0 === response_data.length ) {
						$('.visibility-group-results').html('<div class="no-group-results">No matching results...</div>' );
					} else {
						$( response_data).each( function( item ) {
							var group = new wsuContentVisibility.group( {
								groupID: response_data[ item ].id,
								groupName: response_data[ item ].display_name,
								memberCount: response_data[ item ].member_count,
								memberList: response_data[ item ].member_list,
								selectedClass: response_data[ item ].selected_class
							});
							wsuContentVisibility.app.addOne( group, 'find' );
						});
					}
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
			this.$('#visibility-search-term').val('');
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
				'action': 'set_content_visibility_groups',
				'_ajax_nonce' : wsuVisibilityGroups_nonce,
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
		 * @param response
		 */
		saveGroupResponse: function( response ) {
			wsuContentVisibility.app.getCurrentGroups();
			wsuContentVisibility.app.showCurrentList();
			wsuContentVisibility.app.clearCurrentList();
			wsuContentVisibility.app.clearSearchList();
			wsuContentVisibility.app.$('#visibility-search-term').val('');
		},

		/**
		 * Show and hide the overlay used to display results from
		 * the AD group search.
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
		 * @param evt
		 */
		toggleMemberList: function(evt) {
			var $target = $(evt.target);

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
		 * @param evt
		 */
		toggleGroupSelection: function(evt) {
			var $target = $(evt.target),
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
		 */
		clearSearchList: function() {
			this.$('#visibility-find-group-list .visibility-group-results').html('');
		},

		/**
		 * Remove any values from the current group lists when hiding the modal to
		 * prep for possible future use.
		 */
		clearCurrentList: function() {
			this.$('#visibility-current-group-list .visibility-group-results').html('');
		},

		/**
		 * Show the search list after a search. If the search results tab is already
		 * open, clear the current results to make room for the new search results.
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
		 * Toggle the display of the current and search lists of AD groups inside
		 * the overlay.
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
		 */
		closeGroups: function() {
			this.toggleOverlay();
			this.clearSearchList();
			this.clearCurrentList();
			this.$('#visibility-search-term').val('');
		}
	});
})(window, Backbone, jQuery, _, wsuContentVisibility);