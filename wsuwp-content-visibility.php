<?php
/*
Plugin Name: WSU Content Visibility
Plugin URI: https://web.wsu.edu/
Description: Control the visibility of content for authenticated users.
Author: washingtonstateuniversity, jeremyfelt
Version: 0.0.0
*/

class WSU_Content_Visibility {
	/**
	 * @var WSU_Content_Visibility
	 */
	private static $instance;

	/**
	 * Maintain and return the one instance and initiate hooks when
	 * called the first time.
	 *
	 * @since 0.1.0
	 *
	 * @return \WSU_Content_Visibility
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSU_Content_Visibility();
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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_get_content_visibility_groups', array( $this, 'ajax_get_groups' ) );
		add_action( 'wp_ajax_set_content_visibility_groups', array( $this, 'ajax_set_groups' ) );
		add_action( 'wp_ajax_search_content_visibility_groups', array( $this, 'ajax_search_groups' ) );
	}

	/**
	 * Add support for WSUWP Content Visibility to built in post types.
	 *
	 * @since 0.1.0
	 */
	public function add_post_type_support() {
		add_post_type_support( 'post', 'wsuwp-content-visibility' );
		add_post_type_support( 'page', 'wsuwp-content-visibility' );
	}

	/**
	 * Manage capabilities allowing those other than a post's author to read a private post.
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
				foreach( $caps_keys as $k => $v ) {
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
	 * @since 0.1.0
	 *
	 * @param string  $post_type The slug of the current post type being edited.
	 */
	public function add_meta_boxes( $post_type ) {
		if ( post_type_supports( $post_type, 'wsuwp-content-visibility' ) ) {
			add_meta_box( 'wsuwp-content-visibility', 'Content Visibility', array( $this, 'display_visibility_meta_box' ), null, 'side', 'high' );
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
						<input type="text" id="wsu-group-visibility" name="wsu_group_visibility" class="widefat" />
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
						<div class="visibility-group-results"></div>
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
	 * Enqueue Javascript required in the admin on support post type screens.
	 *
	 * @since 0.1.0
	 */
	public function admin_enqueue_scripts() {
		if ( ! isset( get_current_screen()->post_type ) || ! post_type_supports( get_current_screen()->post_type, 'wsuwp-content-visibility' ) ) {
			return;
		}

		wp_enqueue_style( 'wsuwp-content-visibility', plugins_url( 'css/admin-style.min.css', __FILE__ ), array(), false );
		wp_enqueue_script( 'wsuwp-content-visibility', plugins_url( 'js/content-visibility.min.js', __FILE__ ), array( 'backbone' ), false, true );

		$data = get_post_meta( get_the_ID(), '_content_visibility_groups', true );
		$ajax_nonce = wp_create_nonce( 'wsu-visibility-groups' );

		wp_localize_script( 'wsuwp-content-visibility', 'wsuVisibilityGroups', $data );
		wp_localize_script( 'wsuwp-content-visibility', 'wsuVisibilityGroups_nonce', $ajax_nonce );
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

		foreach( $groups as $group ) {
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

		foreach( $groups as $group ) {
			$group['selected_class'] = in_array( $group['id'], $current_groups ) ? 'visibility-group-selected' : '';

			$return_groups[] = $group;
		}

		wp_send_json_success( $return_groups );
	}
}

add_action( 'after_setup_theme', 'WSU_Content_Visibility' );
/**
 * Start things up.
 *
 * @since 0.1.0
 *
 * @return \WSU_Content_Visibility
 */
function WSU_Content_Visibility() {
	return WSU_Content_Visibility::get_instance();
}