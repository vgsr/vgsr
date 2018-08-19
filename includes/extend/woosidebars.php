<?php

/**
 * VGSR Extension for Woosidebars
 *
 * @package VGSR
 * @subpackage Woosidebars
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Woosidebars' ) ) :
/**
 * The VGSR Woosidebars class
 *
 * @since 1.0.0
 */
class VGSR_Woosidebars {

	/**
	 * Setup this class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 1.0.0
	 *
	 * @uses Woo_Sidebars $woosidebars
	 */
	private function setup_actions() {
		global $woosidebars;

		// Remove default post type support
		remove_post_type_support( 'post', 'woosidebars' );

		// Fix loading of admin columns
		if ( ! has_action( 'manage_edit-sidebar_columns', array( $woosidebars, 'register_custom_column_headings' ) ) ) {
			add_filter( 'manage_edit-sidebar_columns',        array( $woosidebars, 'register_custom_column_headings' ), 10, 1 );
			add_action( 'manage_sidebar_posts_custom_column', array( $woosidebars, 'register_custom_columns'         ), 10, 2 );
		}
	}
}

/**
 * Setup the extension logic for Woosidebars
 *
 * @since 1.0.0
 *
 * @uses VGSR_Woosidebars
 */
function vgsr_setup_woosidebars() {
	vgsr()->extend->woosidebars = new VGSR_Woosidebars;
}

endif; // class_exists
