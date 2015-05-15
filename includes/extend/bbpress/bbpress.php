<?php

/**
 * Main VGSR bbPress Class
 *
 * @package VGSR
 * @subpackage Plugins
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_BBPress' ) ) :
/**
 * Loads bbPress Extension
 *
 * @since 0.0.1
 */
class VGSR_BBPress {

	/** Setup Methods ******************************************************/

	/**
	 * The main VGSR bbPress loader
	 * 
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Define default class globals
	 * 
	 * @since 0.0.1
	 */
	private function setup_globals() {
		$vgsr = vgsr();

		/** Paths **********************************************************/

		$this->includes_dir = trailingslashit( $vgsr->extend_dir . 'bbpress' );
		$this->includes_url = trailingslashit( $vgsr->extend_url . 'bbpress' );
	}

	/**
	 * Include the required files
	 *
	 * @since 0.0.1
	 */
	private function includes() {
		require( $this->includes_dir . 'settings.php' );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 0.0.1
	 */
	private function setup_actions() {

		// Hook settings
		add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_bbp_settings_sections'           );
		add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_bbp_settings_fields'             );
		add_filter( 'vgsr_map_settings_meta_caps',      array( $this, 'map_meta_caps' ), 10, 4 );

		// Not using BuddyPress? Hide profile root
		if ( ! function_exists( 'buddypress' ) ) {
			add_filter( 'bbp_get_user_slug', array( $this, 'hide_profile_root' ) );
		}

		// Breadcrumbs home
		add_filter( 'bbp_before_get_breadcrumb_parse_args', array( $this, 'breadcrumbs_home' ) );
	}

	/** Capabilities *******************************************************/

	/**
	 * Map VGSR bbPress settings capabilities
	 *
	 * @since 0.0.1
	 * 
	 * @param  array   $caps    Required capabilities
	 * @param  string  $cap     Requested capability
	 * @param  integer $user_id User ID
	 * @param  array   $args    Additional arguments
	 * @return array Required capabilities
	 */
	public function map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

		switch ( $cap ) {

			case 'vgsr_settings_bbpress' :
				$caps = array( vgsr()->admin->minimum_capability );
				break;
		}

		return $caps;
	}

	/** Methods ************************************************************/

	/**
	 * Remove the user profile root slug 
	 *
	 * @since 0.0.1
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
	 * @since 0.0.1
	 *
	 * @uses vgsr_bbp_breadcrumbs_home()
	 * @param array $args Parse args
	 * @return array $args
	 */
	public function breadcrumbs_home( $args ) {

		// Get the Home text
		$home = vgsr_bbp_breadcrumbs_home();

		// Only replace when not empty
		if ( ! empty( $home ) )
			$args['home_text'] = $home;

		return $args;
	}	
}

endif; // class_exists