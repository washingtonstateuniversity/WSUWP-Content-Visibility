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
		array( 'id' => 'site-member', 'name' => 'Members of this site' ),
	);

	/**
	 * Whether to trigger a redirect if a 404 page is reached.
	 *
	 * @var bool
	 */
	var $private_redirect = false;

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
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'add_visibility_selection' ) );
		add_action( 'init', array( $this, 'add_post_type_support' ), 11 );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10 );
		add_filter( 'user_has_cap', array( $this, 'allow_read_private_posts' ), 10, 4 );
		add_action( 'template_redirect', array( $this, 'template_redirect' ), 9 );
		add_filter( 'posts_results', array( $this, 'setup_trick_404_redirect' ) );
	}

	/**
	 * Enqueue the scripts and styles used in the admin interface.
	 *
	 * @since 1.0.0
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || ! post_type_supports( $screen->post_type, 'wsuwp-content-visibility' ) ) {
			return;
		}

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$min = '';
		} else {
			$min = '.min';
		}

		wp_enqueue_style( 'content-visibility-admin', plugins_url( 'css/admin' . $min . '.css', dirname( __FILE__ ) ), array(), false );
		wp_enqueue_script( 'content-visibility-selection', plugins_url( 'js/post-admin' . $min . '.js', dirname( __FILE__ ) ), array( 'jquery' ), false, true );
		wp_localize_script( 'content-visibility-selection', 'customPostL10n', array( 'custom' => __( 'Manage authorized viewers' ) ) );
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
	 *
	 * Forked originally from core code in wp-admin/includes/meta-boxes.php used to
	 * assign a post's visibility.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post
	 */
	public function add_visibility_selection( $post ) {
		$post_type = $post->post_type;
		$post_type_object = get_post_type_object( $post_type );

		// If a current user can publish, the current user can modify visibility settings.
		$can_publish = current_user_can( $post_type_object->cap->publish_posts );
		$groups = get_post_meta( $post->ID, '_content_visibility_viewer_groups', true );

		?>
		<div class="misc-pub-section misc-pub-custom-visibility" id="custom-visibility">
			<?php

			esc_html_e( 'Visibility:' );
			$custom_groups_class = 'hide-if-js';

			if ( 'private' === $post->post_status && ! empty( $groups ) ) {
				$post->post_password = '';
				$visibility = 'custom';
				$visibility_trans = __( 'Manage authorized viewers' );
				$custom_groups_class = '';
			} elseif ( 'private' === $post->post_status ) {
				$post->post_password = '';
				$visibility = 'private';
				$visibility_trans = __( 'Private' );
			} elseif ( ! empty( $post->post_password ) ) {
				$visibility = 'password';
				$visibility_trans = __( 'Password protected' );
			} elseif ( 'post' === $post->post_type && is_sticky( $post->ID ) ) {
				$visibility = 'public';
				$visibility_trans = __( 'Public, Sticky' );
			} else {
				$visibility = 'public';
				$visibility_trans = __( 'Public' );
			}

			?>
			<span id="post-custom-visibility-display"><?php echo esc_html( $visibility_trans ); ?></span>
			<?php if ( $can_publish ) { ?>
				<a href="#custom-visibility" class="edit-custom-visibility hide-if-no-js"><span aria-hidden="true"><?php esc_html_e( 'Edit' ); ?></span> <span class="screen-reader-text"><?php esc_html_e( 'Edit visibility' ); ?></span></a>

				<div id="post-custom-visibility-select" class="hide-if-js">
					<input type="hidden" name="hidden_custom_post_password" id="hidden-custom-post-password" value="<?php echo esc_attr( $post->post_password ); ?>" />

					<?php if ( 'post' === $post_type ) : ?>
						<input type="checkbox" style="display:none" name="custom-hidden_post_sticky" id="hidden-custom-post-sticky" value="sticky" <?php checked( is_sticky( $post->ID ) ); ?> />
					<?php endif; ?>

					<input type="hidden" name="hidden_custom_post_visibility" id="hidden-custom-post-visibility" value="<?php echo esc_attr( $visibility ); ?>" />

					<input type="radio" name="visibility" id="custom-visibility-radio-public" class="remove-custom-visibility" value="public" <?php checked( $visibility, 'public' ); ?> /> <label for="custom-visibility-radio-public" class="selectit"><?php esc_html_e( 'Public' ); ?></label><br />

					<?php if ( 'post' === $post_type && current_user_can( 'edit_others_posts' ) ) : ?>
						<span id="sticky-span"><input id="custom-sticky" name="custom-sticky" type="checkbox" value="sticky" <?php checked( is_sticky( $post->ID ) ); ?> /> <label for="custom-sticky" class="selectit"><?php esc_html_e( 'Stick this post to the front page' ); ?></label><br /></span>
					<?php endif; ?>

					<input type="radio" name="visibility" id="custom-visibility-radio-password" class="remove-custom-visibility" value="password" <?php checked( $visibility, 'password' ); ?> /> <label for="custom-visibility-radio-password" class="selectit"><?php esc_html_e( 'Password protected' ); ?></label><br />

					<span id="password-span"><label for="post_password"><?php esc_html_e( 'Password:' ); ?></label> <input type="text" name="post_password" id="custom-post_password" value="<?php echo esc_attr( $post->post_password ); ?>"  maxlength="20" /><br /></span>

					<input type="radio" name="visibility" id="custom-visibility-radio-private" class="remove-custom-visibility" value="private" <?php checked( $visibility, 'private' ); ?> /> <label for="custom-visibility-radio-private" class="selectit"><?php esc_html_e( 'Private' ); ?></label><br />

					<input type="radio" name="custom_visibility" id="custom-visibility-radio-custom" value="custom" <?php checked( $visibility, 'custom' ); ?> /> <label for="custom-visibility-radio-custom" class="selectit"><?php esc_html_e( 'Manage authorized viewers' ); ?></label><br />

					<div class="custom-visibility-groups <?php echo $custom_groups_class; ?>">
						<?php $this->display_viewers_meta_box( $post ); ?>
					</div>
					<p>
						<a href="#custom-visibility" class="save-post-custom-visibility hide-if-no-js button"><?php esc_html_e( 'OK' ); ?></a>
						<a href="#custom-visibility" class="cancel-post-custom-visibility hide-if-no-js button-cancel"><?php esc_html_e( 'Cancel' ); ?></a>
					</p>
				</div>
			<?php } ?>
		</div>
		<?php

	}

	/**
	 * Determine if a post can be viewed by a user based on the content visibility
	 * groups assigned to that user.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function user_can_read_post( $post, $user ) {
		$groups = get_post_meta( $post->ID, '_content_visibility_viewer_groups', true );

		if ( empty( $groups ) ) {
			return true;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$group_member = false;
		if ( in_array( 'site-member', $groups, true ) && is_user_member_of_blog( $user->ID ) ) {
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
		return apply_filters( 'user_in_content_visibility_groups', $group_member, $user->ID, $groups );
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

		if ( ! isset( $_POST['visibility_viewers_box'] ) || ! isset( $_POST['_content_visibility_nonce'] ) || ! wp_verify_nonce( $_POST['_content_visibility_nonce'], 'save-content-visibility' ) ) {
			return;
		}

		if ( ! isset( $_POST['custom_visibility'] ) || 'custom' !== $_POST['custom_visibility'] ) {
			delete_post_meta( $post_id, '_content_visibility_viewer_groups' );

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

	/**
	 * When a post is being updated, check to see if custom visibility is being assigned and
	 * set the post status and post password back to private like defaults if so.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post object prepared for save.
	 * @return WP_Post $post Modified post object.
	 */
	public function wp_insert_post_data( $post ) {
		// If custom visibility is not set, leave the post alone.
		if ( ! isset( $_POST['custom_visibility'] ) || 'custom' !== $_POST['custom_visibility'] ) {
			return $post;
		}

		$post['post_status'] = 'private';

		if ( '' === $post['post_password'] ) {
			$post['post_password'] = '';
		}

		return $post;
	}

	/**
	 * Manage capabilities allowing users to read private posts.
	 *
	 * @since 0.1.0
	 *
	 * @param array   $allcaps An array of all the user's capabilities.
	 * @param array   $caps    Actual capabilities for meta capability.
	 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
	 * @param WP_User $user    The user object.
	 * @return array Updated list of capabilities.
	 */
	public function allow_read_private_posts( $allcaps, $caps, $args, $user ) {
		if ( 'read_post' !== $args[0] ) {
			return $allcaps;
		}

		$post = get_post( $args[2] );

		if ( ! post_type_supports( $post->post_type, 'wsuwp-content-visibility' ) ) {
			return $allcaps;
		}

		$post_type = get_post_type_object( $post->post_type );

		if ( ! in_array( $post_type->cap->read_private_posts, $caps, true ) ) {
			return $allcaps;
		}

		if ( false === $this->user_can_read_post( $post, $user ) ) {
			return $allcaps;
		}

		$allcaps[ $post_type->cap->read_private_posts ] = true;

		return $allcaps;
	}

	/**
	 * Capture a page request before serving a template and redirect if viewers have
	 * been marked for restriction via custom content visibility.
	 *
	 * @since 1.0.0
	 */
	public function template_redirect() {
		if ( is_404() && $this->private_redirect ) {
			if ( ! is_user_logged_in() ) {
				$redirect = wp_login_url( $_SERVER['REQUEST_URI'] );
			} else {
				$redirect = get_home_url();
			}

			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * If results are available at this point in the process, then mark the flag
	 * that will indicate a redirect should occur if the list of posts becomes
	 * empty later in the process due to permissions issues.
	 *
	 * @since 1.0.0
	 *
	 * @param array $results Results of a posts query before additional processing.
	 *
	 * @return array Untouched results.
	 */
	public function setup_trick_404_redirect( $results ) {
		if ( isset( $results[0] ) && 0 < count( $results[0] ) ) {
			$this->private_redirect = true;
		}

		return $results;
	}
}
