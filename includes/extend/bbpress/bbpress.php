<?php

/**
 * Main VGSR bbPress Class
 *
 * @package VGSR
 * @subpackage Plugins
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'VGSR_BBPress' ) ) :
/**
 * Loads bbPress Extension
 *
 * @since 1.0.0
 */
class VGSR_BBPress {

	/** Setup Methods ******************************************************/

	/**
	 * The main VGSR bbPress loader
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Define default class globals
	 * 
	 * @since 1.0.0
	 */
	private function setup_globals() {
		$vgsr = vgsr();
		$this->includes_dir = trailingslashit( $vgsr->includes_dir . 'extend/bbpress' );
		$this->includes_url = trailingslashit( $vgsr->includes_url . 'extend/bbpress' );
	}

	/**
	 * Include the required files
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		require( $this->includes_dir . 'functions.php' );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Hook settings
		add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_bbp_settings_section' );
		add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_bbp_settings_fields'  );

		// Hide profile root
		add_filter( 'bbp_get_user_slug', array( $this, 'hide_profile_root' ) );

		// Breadcrumbs home
		add_filter( 'bbp_before_get_breadcrumb_parse_args', array( $this, 'breadcrumbs_home' ) );
	}

	/** Methods ************************************************************/

	/**
	 * Remove the user profile root slug 
	 *
	 * @since 1.0.0
	 *
	 * @uses vgsr_bbp_hide_profile_root()
	 * @uses get_option()
	 * @param string $slug User slug
	 * @return string $slug
	 */
	public function hide_profile_root( $slug ) {

		// Hide root slug
		if ( ! vgsr_bbp_hide_profile_root() )
			return $slug;

		// Return just the bare user slug
		return get_option( '_bbp_user_slug', 'user' );
	}

	/**
	 * Replace the default breadcrumb home text
	 *
	 * @since 1.0.0
	 *
	 * @uses vgsr_bbp_breadcrumbs_home()
	 * @param array $args Parse args
	 * @return array $args
	 */
	public function breadcrumbs_home( $args ) {

		// Home text
		$home = vgsr_bbp_breadcrumbs_home();

		// Replace home text
		if ( ! empty( $home ) )
			$args['home_text'] = $home;

		return $args;
	}	
}

endif; // class_exists