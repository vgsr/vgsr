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

		/** Paths **********************************************************/

		$this->includes_dir = trailingslashit( $vgsr->includes_dir . 'extend/buddypress' );
		$this->includes_url = trailingslashit( $vgsr->includes_url . 'extend/buddypress' );

		/** Supports *******************************************************/

		// BP Group Hierarchy plugin
		$this->hierarchy = defined( 'BP_GROUP_HIERARCHY_VERSION' );
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

		// Settings
		add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_bp_settings_sections'            );
		add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_bp_settings_fields'              );
		add_filter( 'vgsr_map_settings_meta_caps', array( $this, 'map_meta_caps' ), 10, 4 );

		// Remove BP's admin bar My Account area
		if ( vgsr_bp_remove_ab_my_account_root() ) {
			remove_action( 'admin_bar_menu', 'bp_admin_bar_my_account_root', 100 );
		}

		// Group hooks
		if ( bp_is_active( 'groups' ) ) {

			// Groups component
			$groups = buddypress()->groups;

			// Manage group component
			add_action( "bp_{$groups->id}_setup_actions", array( $this, 'manage_group_component' ) );

			// Use BP groups as VGSR groups
			add_filter( 'vgsr_get_group_vgsr_id',     array( $this, 'get_group_vgsr'     ) );
			add_filter( 'vgsr_get_group_leden_id',    array( $this, 'get_group_leden'    ) );
			add_filter( 'vgsr_get_group_oudleden_id', array( $this, 'get_group_oudleden' ) );

			// User in group
			add_filter( 'vgsr_user_in_group', array( $this, 'user_in_group' ), 10, 3 );
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

	/** Component: Groups **************************************************/

	/**
	 * Manipulate the groups component
	 *
	 * @since 0.0.4
	 */
	public function manage_group_component() {
		$component = buddypress()->groups;

		// Remove admin bar menu
		if ( vgsr_bp_remove_groups_admin_nav() ) {
			remove_action( 'bp_setup_admin_bar', array( $component, 'setup_admin_bar' ), $this->adminbar_myaccount_order );
		}
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

	/** Groups *************************************************************/

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
	 * @param bool $is_member Whether the user is a valid member
	 * @param int $group_id Group ID
	 * @param int $user_id User ID
	 * @return bool User is group member
	 */
	public function user_in_group( $is_member, $group_id = 0, $user_id = 0 ) {
		global $wpdb;

		// Bail if no group provided
		if ( empty( $group_id ) )
			return false;

		// Default to current user
		if ( empty( $user_id ) )
			$user_id = get_current_user_id();

		$group_id  = (int) $group_id;
		$user_id   = (int) $user_id;

		// Hierarchical groups
		if ( $this->hierarchy ) {

			/**
			 * Get all hierarchy group ids
			 * 
			 * Use the ArrayIterator class to dynamically walk all 
			 * array elements while simultaneously adding new items 
			 * to that array.
			 */
			$groups = new ArrayIterator( array( $group_id ) );
			foreach ( $groups as $gid ) {
				if ( $children = BP_Groups_Hierarchy::has_children( $gid ) ) {
					foreach ( $children as $sub_group_id )
						$groups->append( (int) $sub_group_id );
				}
			}

			// Build the query
			$bp   = buddypress();
			$gids = implode( ',', $groups->getArrayCopy() );
			$sql  = $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND group_id IN ($gids)", $user_id );
			
			// Run the query
			$is_member = (bool) $wpdb->get_var( $sql );

		// Flat group list
		} else {

			// Check the group ID
			switch ( $group_id ) {

				// Main VGSR group defaults to leden and oud-leden
				case vgsr_get_group_vgsr_id() :

					// Walk leden and oud-leden groups
					foreach ( array( vgsr_get_group_leden_id(), vgsr_get_group_oudleden_id() ) as $group_id ) {

						// Quit searching when user is member
						if ( $is_member = groups_is_user_member( $user_id, $group_id ) )
							break;
					}
					break;

				// Look for user in provided group
				default :
					$is_member = groups_is_user_member( $user_id, $group_id );
					break;
			}
		}

		return $is_member;
	}
}

endif; // class_exists