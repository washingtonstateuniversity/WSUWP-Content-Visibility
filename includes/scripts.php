<?php namespace WSUWP\Plugin\ContentVisibility;

class Scripts {

	public static function register_block_editor_assets() {

		$editor_asset = include Plugin::get( 'plugin_dir' ) . 'gutenberg/dist/editor.asset.php';

		wp_register_script(
			'wsuwp-plugin-content-visibility-editor-scripts',
			Plugin::get( 'plugin_url' ) . 'gutenberg/dist/editor.js',
			$editor_asset['dependencies'],
			$editor_asset['version']
		);

		wp_register_style(
			'wsuwp-plugin-content-visibility-editor-styles',
			Plugin::get( 'plugin_url' ) . 'gutenberg/dist/editor.css',
			array(),
			$editor_asset['version']
		);

	}

    public static function enqueue_block_editor_assets() {

        wp_enqueue_script('wsuwp-plugin-content-visibility-editor-scripts');
        wp_enqueue_style('wsuwp-plugin-content-visibility-editor-styles');

	}


	public static function init() {
		add_action( 'init', __CLASS__ . '::register_block_editor_assets' );
        add_action( 'enqueue_block_editor_assets', __CLASS__ . '::enqueue_block_editor_assets' );
	}
}

Scripts::init();
