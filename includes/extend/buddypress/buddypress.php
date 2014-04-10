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
		global $wpdb;

		// Bail if no group provided
		if ( empty( $group_id ) )
			return false;

		// Default to current user
		if ( empty( $user_id ) )
			$user_id = get_current_user_id();

		$group_id  = (int) $group_id;
		$user_id   = (int) $user_id;
		$is_member = false;

		// Consider hierarchy so look for sub group members too
		if ( $this->hierarchy ) {

			// Get all hierarchy group ids
			$groups = new ArrayIterator( array( $group_id ) );
			foreach ( $groups as $gid ) {
				if ( $children = BP_Groups_Hierarchy::has_children( $gid ) ) {
					foreach ( $children as $cgid )
						$groups->append( (int) $cgid );
				}
			}

			$bp  = buddypress();
			$sql = $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id IN (%s)", $user_id, implode( ',', $groups->getArrayCopy() ) );
			
			// Run query
			$is_member = (bool) $wpdb->get_var( $sql );

		} else {
			switch ( $group_id ) {

				// Default to leden and oud-leden for main group
				case vgsr_get_group_vgsr_id() :

					// Walk leden and oud-leden groups
					foreach ( array( vgsr_get_group_leden_id(), vgsr_get_group_oudleden_id() ) as $group_id ) {

						// Quit searching when user is member
						if ( $is_member = groups_is_user_member( $user_id, $group_id ) )
							break;
					}
					break;

				default :
					$is_member = groups_is_user_member( $user_id, $group_id );
					break;
			}
		}


		return $is_member;
	}
}

endif; // class_exists