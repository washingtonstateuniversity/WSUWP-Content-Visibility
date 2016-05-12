<?php

class Test_WSUWP_Content_Visibility extends WP_UnitTestCase {
	/**
	 * If no content visibility groups are assigned to a post, the list of caps passed to
	 * allow_read_private_posts will always be returned untouched.
	 */
	public function test_post_author_with_no_content_visibility_groups_return_true() {
		$user_id = $this->factory->user->create( array( 'user_login' => 'testuser', 'role' => 'Contributor' ) );
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private', 'post_author' => $user_id ) );

		$post = get_post( $post_id );
		$user = get_user_by( 'id', $user_id );

		$content_visibility = WSUWP_Content_Visibility();

		$this->assertTrue( $content_visibility->user_can_read_post(  $post, $user ) );
	}

	/**
	 * If a user is not a member of the content visibility groups for a post, and normally has access to
	 * all private posts (Editor), the list of caps passed to allow_read_private_posts will always be
	 * returned untouched.
	 */
	public function test_editor_user_in_content_visibility_groups_return_false() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private' ) );
		$user_id = $this->factory->user->create( array( 'user_login' => 'testeditor', 'role' => 'Editor' ) );

		$post = get_post( $post_id );
		$user = get_user_by( 'id', $user_id );

		$current_user_id = get_current_user_id();
		wp_set_current_user( $user_id );

		update_post_meta( $post->ID, '_content_visibility_viewer_groups', array( 'group1' ) );

		$content_visibility = WSUWP_Content_Visibility();

		add_filter( 'user_in_content_visibility_groups', '__return_false' );
		$can_read = $content_visibility->user_can_read_post( $post, $user );
		remove_filter( 'user_in_content_visibility_groups', '__return_false' );

		wp_set_current_user( $current_user_id );

		$this->assertFalse( $can_read );
	}

	/**
	 * If a user is a member of the content visibility groups for a post and normally does not have access to
	 * all private posts (Contributor), the list of caps passed to allow_read_private_posts should change to
	 * return the read_posts cap for the post type.
	 */
	public function test_user_in_content_visibility_groups_return_true() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private' ) );
		$user_id = $this->factory->user->create( array( 'user_login' => 'testuser', 'role' => 'Contributor' ) );

		$post = get_post( $post_id );
		$user = get_user_by( 'id', $user_id );

		$current_user_id = get_current_user_id();
		wp_set_current_user(  $user_id );

		update_post_meta( $post->ID, '_content_visibility_viewer_groups', array( 'group1' ) );

		$content_visibility = WSUWP_Content_Visibility();

		add_filter( 'user_in_content_visibility_groups', '__return_true' );
		$can_read = $content_visibility->user_can_read_post( $post, $user );
		remove_filter( 'user_in_content_visibility_groups', '__return_true' );

		wp_set_current_user(  $current_user_id );

		$this->assertTrue( $can_read );
	}

	/**
	 * If a user is a site member and the default group for site members is assigned to the post, then the
	 * list of caps passed to allow_read_private_posts should change to return the read_posts cap for the post type.
	 */
	public function test_site_member_user_in_content_visibility_groups_default_groups() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private' ) );
		$user_id = $this->factory->user->create( array( 'user_login' => 'testsubscriber', 'role' => 'Subscriber' ) );

		$post = get_post( $post_id );
		$user = get_user_by( 'id', $user_id );

		$current_user_id = get_current_user_id();
		wp_set_current_user( $user_id );

		update_post_meta( $post->ID, '_content_visibility_viewer_groups', array( 'site-member' ) );

		$content_visibility = WSUWP_Content_Visibility();

		$can_read = $content_visibility->user_can_read_post( $post, $user );

		wp_set_current_user( $current_user_id );

		$this->assertTrue( $can_read );
	}
}
