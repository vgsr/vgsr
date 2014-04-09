<?php

/**
 * Main VGSR Groupz Class
 *
 * @package VGSR
 * @subpackage Extend
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'VGSR_Groupz' ) ) :
/**
 * Loads Groupz Extension
 *
 * @since 0.0.1
 */
class VGSR_Groupz {

	/** Setup Methods ******************************************************/

	/**
	 * The main VGSR Groupz loader
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
		$this->includes_dir = trailingslashit( $vgsr->includes_dir . 'extend/groupz' );
		$this->includes_url = trailingslashit( $vgsr->includes_url . 'extend/groupz' );
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
		add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_groupz_settings_sections'        );
		add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_groupz_settings_fields'          );
		add_filter( 'vgsr_map_settings_meta_caps',      array( $this, 'map_meta_caps' ), 10, 4 );

		// Group IDs
		add_filter( 'vgsr_get_group_vgsr_id',     array( $this, 'get_group_vgsr'     ) );
		add_filter( 'vgsr_get_group_leden_id',    array( $this, 'get_group_leden'    ) );
		add_filter( 'vgsr_get_group_oudleden_id', array( $this, 'get_group_oudleden' ) );

		// User in group
		add_filter( 'vgsr_user_in_group', 'groupz_user_in_group', 10, 2 );
	}

	/** Capabilities *******************************************************/

	/**
	 * Map VGSR Groupz settings capabilities
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

			case 'vgsr_settings_groupz' :
				$caps = array( vgsr()->admin->minimum_capability );
				break;
		}

		return $caps;
	}

	/** Methods ************************************************************/

	/**
	 * Return the vgsr group ID
	 *
	 * @since 0.0.1
	 *
	 * @uses get_option()
	 * @return int VGSR group ID
	 */
	public function get_group_vgsr() {
		return (int) get_option( 'vgsr_groupz_group_vgsr', 0 );
	}

	/**
	 * Return the leden group ID
	 *
	 * @since 0.0.1
	 *
	 * @uses get_option()
	 * @return int Leden group ID
	 */
	public function get_group_leden() {
		return (int) get_option( 'vgsr_groupz_group_leden', 0 );
	}

	/**
	 * Return the oud-leden group ID
	 *
	 * @since 0.0.1
	 *
	 * @uses get_option()
	 * @return int Oud-leden group ID
	 */
	public function get_group_oudleden() {
		return (int) get_option( 'vgsr_groupz_group_oudleden', 0 );
	}
}

endif; // class_exists