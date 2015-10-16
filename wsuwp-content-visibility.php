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
	 * Setup hooks to include.
	 */
	public function setup_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Add the meta boxes created by the plugin.
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
 * @return \WSU_Content_Visibility
 */
function WSU_Content_Visibility() {
	return WSU_Content_Visibility::get_instance();
}