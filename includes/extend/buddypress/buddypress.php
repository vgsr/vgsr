<?php

/**
 * Main VGSR BuddyPress Class
 *
 * @package VGSR
 * @subpackage Plugins
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'VGSR_BuddyPress' ) ) :
/**
 * Loads BuddyPress Extension
 *
 * @since 0.0.1
 */
class VGSR_BuddyPress {

	/** Setup Methods ******************************************************/

	/**
	 * The main VGSR BuddyPress loader
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
		$this->includes_dir = trailingslashit( $vgsr->includes_dir . 'extend/buddypress' );
		$this->includes_url = trailingslashit( $vgsr->includes_url . 'extend/buddypress' );

		// Support BP Group Hierarcy plugin
		$this->hierarchy = defined( 'BP_GROUP_HIERARCHY_VERSION');
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
	 *
	 * @uses bp_is_active() To enable hooks for active BP components
	 */
	private function setup_actions() {

		// Hook settings
		add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_bp_settings_sections'            );
		add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_bp_settings_fields'              );
		add_filter( 'vgsr_map_settings_meta_caps',      array( $this, 'map_meta_caps' ), 10, 4 );

		// Remove admin bar My Account root
		if ( vgsr_bp_remove_ab_my_account_root() ) {
			remove_action( 'admin_bar_menu', 'bp_admin_bar_my_account_root', 100 );
		}

		// Group hooks
		if ( bp_is_active( 'groups' ) ) {

			// Group IDs
			add_filter( 'vgsr_get_group_vgsr_id',     array( $this, 'get_group_vgsr'     ) );
			add_filter( 'vgsr_get_group_leden_id',    array( $this, 'get_group_leden'    ) );
			add_filter( 'vgsr_get_group_oudleden_id', array( $this, 'get_group_oudleden' ) );

			// User in group
			add_filter( 'vgsr_user_in_group', array( $this, 'user_in_group' ), 10, 2 );
		}
	}

	/** Capabilities *******************************************************/

	/**
	 * Map VGSR BuddyPress settings capabilities
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

			case 'vgsr_settings_bp_general' :
			case 'vgsr_settings_bp_groups'  :
				$caps = array( vgsr()->admin->minimum_capability );
				break;
		}

		return $caps;
	}

	/** Options ************************************************************/

	/**
	 * Return the vgsr group ID
	 *
	 * @since 0.0.1
	 *
	 * @uses get_option()
	 * @return int VGSR group ID
	 */
	public function get_group_vgsr() {
		return (int) get_option( 'vgsr_bp_group_vgsr', 0 );
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
		return (int) get_option( 'vgsr_bp_group_leden', 0 );
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
		return (int) get_option( 'vgsr_bp_group_oudleden', 0 );
	}

	/** Methods ************************************************************/

	/**
	 * Map user group membership checks to BP function
	 *
	 * @since 0.0.1
	 *
	 * @uses vgsr_get_group_vgsr_id()
	 * @uses vgsr_get_group_leden_id()
	 * @uses vgsr_get_group_oudleden_id()
	 * @uses groups_is_user_member()
	 *
	 * @param int $group_id Group ID
	 * @param int $user_id User ID
	 * @return bool User is group member
	 */
	public function user_in_group( $group_id = 0, $user_id = 0 ) {

		// Check VGSR subgroups if hierarchy is not enabled
		if ( ! $this->hierarchy && vgsr_get_group_vgsr_id() == $group_id ) {

			// Walk leden and oud-leden groups
			foreach ( array( vgsr_get_group_leden_id(), vgsr_get_group_oudleden_id() ) as $group_id ) {
				$is_member = groups_is_user_member( $user_id, $group_id );

				// Bail if success				
				if ( $is_member )
					break;
			}

		} else {
			$is_member = groups_is_user_member( $user_id, $group_id );
		}

		return $is_member;
	}
}

endif; // class_exists