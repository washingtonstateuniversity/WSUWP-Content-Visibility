<?php namespace WSUWP\Plugin\ContentVisibility;

class Api {

	public static function get_groups( \WP_REST_Request $request ) {
        
		$params = array(
            'postId' => $request['postId'] ? sanitize_text_field( $request['postId'] ) : null,
        );

        $content_visibility_instance = WSUWP_Content_Visibility();

		$group_options = apply_filters( 'content_visibility_default_groups', $content_visibility_instance->default_groups );

        $selected_groups = get_post_meta( $params['postId'], '_content_visibility_viewer_groups', true );

		return array(
			'group_options' => $group_options,
			'selected_group_ids' => $selected_groups
		);

	}


	public static function update_groups( \WP_REST_Request $request ) {

        $params = wp_parse_args($request->get_params(), [
			'post_id' => 0,
			'selected_groups' => array()
		]);				
		$content_visibility_instance = WSUWP_Content_Visibility();
		$default_groups = apply_filters( 'content_visibility_default_groups', $content_visibility_instance->default_groups );
		$default_group_ids = wp_list_pluck( $default_groups, 'id' );
		$save_groups = array();

		foreach ( $params['selected_groups'] as $selected_group ) {
			if ( in_array( $selected_group, $default_group_ids, true ) ) {
				$save_groups[] = $selected_group;
			}
		}

		if(empty($save_groups)){
			delete_post_meta( $params['post_id'], '_content_visibility_viewer_groups' );
		}else{
			update_post_meta( $params['post_id'], '_content_visibility_viewer_groups', $save_groups );
		}

		return new \WP_REST_Response('', 200);

	}	


	public static function init() {
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'content-visibility-api/v1',
					'/groups',
					array(
						'methods' => \WP_REST_Server::READABLE,
						'callback' => array( __CLASS__, 'get_groups' ),
						'permission_callback' => '__return_true',
					)
				);

				register_rest_route(
					'content-visibility-api/v1',
					'/groups',
					array(
						'methods' => \WP_REST_Server::EDITABLE,
						'callback' => array( __CLASS__, 'update_groups' ),
						'permission_callback' => function(){
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		);
	}
}


Api::init();
