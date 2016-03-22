<?php

class WSUWP_Content_Visibility {
	/**
	 * @var string Version number to append to enqueued assets.
	 */
	var $script_version = '0.1.0';

	/**
	 * @var WSUWP_Content_Visibility
	 */
	private static $instance;

	/**
	 * Maintain and return the one instance of the plugin. Initiate hooks when
	 * called the first time.
	 *
	 * @since 0.1.0
	 *
	 * @return \WSUWP_Content_Visibility
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSUWP_Content_Visibility();
			self::$instance->setup_hooks();
		}
		return self::$instance;
	}

	/**
	 * Setup hooks to fire when the plugin is first initialized.
	 *
	 * @since 0.1.0
	 */
	public function setup_hooks() {
		add_action( 'init', array( $this, 'add_post_type_support' ), 10 );

		add_filter( 'map_meta_cap', array( $this, 'allow_read_private_posts' ), 10, 4 );
		add_filter( 'user_has_cap', array( $this, 'ad_groups_edit_page' ), 10, 4 );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_get_content_visibility_groups', array( $this, 'ajax_get_viewer_groups' ) );
		add_action( 'wp_ajax_set_content_visibility_groups', array( $this, 'ajax_set_viewer_groups' ) );
		add_action( 'wp_ajax_search_content_visibility_groups', array( $this, 'ajax_search_viewer_groups' ) );

		add_action( 'wp_ajax_wsuwp_ad_group_check', array( $this, 'ajax_get_editor_groups' ) );
		add_action( 'wp_ajax_wsuwp_ad_group_save', array( $this, 'ajax_set_editor_groups' ) );
		add_action( 'wp_ajax_wsuwp_get_ad_groups', array( $this, 'ajax_search_editor_groups' ) );
	}

	/**
	 * Add support for WSUWP Content Visibility to built in post types.
	 *
	 * @since 0.1.0
	 */
	public function add_post_type_support() {
		add_post_type_support( 'post', 'wsuwp-content-viewers' );
		add_post_type_support( 'page', 'wsuwp-content-viewers' );
		add_post_type_support( 'post', 'wsuwp-content-editors' );
		add_post_type_support( 'page', 'wsuwp-content-editors' );
	}

	/**
	 * Manage capabilities allowing those other than a post's author to read a private post.
	 *
	 * By default, a post's author has private post capabilities for this post, so we return
	 * the current capabilities untouched.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $caps    List of capabilities.
	 * @param string $cap     The primitive capability.
	 * @param int    $user_id ID of the user.
	 * @param array  $args    Additional data, contains post ID.
	 * @return array Updated list of capabilities.
	 */
	public function allow_read_private_posts( $caps, $cap, $user_id, $args ) {
		if ( ( 'read_post' === $cap && ! isset( $caps['read_post'] ) ) || ( 'read_page' === $cap && ! isset( $caps['read_page'] ) ) ) {
			$post = get_post( $args[0] );

			if ( 'private' !== $post->post_status ) {
				return $caps;
			}

			// Post authors can view their own posts.
			if ( (int) $post->post_author === $user_id ) {
				return $caps;
			}

			$groups = get_post_meta( $post->ID, '_content_visibility_groups', true );

			// No content visible groups have been assigned to this post.
			if ( empty( $groups ) ) {
				return $caps;
			}

			/**
			 * Filter whether a user is a member of the allowed groups to view this private content.
			 *
			 * @since 0.1.0
			 *
			 * @param bool  $value   Default false. True if the user is a member of the passed groups. False if not.
			 * @param int   $user_id ID of the user attempting to view content.
			 * @param array $groups  List of allowed groups attached to a post.
			 */
			if ( false === apply_filters( 'user_in_content_visibility_groups', false, $user_id, $groups ) ) {
				return $caps;
			}

			$post_type = get_post_type_object( $post->post_type );

			$caps_keys = array_keys( $caps, $post_type->cap->read_private_posts );

			if ( 1 === count( $caps_keys ) ) {
				$caps = array( $post_type->cap->read );
			} else {
				foreach ( $caps_keys as $k => $v ) {
					unset( $caps[ $v ] );
				}
				$caps[] = $post_type->cap->read;
				$caps = array_values( $caps );
			}
		}

		return $caps;
	}

	/**
	 * Add the meta boxes created by the plugin to supporting post types.
	 *
	 * @todo capability checks
	 *
	 * @since 0.1.0
	 *
	 * @param string  $post_type The slug of the current post type being edited.
	 */
	public function add_meta_boxes( $post_type ) {
		if ( post_type_supports( $post_type, 'wsuwp-content-viewers' ) ) {
			add_meta_box( 'wsuwp-content-visibility', 'Content Visibility', array( $this, 'display_visibility_meta_box' ), null, 'side', 'high' );
		}

		if ( post_type_supports( $post_type, 'wsuwp-content-editors' ) ) {
			add_meta_box( 'wsuwp-sso-ad-group', 'Editor Groups', array( $this, 'display_group_visibility_meta_box' ), null, 'side', 'high' );
		}
	}

	/**
	 * Display the meta box used to assign and maintain visibility for users and groups.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post $post The current post being edited.
	 */
	public function display_visibility_meta_box( $post ) {
		?>
		<p class="description">Groups and users with access to view this content.</p>
		<input type="button" id="visibility-group-manage" class="primary button" value="Manage Groups" />
		<div class="visibility-group-overlay">
			<div class="visibility-group-overlay-wrapper">
				<div class="visibility-group-overlay-header">
					<div class="visibility-group-overlay-title">
						<h3>Manage Groups</h3>
					</div>
					<div class="visibility-group-overlay-close">Close</div>
				</div>
				<div class="visibility-group-overlay-body">
					<div class="visibility-group-search-area">
						<input type="text" id="visibility-search-term" name="visibility_search_term" class="widefat" />
						<input type="button" id="visibility-group-search" class="button button-primary button-large" value="Find" />
					</div>
					<div class="visibility-save-cancel">
						<input type="button" id="visibility-save-groups" class="button button-primary button-large" value="Save" />
						<input type="button" id="visibility-cancel-groups" class="button button-secondary button-large" value="Cancel" />
					</div>
					<div id="visibility-current-group-list" class="visibility-group-list visibility-group-list-open">
						<div id="visibility-current-group-tab" class="visibility-group-tab visibility-current-tab">Currently Assigned</div>
						<div class="visibility-group-results"></div>
					</div>
					<div id="visibility-find-group-list" class="visibility-group-list">
						<div id="visibility-find-group-tab" class="visibility-group-tab">Group Results</div>
						<div class="visibility-group-results pending-results"></div>"
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
		<script type="text/template" id="visibility-group-template">
			<div class="visibility-group-single">
				<div class="visibility-group-select <%= selectedClass %>" data-group-id="<%= groupID %>"></div>
				<div class="visibility-group-name"><%= groupName %></div>
				<div class="visibility-group-member-count">(<%= memberCount %> members)</div>
				<ul class="visibility-group-members">
					<% for(var member in memberList) { %>
					<li><%= memberList[member] %></li>
					<% } %>
				</ul>
				<div class="clear"></div>
			</div>
		</script>
		<?php
	}

	/**
	 * Display the meta box used to search for an AD group.
	 *
	 * @param WP_Post $post The current post being edited.
	 */
	public function display_group_visibility_meta_box( $post ) {
		?>
		<p class="description">Groups from Active Directory with editor access to this page.</p>
		<input type="button" id="wsu-group-manage" class="primary button" value="Manage Groups" />
		<div class="ad-group-overlay">
			<div class="ad-group-overlay-wrapper">
				<div class="ad-group-overlay-header">
					<div class="ad-group-overlay-title">
						<h3>Manage Active Directory Editor Groups</h3>
					</div>
					<div class="ad-group-overlay-close">Close</div>
				</div>
				<div class="ad-group-overlay-body">
					<div class="ad-group-search-area">
						<input type="text" id="wsu-group-visibility" name="wsu_group_visibility" class="widefat" />
						<input type="button" id="wsu-group-search" class="button button-primary button-large" value="Find" />
					</div>
					<div class="ad-save-cancel">
						<input type="button" id="wsu-save-groups" class="button button-primary button-large" value="Save" />
						<input type="button" id="wsu-cancel-groups" class="button button-secondary button-large" value="Cancel" />
					</div>
					<div id="ad-current-group-list" class="ad-group-list ad-group-list-open">
						<div id="ad-current-group-tab" class="ad-group-tab ad-current-tab">Currently Assigned</div>
						<div class="ad-group-results"></div>
					</div>
					<div id="ad-find-group-list" class="ad-group-list">
						<div id="ad-find-group-tab" class="ad-group-tab">Group Results</div>
						<div class="ad-group-results"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
		<script type="text/template" id="ad-group-template">
			<div class="ad-group-single">
				<div class="ad-group-select <%= selectedClass %>" data-group-id="<%= groupID %>"></div>
				<div class="ad-group-name"><%= groupName %></div>
				<div class="ad-group-member-count">(<%= memberCount %> members)</div>
				<ul class="ad-group-members">
					<% for(var member in memberList) { %>
					<li><%= memberList[member] %></li>
					<% } %>
				</ul>
				<div class="clear"></div>
			</div>
		</script>
		<?php
	}

	/**
	 * Enqueue Javascript required in the admin on support post type screens.
	 *
	 * @todo capabilities
	 *
	 * @since 0.1.0
	 */
	public function admin_enqueue_scripts() {
		if ( ! isset( get_current_screen()->post_type ) ) {
			return;
		}
		if ( ! post_type_supports( get_current_screen()->post_type, 'wsuwp-content-viewers' ) && ! post_type_supports( get_current_screen()->post_type, 'wsuwp-content-editors' ) ) {
			return;
		}

		wp_enqueue_style( 'wsuwp-content-visibility', plugins_url( 'css/admin-style.min.css', dirname( __FILE__ ) ), array(), false );
		wp_enqueue_script( 'wsuwp-content-visibility', plugins_url( 'js/content-visibility.min.js', dirname( __FILE__ ) ), array( 'backbone' ), false, true );

		$data = get_post_meta( get_the_ID(), '_content_visibility_groups', true );
		$ajax_nonce = wp_create_nonce( 'wsu-visibility-groups' );

		wp_localize_script( 'wsuwp-content-visibility', 'wsuVisibilityGroups', $data );
		wp_localize_script( 'wsuwp-content-visibility', 'wsuVisibilityGroups_nonce', $ajax_nonce );

		$data = get_post_meta( get_the_ID(), '_ad_editor_groups', true );
		$ajax_nonce = wp_create_nonce( 'wsu-sso-ad-groups' );

		wp_localize_script( 'wsuwp-content-visibility', 'wsuADGroups', $data );
		wp_localize_script( 'wsuwp-content-visibility', 'wsuADGroups_nonce', $ajax_nonce );
	}

	/**
	 * Retrieve a current list of groups attached to a post for display in the
	 * admin modal.
	 *
	 * @since 0.1.0
	 */
	public function ajax_get_groups() {
		check_ajax_referer( 'wsu-visibility-groups' );

		$post_id = absint( $_POST['post_id'] );

		if ( 0 === $post_id ) {
			wp_send_json_error( 'Invalid post ID.' );
		}

		$groups = get_post_meta( $post_id, '_content_visibility_groups', true );

		if ( empty( $groups ) ) {
			wp_send_json_success( array() );
		}

		$return_groups = array();

		foreach ( $groups as $group ) {
			$group_details = array(
				'id' => $group,
				'display_name' => $group,
				'member_count' => '',
				'member_list' => '',
			);
			/**
			 * Filter the details associated with assigned visibility groups.
			 *
			 * @since 0.1.0
			 *
			 * @param array $group_details Array of details, containing only basic information by default.
			 */
			$group_details = apply_filters( 'content_visibility_group_details', $group_details );

			// Current groups should always have the selected class enabled.
			$group_details['selected_class'] = 'visibility-group-selected';

			$return_groups[] = $group_details;
		}

		wp_send_json_success( $return_groups );
	}

	/**
	 * Save any changes made to a list of visibility groups assigned to a post.
	 *
	 * @since 0.1.0
	 */
	public function ajax_set_groups() {
		check_ajax_referer( 'wsu-visibility-groups' );

		if ( ! isset( $_POST['post_id'] ) || 0 === absint( $_POST['post_id'] ) ) {
			wp_send_json_error( 'Invalid post ID.' );
		}

		if ( ! isset( $_POST['visibility_groups'] ) || empty( $_POST['visibility_groups'] ) ) {
			wp_send_json_success( 'No changes.' );
		}

		$post_id = absint( $_POST['post_id'] );
		$group_ids = array_filter( $_POST['visibility_groups'], 'sanitize_text_field' );

		update_post_meta( $post_id, '_content_visibility_groups', $group_ids );

		wp_send_json_success( 'Changes saved.' );
	}

	/**
	 * Retrieve a list of groups matching the provided search terms from an AJAX request.
	 *
	 * @since 0.1.0
	 */
	public function ajax_search_groups() {
		check_ajax_referer( 'wsu-visibility-groups' );

		$search_text = sanitize_text_field( $_POST['visibility_group'] );

		if ( empty( $search_text ) ) {
			wp_send_json_error( 'Empty search text was submitted.' );
		}

		if ( 1 === mb_strlen( $search_text ) ) {
			wp_send_json_error( 'Please provide more than one character.' );
		}

		$post_id = absint( $_POST['post_id'] );

		if ( 0 === $post_id ) {
			$current_groups = array();
		} else {
			$current_groups = get_post_meta( $post_id, '_content_visibility_groups', true );
		}

		// Has this term been used recently?
		$groups = wp_cache_get( md5( $search_text ), 'content-visibility' );

		if ( ! $groups ) {
			/**
			 * Filter the groups attached to a search term.
			 *
			 * @since 0.1.0
			 *
			 * @param array  $groups      Group result data.
			 * @param string $search_text Text used to search for a group.
			 * @param int     $post_id     ID of the post being edited.
			 */
			$groups = apply_filters( 'content_visibility_group_search', $groups, $search_text, $post_id );

			// Cache a search term's results for an hour.
			wp_cache_add( md5( $search_text ), $groups, 'content-visibility', 3600 );
		}

		$return_groups = array();

		foreach ( $groups as $group ) {
			$group['selected_class'] = in_array( $group['id'], (array) $current_groups, true ) ? 'visibility-group-selected' : '';

			$return_groups[] = $group;
		}

		wp_send_json_success( $return_groups );
	}


	/**
	 * Handle AJAX requests for the AD groups attached to an individual post.
	 */
	public function ajax_get_ad_groups() {
		check_ajax_referer( 'wsu-sso-ad-groups' );

		$post_id = absint( $_POST['post_id'] );
		$ad_groups = (array) get_post_meta( $post_id, '_ad_editor_groups', true );

		$return_groups = array();

		foreach ( $ad_groups as $group ) {
			$this_group = array();

			// We may have a cached record of the AD group containing more detailed information.
			// If not, we just use the DN for both DN and display name.
			if ( $cached_group = wp_cache_get( md5( $group ), 'wsuwp-ad' ) ) {
				$this_group = $cached_group;
			} else {
				$this_group['dn'] = $group;
				$this_group['display_name'] = $group;
			}

			$this_group['selected_class'] = 'ad-group-selected';
			$return_groups[] = $this_group;
		}

		echo wp_json_encode( $return_groups );
		die();
	}

	/**
	 * Search Active Directory for a list of groups matching a POSTed request.
	 */
	public function ajax_ad_group_check() {
		check_ajax_referer( 'wsu-sso-ad-groups' );

		$ad_group = sanitize_text_field( $_POST['ad_group'] );
		$ad_groups = (array) get_post_meta( absint( $_POST['post_id'] ), '_ad_editor_groups', true );

		if ( empty( $ad_group ) ) {
			wp_send_json_error( 'Empty AD group was passed.' );
		}

		$groups = wp_cache_get( md5( $ad_group ), 'wsuwp-ad-search' );
		if ( ! $groups ) {
			$groups = $this->search_groups( $ad_group );
			// Cache AD group search results for 10 minutes.
			wp_cache_add( md5( $ad_group ), $groups, 'wsuwp-ad-search', 600 );
		}

		$return_groups = array();
		// Only return groups that have members.
		foreach ( $groups as $group ) {
			if ( isset( $group['member'] ) ) {
				$group['member_count'] = $group['member']['count'];
				unset( $group['member']['count'] );
				$group['member'] = array_map( array( $this, 'clean_members' ), $group['member'] );
				$group['display_name'] = $group['name'][0];
				$group['selected_class'] = in_array( $group['dn'], $ad_groups, true ) ? 'ad-group-selected' : '';

				wp_cache_add( md5( $group['dn'] ), $group, 'wsuwp-ad', 1200 );

				$return_groups[] = $group;
			}
		}

		echo wp_json_encode( $return_groups );
		die();
	}

	/**
	 * Clean an AD member string so that only a CN is returned, not the entire result.
	 *
	 * @param string $member
	 *
	 * @return string
	 */
	public function clean_members( $member ) {
		$member = array_shift( explode( ',', $member ) );
		$member = array_pop( explode( '=', $member ) );

		return $member;
	}

	/**
	 * Handle an AJAX request to save a list of AD groups to a post.
	 */
	public function ajax_ad_group_save() {
		check_ajax_referer( 'wsu-sso-ad-groups' );

		if ( ! isset( $_POST['post_id'] ) || 0 === absint( $_POST['post_id'] ) ) {
			wp_send_json_error( 'Invalid post ID.' );
		}

		if ( ! isset( $_POST['ad_groups'] ) || empty( $_POST['ad_groups'] ) ) {
			wp_send_json_success( 'No groups passed. Success.' );
		}

		$post_id = absint( $_POST['post_id'] );
		$ad_groups = array_filter( $_POST['ad_groups'], 'sanitize_text_field' );

		update_post_meta( $post_id, '_ad_editor_groups', $ad_groups );

		wp_send_json_success( 'Groups saved.' );
	}


	/**
	 * Determine edit page capability based on the AD group a user is a member of if the
	 * page has been restricted to specific AD groups. If the user is not a site, network,
	 * or group admin, we need to check:
	 *
	 *     - edit_post, which has meta caps edit_others_pages and edit_published pages
	 *     - edit_page, which has meta caps edit_others_pages and edit_published pages
	 *     - publish_pages
	 *     - edit_others_pages is also checked on its own.
	 *
	 * @param array $allcaps All current capabilities assigned to the user.
	 * @param array $caps
	 * @param array $args    Additional capabilities being checked.
	 * @param WP_User $user  The current user being checked.
	 *
	 * @return array Modified list of capabilities assigned to the user.
	 */
	public function ad_groups_edit_page( $allcaps, $caps, $args, $user ) {
		// Catch current administrators and editors.
		$user_can_create_pages = array_intersect( array( 'administrator', 'editor' ), $user->roles );

		/*
		 * Administrators and Editors can still create new pages, but we need
		 * to ensure that others with individual page access cannot create
		 * new pages themselves.
		 */
		if ( 'create_pages' === $args[0] ) {
			if ( ! empty( $user_can_create_pages ) ) {
				$allcaps['create_pages'] = true;
			}
			return $allcaps;
		}

		/**
		 * We're a little crazy, so we'll give everyone with a role on the site
		 * access to the pages menu. Really the individual page cap checks should
		 * take care of this and it ends up being a list of pages on the site for
		 * most users.
		 */
		if ( 'edit_pages' === $args[0] ) {
			$allcaps['edit_pages'] = true;
			return $allcaps;
		}

		/**
		 * And now we check for individual page capabilities for anyone that is not an
		 * Administrator or Editor on the site. This doesn't work for Subscribers, only
		 * Contributors and Authors.
		 */
		if ( $user && empty( $user_can_create_pages ) && in_array( $args[0], array( 'edit_post', 'edit_page', 'publish_pages', 'edit_others_pages' ), true ) ) {
			$post = get_post();

			// We need a valid page before we can assign capabilities.
			if ( ! $post ) {
				return $allcaps;
			}

			// Retrieve the array of AD groups assigned to the current page.
			$page_ad_groups = get_post_meta( $post->ID, '_ad_editor_groups', true );

			// No additional AD groups have been assigned, return unaltered.
			if ( empty( $page_ad_groups ) ) {
				return $allcaps;
			}

			// The user must be an AD user for this to work.
			$user_type = get_user_meta( $user->ID, '_wsuwp_sso_user_type', true );
			if ( 'nid' !== $user_type ) {
				return $allcaps;
			}

			// Retrieve the array of AD groups assigned to the user.
			$user_ad_groups = $this->get_user_ad_groups( $user );
			$groups = array_intersect( $page_ad_groups, $user_ad_groups );

			// No access if no intersection between the allowed groups and the user's groups.
			if ( empty( $groups ) ) {
				return $allcaps;
			}

			$allcaps['edit_post'] = true;
			$allcaps['edit_page'] = true;
			$allcaps['publish_pages'] = true;
			$allcaps['edit_others_pages'] = true;
			$allcaps['edit_published_pages'] = true;

			return $allcaps;
		}

		return $allcaps;
	}
}
