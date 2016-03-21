<?php

class Test_WSUWP_Content_Visibility extends WP_UnitTestCase {
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

	public function test_user_in_content_visibility_groups_return_false() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private' ) );
		$user_id = $this->factory->user->create( array( 'user_login' => 'testuser', 'role' => 'Contributor' ) );

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );
		$caps = array( $post_type->cap->read_private_posts );
		$cap = 'read_post';
		$args = array( $post->ID );

		update_post_meta( $post->ID, '_content_visibility_groups', array( 'group1' => array( 'id' => 'group_id', 'display_name' => 'Group Display' ) ) );

		$content_visibility = WSUWP_Content_Visibility();

		add_filter( 'user_in_content_visibility_groups', '__return_false' );
		$this->assertEquals( $caps, $content_visibility->allow_read_private_posts( $caps, $cap, $user_id, $args ) );
		remove_filter( 'user_in_content_visibility_groups', '__return_false' );
	}

	public function test_user_in_content_visibility_groups_return_true() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private' ) );
		$user_id = $this->factory->user->create( array( 'user_login' => 'testuser', 'role' => 'Contributor' ) );

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		$caps = array( $post_type->cap->read_private_posts );
		$filtered_caps = array( $post_type->cap->read );

		$cap = 'read_post';
		$args = array( $post->ID );

		update_post_meta( $post->ID, '_content_visibility_groups', array( 'group1' => array( 'id' => 'group_id', 'display_name' => 'Group Display' ) ) );

		$content_visibility = WSUWP_Content_Visibility();

		add_filter( 'user_in_content_visibility_groups', '__return_true' );
		$this->assertEquals( $filtered_caps, $content_visibility->allow_read_private_posts( $caps, $cap, $user_id, $args ) );
		remove_filter( 'user_in_content_visibility_groups', '__return_true' );
	}

	public function test_post_with_no_groups_always_returns_original_caps() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Post', 'post_status' => 'private' ) );
		$user_id = $this->factory->user->create( array( 'user_login' => 'testuser', 'role' => 'Contributor' ) );

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		$caps = array( $post_type->cap->read_private_posts );
		$cap = 'read_post';
		$args = array( $post->ID );

		$content_visibility = WSUWP_Content_Visibility();

		// The `true` filter here should not work and the original `$caps` data should return.
		add_filter( 'user_in_content_visibility_groups', '__return_true' );
		$this->assertEquals( $caps, $content_visibility->allow_read_private_posts( $caps, $cap, $user_id, $args ) );
		remove_filter( 'user_in_content_visibility_groups', '__return_true' );
	}
}
