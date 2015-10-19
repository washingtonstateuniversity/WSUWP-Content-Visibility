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
		add_filter( 'map_meta_cap', array( $this, 'allow_read_private_posts' ), 10, 4 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
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

			$groups = get_post_meta( $post->ID, '_content_visibility_groups' );

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
	 * Add the meta boxes created by the plugin.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $post_type The slug of the current post type being edited.
	 * @param WP_Post $post      The full post object being edited.
	 */
	public function add_meta_boxes( $post_type, $post ) {
		add_meta_box( 'wsuwp-content-visibility', 'Content Visibility', array( $this, 'display_visibility_meta_box' ), null, 'side', 'high' );
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
	 * Enqueue Javascript required in the admin.
	 *
	 * @since 0.1.0
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'wsuwp-ad-style', plugins_url( 'css/admin-style.min.css', __FILE__ ), array(), false );
		wp_enqueue_script( 'wsuwp-ad-group-view', plugins_url( 'js/content-visibility.min.js', __FILE__ ), array( 'backbone' ), false, true );
		$data = get_post_meta( get_the_ID(), '_content_visibility_groups', true );
		$ajax_nonce = wp_create_nonce( 'wsu-sso-ad-groups' );
		wp_localize_script( 'wsuwp-ad-group-view', 'wsuADGroups', $data );
		wp_localize_script( 'wsuwp-ad-group-view', 'wsuADGroups_nonce', $ajax_nonce );
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