<?php

class WSUW_Content_Visibility_Ajax extends WP_Ajax_UnitTestCase {
	public function test_ajax_get_groups_invalid_post_id() {
		$this->_setRole( 'administrator' );

		$_POST['_ajax_nonce'] = wp_create_nonce( 'wsu-visibility-groups' );
		$_POST['post_id'] = 0;

		try {
			$this->_handleAjax( 'get_content_visibility_groups' );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response, true );

		$expected_response = array( 'success' => false, 'data' => 'Invalid post ID.' );
		$this->assertEquals( $expected_response, $response );
	}

	public function test_ajax_get_groups_valid_post_id_with_no_saved_groups() {
		$this->_setRole( 'administrator' );

		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Title' ) );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'wsu-visibility-groups' );
		$_POST['post_id'] = $post_id;

		delete_post_meta( $post_id, '_content_visibility_groups' );

		try {
			$this->_handleAjax( 'get_content_visibility_groups' );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response, true );
		$expected_response = array( 'success' => true, 'data' => array() );

		$this->assertEquals( $expected_response, $response );
	}

	public function test_ajax_get_groups_valid_post_id_with_saved_groups() {
		$this->_setRole( 'administrator' );

		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Title' ) );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'wsu-visibility-groups' );
		$_POST['post_id'] = $post_id;

		$saved_groups = array( 'saved_group_1', 'saved_group_2' );

		update_post_meta( $post_id, '_content_visibility_groups', $saved_groups );

		try {
			$this->_handleAjax( 'get_content_visibility_groups' );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response, true );
		$expected_response = array(
			'success' => true,
			'data' => array(
				array(
					'id' => 'saved_group_1',
					'display_name' => 'saved_group_1',
					'member_count' => '',
					'member_list' => '',
					'selected_class' => 'visibility-group-selected',
				),
				array(
					'id' => 'saved_group_2',
					'display_name' => 'saved_group_2',
					'member_count' => '',
					'member_list' => '',
					'selected_class' => 'visibility-group-selected',
				),
			),
		);

		$this->assertEquals( $expected_response, $response );
	}

	public function filter_group_details( $group_details ) {
		if ( 'saved_group_1' === $group_details['id'] ) {
			$group_details['member_count'] = 1;
			$group_details['member_list'] = array( 'testuser' );
		} elseif ( 'saved_group_2' === $group_details['id'] ) {
			$group_details['member_count'] = 2;
			$group_details['member_list'] = array( 'testuser', 'testuser2' );
		}

		return $group_details;
	}

	public function test_ajax_get_groups_valid_post_id_with_filtered_saved_groups() {
		$this->_setRole( 'administrator' );

		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Title' ) );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'wsu-visibility-groups' );
		$_POST['post_id'] = $post_id;

		$saved_groups = array( 'saved_group_1', 'saved_group_2' );

		update_post_meta( $post_id, '_content_visibility_groups', $saved_groups );

		add_filter( 'content_visibility_group_details', array( $this, 'filter_group_details' ) );
		try {
			$this->_handleAjax( 'get_content_visibility_groups' );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response, true );
		remove_filter( 'content_visibility_group_details', array( $this, 'filter_group_details' ) );

		$expected_response = array(
			'success' => true,
			'data' => array(
				array(
					'id' => 'saved_group_1',
					'display_name' => 'saved_group_1',
					'member_count' => 1,
					'member_list' => array( 'testuser' ),
					'selected_class' => 'visibility-group-selected',
				),
				array(
					'id' => 'saved_group_2',
					'display_name' => 'saved_group_2',
					'member_count' => 2,
					'member_list' => array( 'testuser', 'testuser2' ),
					'selected_class' => 'visibility-group-selected',
				),
			),
		);

		$this->assertEquals( $expected_response, $response );
	}
}