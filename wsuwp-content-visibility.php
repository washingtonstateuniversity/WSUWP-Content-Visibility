<?php
/*
Plugin Name: WSU Content Visibility
Plugin URI: https://web.wsu.edu/
Description: Control the visibility of content for authenticated users.
Author: washingtonstateuniversity, jeremyfelt
Version: 1.1.0
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// The core plugin class.
require dirname( __FILE__ ) . '/includes/class-wsuwp-content-visibility.php';

add_action( 'after_setup_theme', 'WSUWP_Content_Visibility' );
/**
 * Start things up.
 *
 * @since 0.1.0
 *
 * @return \WSUWP_Content_Visibility
 */
function WSUWP_Content_Visibility() {
	return WSUWP_Content_Visibility::get_instance();
}
