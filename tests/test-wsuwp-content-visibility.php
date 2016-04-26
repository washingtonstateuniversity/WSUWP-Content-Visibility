<?php

class Test_WSUWP_Content_Visibility extends WP_UnitTestCase {
	/**
	 * A post author who has the role of contributor can read their own post
	 * when no viewer groups are assigned.
	 */
	public function test_post_author_with_no_content_visibility_groups_return_true() {
		$user_id = $this->factory->user->create( array( 'user_login' => 'testuser', 'role' => 'Contributor' ) );
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private', 'post_author' => $user_id ) );

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		$caps = array( $post_type->cap->read_private_posts );
		$filtered_caps = array( $post_type->cap->read_private_posts );

		$cap = 'read_post';
		$args = array( $post->ID );

		$content_visibility = WSUWP_Content_Visibility();

		$this->assertEquals( $filtered_caps, $content_visibility->allow_read_private_posts( $caps, $cap, $user_id, $args ) );
	}

	/**
	 * A user with the role of contributor can not read a private post they do not own
	 * if the filter determines they do not belong to one of the assigned viewer groups.
	 */
	public function test_user_in_content_visibility_groups_return_false() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private' ) );
		$user_id = $this->factory->user->create( array( 'user_login' => 'testuser', 'role' => 'Contributor' ) );

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );
		$caps = array( $post_type->cap->read_private_posts );
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
	 * A user with the role of contributor can read a private post they do not own
	 * if the filter determines they are a member of one of the assigned viewer groups.
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
	 * Capabilities for a user with the role of contributor will be returned untouched if no viewer
	 * groups have been assigned to the post, even if a filter exists verifying this user as a member
	 * of a valid group.
	 */
	public function test_post_with_no_groups_always_returns_original_caps() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private' ) );
		$user_id = $this->factory->user->create( array( 'user_login' => 'testuser', 'role' => 'Contributor' ) );

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		$caps = array( 'madethiscapup' );
		$cap = 'read_post';
		$args = array( $post->ID );

		$content_visibility = WSUWP_Content_Visibility();

		// The `true` filter here should not work and the original `$caps` data should return.
		add_filter( 'user_in_content_visibility_groups', '__return_true' );
		$actual_caps = $content_visibility->allow_read_private_posts( $caps, $cap, $user_id, $args );
		remove_filter( 'user_in_content_visibility_groups', '__return_true' );

		$this->assertEquals( $caps, $actual_caps );
	}
}
