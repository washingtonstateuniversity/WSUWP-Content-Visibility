<?php

class WSUWP_Content_Visibility {

	/**
	 * @var WSUWP_Content_Visibility
	 */
	private static $instance;

	/**
	 * Provide a list of default groups supported by WSU Content Visibility.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	var $default_groups = array(
		array( 'id' => 'site-member', 'name' => 'Site Members' ),
	);

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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
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

			$groups = get_post_meta( $post->ID, '_content_visibility_viewer_groups', true );

			// No content visible groups have been assigned to this post.
			if ( empty( $groups ) ) {
				return $caps;
			}

			$group_member = false;
			if ( in_array( 'site-member', $groups, true ) && is_user_member_of_blog( $user_id ) ) {
				$group_member = true;
			}

			/**
			 * Filter whether a user is a member of the allowed groups to view this private content.
			 *
			 * @since 0.1.0
			 *
			 * @param bool  $group_member Default false. True if the user is a member of the passed groups. False if not.
			 * @param int   $user_id      ID of the user attempting to view content.
			 * @param array $groups       List of allowed groups attached to a post.
			 */
			if ( false === apply_filters( 'user_in_content_visibility_groups', $group_member, $user_id, $groups ) ) {
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
	 * @since 0.1.0
	 *
	 * @param string  $post_type The slug of the current post type being edited.
	 * @param WP_Post $post      The current post.
	 */
	public function add_meta_boxes( $post_type, $post ) {
		if ( post_type_supports( $post_type, 'wsuwp-content-visibility' ) && 'private' === $post->post_status ) {
			add_meta_box( 'wsuwp-content-viewers-box', 'Content Viewers', array( $this, 'display_viewers_meta_box' ), null, 'side', 'high' );
		}
	}

	/**
	 * Display the meta box used to determine which groups of users can view a piece of content.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post
	 */
	public function display_viewers_meta_box( $post ) {
		?>
		<p class="description">Manage authorized viewers of this content.</p>
		<input type="hidden" name="visibility_viewers_box" value="1" />
		<?php
		wp_nonce_field( 'save-content-visibility', '_content_visibility_nonce' );

		/**
		 * Filter the default groups that will always display in the interface.
		 *
		 * @since 1.0.0
		 *
		 * @param array $group_details Array of details, containing only basic information by default.
		 */
		$default_groups = apply_filters( 'content_visibility_default_groups', $this->default_groups );

		$viewer_groups = (array) get_post_meta( $post->ID, '_content_visibility_viewer_groups', true );

		foreach ( $default_groups as $group ) {
			$viewer_selected = false;

			if ( in_array( $group['id'], $viewer_groups, true ) ) {
				$viewer_selected = true;
			}

			?>
			<div class="content-visibility-group-selection">
				<input type="checkbox" id="view_<?php echo esc_attr( $group['id'] ); ?>" name="content_view[<?php echo esc_attr( $group['id'] ); ?>]" <?php checked( $viewer_selected ); ?>>
				<label for="view_<?php echo esc_attr( $group['id'] ); ?>"><?php echo esc_html( $group['name'] ); ?></label>
			</div>
			<?php
		}
	}

	/**
	 * Save viewer and editor group data associated with a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! post_type_supports( $post->post_type, 'wsuwp-content-visibility' ) ) {
			return;
		}

		if ( 'private' !== $post->post_status ) {
			return;
		}

		if ( ! isset( $_POST['visibility_viewers_box'] ) || ! isset( $_POST['_content_visibility_nonce'] ) || ! wp_verify_nonce( $_POST['_content_visibility_nonce'], 'save-content-visibility' ) ) {
			return;
		}

		/**
		 * Filter the default groups that will always display in the interface.
		 *
		 * @since 1.0.0
		 *
		 * @param array $group_details Array of details, containing only basic information by default.
		 */
		$default_groups = apply_filters( 'content_visibility_default_groups', $this->default_groups );
		$default_group_ids = wp_list_pluck( $default_groups, 'id' );

		$content_viewer_ids = isset( $_POST['content_view'] ) ? (array) $_POST['content_view'] : array();
		$save_groups = array();

		foreach ( $content_viewer_ids as $content_viewer => $v ) {
			if ( in_array( $content_viewer, $default_group_ids, true ) ) {
				$save_groups[] = $content_viewer;
			}
		}

		update_post_meta( $post_id, '_content_visibility_viewer_groups', $save_groups );
	}
}
