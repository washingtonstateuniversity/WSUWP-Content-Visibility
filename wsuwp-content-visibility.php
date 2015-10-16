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