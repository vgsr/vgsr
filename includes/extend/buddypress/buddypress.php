<?php

/**
 * Main VGSR BuddyPress Class
 *
 * @package VGSR
 * @subpackage Plugins
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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

		// Support the BP Group Hierarchy plugin
		$this->bp_group_hierarchy = defined( 'BP_GROUP_HIERARCHY_VERSION' );
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
		add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_bp_settings_sections' );
		add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_bp_settings_fields'   );

		// Caps
		add_filter( 'vgsr_map_settings_meta_caps', array( $this, 'map_meta_caps' ), 10, 4 );

		// Groups Component
		if ( bp_is_active( 'groups' ) ) {
			$groups = buddypress()->groups;

			// Use BP groups as VGSR groups
			add_filter( 'vgsr_get_group_vgsr_id',     array( $this, 'get_group_vgsr'     ) );
			add_filter( 'vgsr_get_group_leden_id',    array( $this, 'get_group_leden'    ) );
			add_filter( 'vgsr_get_group_oudleden_id', array( $this, 'get_group_oudleden' ) );
			add_filter( 'vgsr_get_vgsr_groups',       array( $this, 'get_vgsr_groups'    ) );

			// Check membership
			add_filter( 'vgsr_user_in_group', array( $this, 'user_in_group' ), 10, 3 );

			// Remove groups admin bar menu items
			if ( vgsr_bp_remove_groups_admin_nav() ) {
				remove_action( 'bp_setup_admin_bar', array( $groups, 'setup_admin_bar' ), $groups->adminbar_myaccount_order );
			}
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
	 * @uses get_site_option()
	 * @return int VGSR group ID
	 */
	public function get_group_vgsr() {
		return (int) get_site_option( 'vgsr_bp_group_vgsr', 0 );
	}

	/**
	 * Return the leden group ID
	 *
	 * @since 0.0.1
	 *
	 * @uses get_site_option()
	 * @return int Leden group ID
	 */
	public function get_group_leden() {
		return (int) get_site_option( 'vgsr_bp_group_leden', 0 );
	}

	/**
	 * Return the oud-leden group ID
	 *
	 * @since 0.0.1
	 *
	 * @uses get_site_option()
	 * @return int Oud-leden group ID
	 */
	public function get_group_oudleden() {
		return (int) get_site_option( 'vgsr_bp_group_oudleden', 0 );
	}

	/**
	 * Return all VGSR group ids
	 *
	 * @since 0.0.6
	 *
	 * @uses VGSR_BuddyPress:get_group_hierarchy()
	 * @uses vgsr_get_group_vgsr_id()
	 * @uses vgsr_get_group_leden_id()
	 * @uses vgsr_get_group_oudleden_id()
	 * 
	 * @param array $groups VGSR groups
	 * @return array VGSR groups
	 */
	public function get_vgsr_groups( $groups ) {

		// Append full VGSR group hierarchy
		$groups = array_filter( array_unique( array_merge( $groups, $this->get_group_hierarchy( array( 
			vgsr_get_group_vgsr_id(), 
			vgsr_get_group_leden_id(), 
			vgsr_get_group_oudleden_id() 
		) ) ) ) );

		return $groups;
	}

	/** Groups *************************************************************/

	/**
	 * Return whether the given user is member of (a) BuddyPress group(s)
	 *
	 * @since 0.0.1
	 *
	 * @uses vgsr_get_group_vgsr_id()
	 * @uses vgsr_get_vgsr_groups()
	 * @uses VGSR_BuddyPress::get_group_hierarchy()
	 *
	 * @param bool $is_member Whether the user is a valid member
	 * @param int|array $group_id Group ID or ids
	 * @param int $user_id User ID
	 * @return bool User is group member
	 */
	public function user_in_group( $is_member, $group_id = 0, $user_id = 0 ) {
		global $wpdb;

		// Bail when no group was provided
		if ( empty( $group_id ) )
			return false;

		// Default to current user
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$group_id = array_map( 'intval', (array) $group_id );
		$user_id  = (int) $user_id;

		// Ensure BP is setup to use its logic
		if ( ! did_action( 'bp_init' ) ) {
			bp_init();
		}

		// Find any group memberships
		$groups = groups_get_groups( array( 
			'user_id'         => $user_id,
			'include'         => $this->get_group_hierarchy( $group_id ),
			'show_hidden'     => true,
			'per_page'        => false,
			'populate_extras' => false
		) );

		// Return whether any membership was found
		return ! empty( $groups['groups'] );
	}

	/**
	 * Return all groups in the group hierarchy when it's active
	 *
	 * @since 0.0.7
	 *
	 * @uses BP_Groups_Hierarchy::has_children()
	 * 
	 * @param int|array $group_ids Group ID or ids
	 * @return array Group ids
	 */
	public function get_group_hierarchy( $group_ids ) {

		// Force array
		$group_ids = (array) $group_ids;

		// Account for group hierarchy
		if ( $this->bp_group_hierarchy ) {

			/**
			 * Use the ArrayIterator class to dynamically walk all array elements 
			 * while adding new items to that array for continued iteration.
			 */
			$hierarchy = new ArrayIterator( $group_ids );
			foreach ( $hierarchy as $gid ) {

				// Add child group ids when found
				if ( ! empty( $gid ) && ( $children = @BP_Groups_Hierarchy::has_children( $gid ) ) ) {
					foreach ( $children as $child_id ) {
						$hierarchy->append( (int) $child_id );
					}
				}
			}

			// Set hierarchy group id collection
			$group_ids = $hierarchy->getArrayCopy();
		}

		return array_unique( $group_ids );
	}
}

endif; // class_exists
