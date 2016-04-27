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

		$filtered_caps = $caps = array( 'will-not-change' );

		$cap = 'read_post';
		$args = array( $post->ID );

		$content_visibility = WSUWP_Content_Visibility();

		$this->assertEquals( $filtered_caps, $content_visibility->allow_read_private_posts( $caps, $cap, $user_id, $args ) );
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

		$caps = array( 'will-not-change' );
		$cap = 'read_post';
		$args = array( $post->ID );

		update_post_meta( $post->ID, '_content_visibility_viewer_groups', array( 'group1' ) );

		$content_visibility = WSUWP_Content_Visibility();

		add_filter( 'user_in_content_visibility_groups', '__return_false' );
		$actual_caps = $content_visibility->allow_read_private_posts( $caps, $cap, $user_id, $args );
		remove_filter( 'user_in_content_visibility_groups', '__return_false' );

		$this->assertEquals( $caps, $actual_caps );
	}

	/**
	 * If a user is not a member of the content visibility groups for a post, and normally does not have access
	 * to all private posts (Contributor), the list of caps passed to allow_read_private_posts will always be
	 * returned untouched.
	 */
	public function test_contributor_user_in_content_visibility_groups_return_false() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private' ) );
		$user_id = $this->factory->user->create( array( 'user_login' => 'testeditor', 'role' => 'Contributor' ) );

		$post = get_post( $post_id );

		$caps = array( 'will-not-change' );
		$cap = 'read_post';
		$args = array( $post->ID );

		update_post_meta( $post->ID, '_content_visibility_viewer_groups', array( 'group1' ) );

		$content_visibility = WSUWP_Content_Visibility();

		add_filter( 'user_in_content_visibility_groups', '__return_false' );
		$actual_caps = $content_visibility->allow_read_private_posts( $caps, $cap, $user_id, $args );
		remove_filter( 'user_in_content_visibility_groups', '__return_false' );

		$this->assertEquals( $caps, $actual_caps );
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
		$post_type = get_post_type_object( $post->post_type );

		$caps = array( $post_type->cap->read_private_posts );
		$filtered_caps = array( $post_type->cap->read );

		$cap = 'read_post';
		$args = array( $post->ID );

		update_post_meta( $post->ID, '_content_visibility_viewer_groups', array( 'group1' ) );

		$content_visibility = WSUWP_Content_Visibility();

		add_filter( 'user_in_content_visibility_groups', '__return_true' );
		$actual_caps = $content_visibility->allow_read_private_posts( $caps, $cap, $user_id, $args );
		remove_filter( 'user_in_content_visibility_groups', '__return_true' );

		$this->assertEquals( $filtered_caps, $actual_caps );
	}

	/**
	 * If a user is a site member and the default group for site members is assigned to the post, then the
	 * list of caps passed to allow_read_private_posts should change to return the read_posts cap for the post type.
	 */
	public function test_site_member_user_in_content_visibility_groups_default_groups() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private' ) );
		$user_id = $this->factory->user->create( array( 'user_login' => 'testsubscriber', 'role' => 'Subscriber' ) );

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		$caps = array( $post_type->cap->read_private_posts );
		$filtered_caps = array( $post_type->cap->read );

		$cap = 'read_post';
		$args = array( $post->ID );

		update_post_meta( $post->ID, '_content_visibility_viewer_groups', array( 'site-member' ) );

		$content_visibility = WSUWP_Content_Visibility();

		$actual_caps = $content_visibility->allow_read_private_posts( $caps, $cap, $user_id, $args );

		$this->assertEquals( $filtered_caps, $actual_caps );
	}
}
