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
		add_filter( 'user_has_cap', array( $this, 'allow_edit_content' ), 10, 4 );

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
	public function allow_edit_content( $allcaps, $caps, $args, $user ) {
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
			// @todo fix this whole area to be in WSUWP SSO
			$user_ad_groups = WSUWP_SSO_Authentication()->get_user_ad_groups( $user );
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

	/**
	 * Add the meta boxes created by the plugin to supporting post types.
	 *
	 * @todo capability checks
	 *
	 * @since 0.1.0
	 *
	 * @param string  $post_type The slug of the current post type being edited.
	 * @param WP_Post $post      The current post.
	 */
	public function add_meta_boxes( $post_type, $post ) {
		if ( post_type_supports( $post_type, 'wsuwp-content-visibility' ) ) {
			add_meta_box( 'wsuwp-content-editors-box', 'Content Editors', array( $this, 'display_editors_meta_box' ), null, 'side', 'high' );

			if ( 'private' === $post->post_status ) {
				add_meta_box( 'wsuwp-content-viewers-box', 'Content Viewers', array( $this, 'display_viewers_meta_box' ), null, 'side', 'high' );
			}
		}
	}

	/**
	 * Display the meta box used to determine which groups of users can view a piece of content.
	 *
	 * @param WP_Post $post
	 */
	public function display_viewers_meta_box( $post ) {
		?>
		<p class="description">Manage authorized viewers of this content.</p>
		<?php

		/**
		 * Filter the default groups that will always display in the interface.
		 *
		 * @since 1.0.0
		 *
		 * @param array $group_details Array of details, containing only basic information by default.
		 */
		$default_groups = apply_filters( 'content_visibility_default_groups', array() );

		$viewer_groups = (array) get_post_meta( $post->ID, '_content_visibility_viewer_groups', true );

		foreach ( $default_groups as $group ) {
			$viewer_selected = false;

			if ( in_array( $group['id'], $viewer_groups, true ) ) {
				$viewer_selected = true;
			}

			$group_details = array(
				'id' => $group['id'],
				'display_name' => $group['name'],
			);

			/**
			 * Filter the details associated with assigned visibility groups.
			 *
			 * @since 0.1.0
			 *
			 * @param array $group_details Array of details, containing only basic information by default.
			 */
			$group_details = apply_filters( 'content_visibility_group_details', $group_details );

			?>
			<div class="content-visibility-group-selection">
				<input type="checkbox" id="view_<?php echo esc_attr( $group_details['id'] ); ?>" name="content_view[<?php echo esc_attr( $group_details['id'] ); ?>]" <?php checked( $viewer_selected ); ?>>
				<label for="view_<?php echo esc_attr( $group_details['id'] ); ?>"><?php echo esc_html( $group_details['display_name'] ); ?></label>
			</div>
			<?php
		}
	}

	/**
	 * Display the meta box used to determine which groups of users can edit a piece of content.
	 *
	 * @param WP_Post $post
	 */
	public function display_editors_meta_box( $post ) {
		?>
		<p class="description">Manage authorized editors of this content.</p>
		<?php
		wp_nonce_field( 'save-content-visibility', '_content_visibility_nonce' );

		/**
		 * Filter the default groups that will always display in the interface.
		 *
		 * @since 1.0.0
		 *
		 * @param array $group_details Array of details, containing only basic information by default.
		 */
		$default_groups = apply_filters( 'content_visibility_default_groups', array() );

		$editor_groups = (array) get_post_meta( $post->ID, '_content_visibility_editor_groups', true );

		foreach ( $default_groups as $group ) {
			$editor_selected = false;

			if ( in_array( $group['id'], $editor_groups, true ) ) {
				$editor_selected = true;
			}

			$group_details = array(
				'id' => $group['id'],
				'display_name' => $group['name'],
			);

			/**
			 * Filter the details associated with assigned visibility groups.
			 *
			 * @since 0.1.0
			 *
			 * @param array $group_details Array of details, containing only basic information by default.
			 */
			$group_details = apply_filters( 'content_visibility_group_details', $group_details );

			?>
			<div class="content-visibility-group-selection">
				<input type="checkbox" id="edit_<?php echo esc_attr( $group_details['id'] ); ?>" name="content_edit[<?php echo esc_attr( $group_details['id'] ); ?>]" <?php checked( $editor_selected ); ?>>
				<label for="edit_<?php echo esc_attr( $group_details['id'] ); ?>"><?php echo esc_html( $group_details['display_name'] ); ?></label>
			</div>
			<?php
		}
	}

	/**
	 * Save viewer and editor group data associated with a post.
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

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		//@todo Add a hidden input that is always submitted with the meta box so that we don't rely on possibly missing checkboxes

		if ( ! isset( $_POST['_content_visibility_nonce'] ) || ! wp_verify_nonce( $_POST['_content_visibility_nonce'], 'save-content-visibility' ) ) {
			return;
		}

		/**
		 * Filter the default groups that will always display in the interface.
		 *
		 * @since 1.0.0
		 *
		 * @param array $group_details Array of details, containing only basic information by default.
		 */
		$default_groups = apply_filters( 'content_visibility_default_groups', array() );
		$default_group_ids = wp_list_pluck( $default_groups, 'id' );

		if ( 'private' === $post->post_status ) {
			$content_viewer_ids = isset( $_POST['content_view'] ) ? (array) $_POST['content_view'] : array();
			$save_groups = array();

			foreach ( $content_viewer_ids as $content_viewer => $v ) {
				if ( in_array( $content_viewer, $default_group_ids, true ) ) {
					$save_groups[] = $content_viewer;
				}
			}

			update_post_meta( $post_id, '_content_visibility_viewer_groups', $save_groups );
		}

		$content_editor_ids = isset( $_POST['content_edit'] ) ? (array) $_POST['content_edit'] : array();
		$save_groups = array();

		foreach ( $content_editor_ids as $content_editor => $v ) {
			if ( in_array( $content_editor, $default_group_ids, true ) ) {
				$save_groups[] = $content_editor;
			}
		}

		update_post_meta( $post_id, '_content_visibility_editor_groups', $save_groups );
	}
}
