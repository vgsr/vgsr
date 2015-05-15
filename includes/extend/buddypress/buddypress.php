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

		$this->includes_dir = trailingslashit( $vgsr->extend_dir . 'buddypress' );
		$this->includes_url = trailingslashit( $vgsr->extend_url . 'buddypress' );

		/** Supports *******************************************************/

		// BP Group Hierarchy plugin
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

		// Hide most of BP for non-vgsr
		add_action( 'bp_template_redirect',     array( $this, 'bp_pages_404'             ), 0 );
		add_filter( 'is_buddypress',            array( $this, 'is_buddypress'            )    );
		add_action( 'bp_setup_canonical_stack', array( $this, 'define_default_component' ), 5 ); // Before default priority
		add_action( 'bp_init',                  array( $this, 'deactivate_components'    ), 5 ); // After members component setup

		// Settings
		add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_bp_settings_sections' );
		add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_bp_settings_fields'   );

		// Caps
		add_filter( 'vgsr_map_settings_meta_caps', array( $this, 'map_meta_caps' ), 10, 4 );

		// Member Types. Dedicated hook per BP 2.3+
		if ( function_exists( 'bp_has_member_type' ) ) {
			add_action( 'bp_register_member_types', array( $this, 'register_member_types' ) );
		} else {
			add_action( 'bp_loaded', array( $this, 'register_member_types' ) );
		}

		add_filter( 'is_user_vgsr',   array( $this, 'is_user_vgsr'   ), 10, 2 );
		add_filter( 'is_user_lid',    array( $this, 'is_user_lid'    ), 10, 2 );
		add_filter( 'is_user_oudlid', array( $this, 'is_user_oudlid' ), 10, 2 );

		// Groups Component
		if ( bp_is_active( 'groups' ) ) {
			// add_filter( 'is_user_vgsr',   array( $this, 'in_vgsr_group'     ), 10, 2 );
			// add_filter( 'is_user_lid',    array( $this, 'in_leden_group'    ), 10, 2 );
			// add_filter( 'is_user_oudlid', array( $this, 'in_oudleden_group' ), 10, 2 );
		}
	}

	/** General ************************************************************/

	/**
	 * Hide nearly all BuddyPress pages for guests and non-vgsr users
	 *
	 * @since 0.1.0
	 *
	 * @uses is_buddypress()
	 * @uses is_user_vgsr()
	 * @uses bp_is_my_profile()
	 * @uses VGSR_BuddyPress::is_vgsr_only_component()
	 * @uses bp_is_register_page()
	 * @uses bp_is_activation_page()
	 * @uses remove_all_actions()
	 * @uses bp_do_404()
	 */
	public function bp_pages_404() {

		// Set the page to 404 when:
		// ... this is a BP page
		// ... AND the user is not VGSR or a guest
		if ( is_buddypress() && ! is_user_vgsr() ) {

			// Make an exception when:
			// ... this is the user's own profile AND this is a common component
			// ... OR this is the registration page
			// ... OR this is the activation page
			if ( ( bp_is_my_profile() && ! $this->is_vgsr_only_component() ) || bp_is_register_page() || bp_is_activation_page() )
				return;

			// Remove all other BP template routers when 404-ing
			remove_all_actions( 'bp_template_redirect' );

			// Make the page 404
			bp_do_404();
			return;
		}
	}

	/**
	 * Modify the return value for `is_buddypress()`
	 *
	 * @since 0.1.0
	 *
	 * @uses get_queried_object_id()
	 * @uses bp_core_get_directory_page_ids()
	 * 
	 * @param bool $is Is this a BuddyPress page?
	 * @return boolean Is BuddyPress
	 */
	public function is_buddypress( $is ) {

		// Set true for all directory pages, with active components or not
		if ( ! $is ) {
			$is = in_array( get_queried_object_id(), (array) bp_core_get_directory_page_ids( 'all' ) );
		}

		return $is;
	}

	/**
	 * Return selected BP components that are vgsr-only
	 *
	 * @since 0.1.0
	 *
	 * @uses apply_filters() Calls 'vgsr_only_bp_components'
	 * @return array VGSR-only BP components
	 */
	public function vgsr_only_bp_components() {
		return apply_filters( 'vgsr_only_bp_components', array(
			'activity',
			'friends',
			'groups',
			'messages',
			'notifications'
		) );
	}

	/**
	 * Return whether the given component is vgsr-only
	 *
	 * @since 0.1.0
	 *
	 * @uses bp_current_component()
	 * @uses VGSR_BuddyPress::vgsr_only_bp_components()
	 * 
	 * @param string $component Optional. Defaults to current component
	 * @return bool Component is vgsr-only
	 */
	public function is_vgsr_only_component( $component = '' ) {

		// Default to the current component
		if ( empty( $component ) ) {
			$component = bp_current_component();
		}

		$is = in_array( $component, $this->vgsr_only_bp_components() );

		return $is;
	}

	/**
	 * Define the default component for non-vgsr users
	 *
	 * When active, the activity component is set as the default component 
	 * in BP_Members_Component::setup_canonical_stack(). For non-vgsr
	 * displayed users, this results in a 404 when visiting members/<user>.
	 * This is solved by making the profile component default for this situation.
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::vgsr_only_bp_components()
	 * @uses bp_is_active()
	 * @uses is_user_vgsr()
	 */
	public function define_default_component() {
		$bp         = buddypress();
		$components = $this->vgsr_only_bp_components();

		// Define the default component when
		// ... the activity component is active
		// ... AND the activity component is vgsr-only
		// ... AND the displayed user is non-vgsr
		if ( bp_is_active( 'activity' ) && in_array( 'activity', $components ) && ! is_user_vgsr( bp_displayed_user_id() ) ) {
			if ( ! defined( 'BP_DEFAULT_COMPONENT' ) ) {

				// Make the default component 'profile'
				define( 'BP_DEFAULT_COMPONENT', ( 'xprofile' === $bp->profile->id ) ? 'profile' : $bp->profile->id );
			}
		}
	}

	/**
	 * Deactivate selected components for non-vgsr users
	 *
	 * Removes all important (visual) BP elements for non-vgsr users.
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::vgsr_only_bp_components()
	 * @uses bp_is_active()
	 * @uses is_user_vgsr()
	 * @uses remove_action()
	 * @uses bp_is_user()
	 * @uses do_action() Calls 'vgsr_bp_deactivate_component'
	 * @uses add_filter()
	 */
	public function deactivate_components() {
		$bp = buddypress();

		// Unhook selected components' elements
		foreach ( $this->vgsr_only_bp_components() as $component ) {

			// Skip logic when component is not active
			if ( ! bp_is_active( $component ) )
				continue;

			$the_component = $bp->$component;

			// Unhook default added component actions
			// Keep component globals and included files
			// See BP_Component::setup_actions()

			// Remove core component hooks for current user
			if ( ! is_user_vgsr( bp_loggedin_user_id() ) ) {
				remove_action( 'bp_setup_canonical_stack',  array( $the_component, 'setup_canonical_stack'  ), 10 );
				remove_action( 'bp_setup_admin_bar',        array( $the_component, 'setup_admin_bar'        ), $the_component->adminbar_myaccount_order );
				remove_action( 'bp_setup_cache_groups',     array( $the_component, 'setup_cache_groups'     ), 10 );
				remove_action( 'bp_register_post_types',    array( $the_component, 'register_post_types'    ), 10 );
				remove_action( 'bp_register_taxonomies',    array( $the_component, 'register_taxonomies'    ), 10 );
				remove_action( 'bp_add_rewrite_tags',       array( $the_component, 'add_rewrite_tags'       ), 10 );
				remove_action( 'bp_add_rewrite_rules',      array( $the_component, 'add_rewrite_rules'      ), 10 );
				remove_action( 'bp_add_permastructs',       array( $the_component, 'add_permastructs'       ), 10 );
				remove_action( 'bp_parse_query',            array( $the_component, 'parse_query'            ), 10 );
				remove_action( 'bp_generate_rewrite_rules', array( $the_component, 'generate_rewrite_rules' ), 10 );
			}

			// Remove display component hooks for displayed user
			if ( bp_is_user() && ! is_user_vgsr( bp_displayed_user_id() ) ) {
				remove_action( 'bp_setup_nav',              array( $the_component, 'setup_nav'              ), 10 );
				remove_action( 'bp_setup_title',            array( $the_component, 'setup_title'            ), 10 );
			}

			// Provide hook for further unhooking
			do_action( 'vgsr_bp_deactivate_component', $the_component, $component );
		}

		// Deactivate (but not remove) the component under certain conditions
		// for component checks after this point
		add_filter( 'bp_is_active', array( $this, 'bp_is_active' ), 10, 2 );
	}

	/**
	 * Modify the return value for `bp_is_active()`
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress:vgsr_only_bp_components()
	 * @uses is_user_vgsr()
	 * @uses in_the_loop()
	 * @uses bp_loggedin_user_id()
	 * @uses bp_displayed_user_id()
	 * 
	 * @param bool $retval Component is active
	 * @param string $component Component name
	 * @return bool Component is active
	 */
	public function bp_is_active( $retval, $component ) {

		// Component is non-vgsr
		if ( in_array( $component, $this->vgsr_only_bp_components() ) ) {

			// Check the current user
			if ( ! is_user_vgsr( bp_loggedin_user_id() ) ) {
				$retval = false;

			// When in the loop, check the displayed user
			} elseif ( in_the_loop() && bp_is_user() && ! is_user_vgsr( bp_displayed_user_id() ) ) {
				$retval = false;
			}
		}

		return $retval;
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

	/** Groups *************************************************************/

	/**
	 * Return the oud-leden group ID
	 * 
	 * @since 0.1.0
	 * 
	 * @uses get_site_option()
	 * 
	 * @return int Oud-leden group ID
	 */
	public function vgsr_group_id() {
		return (int) get_site_option( 'vgsr_bp_group_vgsr', 0 );
	}

	/**
	 * Return the oud-leden group ID
	 * 
	 * @since 0.1.0
	 * 
	 * @uses get_site_option()
	 * 
	 * @return int Oud-leden group ID
	 */
	public function leden_group_id() {
		return (int) get_site_option( 'vgsr_bp_group_leden', 0 );
	}

	/**
	 * Return the oud-leden group ID
	 * 
	 * @since 0.1.0
	 * 
	 * @uses get_site_option()
	 * 
	 * @return int Oud-leden group ID
	 */
	public function oudleden_group_id() {
		return (int) get_site_option( 'vgsr_bp_group_oudleden', 0 );
	}

	/**
	 * Return whether the given user is member of (a) BuddyPress group(s)
	 *
	 * @since 0.0.1
	 *
	 * @uses groups_get_groups()
	 * @uses VGSR_BuddyPress::get_group_hierarchy()
	 *
	 * @param int|array $group_id Group ID or ids
	 * @param int $user_id User ID
	 * @return bool User is group member
	 */
	public function user_in_group( $group_id = 0, $user_id = null ) {
		global $wpdb;

		// Bail when no group was provided
		if ( empty( $group_id ) )
			return false;

		// Default to the current user
		if ( null === $user_id ) {
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
	 * @uses VGSR_BuddyPress::vgsr_group_id()
	 * @uses VGSR_BuddyPress::leden_group_id()
	 * @uses VGSR_BuddyPress::oudleden_group_id()
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

		// Ensure leden + oud-leden are checked as well
		if ( $this->vgsr_group_id() === $group_id ) {
			$hierarchy += array( $this->leden_group_id(), $this->oudleden_group_id() );
		}

		return array_unique( $group_ids );
	}

	/**
	 * Return whether the given user is member of the vgsr group
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::user_in_group()
	 * @uses VGSR_BuddyPress::vgsr_group_id()
	 * 
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return int User is member of VGSR group
	 */
	public function in_vgsr_group( $is, $user_id = null ) {
		return ( $is ? $is : $this->user_in_group( $this->vgsr_group_id(), $user_id ) );
	}

	/**
	 * Return whether the given user is member of the leden group
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::user_in_group()
	 * @uses VGSR_BuddyPress::leden_group_id()
	 * 
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return int User is member of Leden group
	 */
	public function in_leden_group( $is, $user_id = null ) {
		return ( $is ? $is : $this->user_in_group( $this->leden_group_id(), $user_id ) );
	}

	/**
	 * Return whether the given user is member of the oud-leden group
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::user_in_group()
	 * @uses VGSR_BuddyPress::oudleden_group_id()
	 * 
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return int User is member of Oud-leden group
	 */
	public function in_oudleden_group( $is, $user_id = null ) {
		return ( $is ? $is : $this->user_in_group( $this->oudleden_group_id(), $user_id ) );
	}

	/** Member Types *******************************************************/

	/**
	 * Return the member type for lid
	 *
	 * @since 0.1.0
	 * 
	 * @uses apply_filters() Calls 'vgsr_bp_lid_member_type'
	 * 
	 * @return string Lid member type name
	 */
	public function lid_member_type() {
		return apply_filters( 'vgsr_bp_lid_member_type', 'lid' );
	}

	/**
	 * Return the member type for oud-lid
	 * 
	 * @since 0.1.0
	 * 
	 * @uses apply_filters() Calls 'vgsr_bp_oudlid_member_type'
	 * 
	 * @return string Oud-lid member type name
	 */
	public function oudlid_member_type() {
		return apply_filters( 'vgsr_bp_oudlid_member_type', 'oud-lid' );
	}

	/**
	 * Register VGSR member types
	 *
	 * Since BP 2.2.0
	 *
	 * @since 0.1.0
	 *
	 * @uses bp_register_member_type()
	 */
	public function register_member_types() {

		// Lid
		bp_register_member_type( $this->lid_member_type(), array(
			'labels' => array(
				'name'          => __( 'Lid',   'vgsr' ),
				'singular_name' => __( 'Lid',   'vgsr' ),
				'plural_name'   => __( 'Leden', 'vgsr' ),
			)
		) );

		// Oud-lid
		bp_register_member_type( $this->oudlid_member_type(), array(
			'labels' => array(
				'name'          => __( 'Oud-lid',   'vgsr' ),
				'singular_name' => __( 'Oud-lid',   'vgsr' ),
				'plural_name'   => __( 'Oud-leden', 'vgsr' ),
			)
		) );
	}

	/**
	 * Return whether the given user has the given member type
	 *
	 * @since 0.1.0
	 *
	 * @uses bp_has_member_type()
	 * @uses bp_get_member_type_object()
	 * @uses bp_get_member_type()
	 * 
	 * @param string $member_type Member type name
	 * @param int $user_id User ID
	 * @return boolean User has member type
	 */
	public function has_member_type( $member_type, $user_id = null ) {

		// Default to the current user
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Per BP 2.3+
		if ( function_exists( 'bp_has_member_type' ) ) {
			return bp_has_member_type( $user_id, $member_type );

		// Implementation of BP 2.3's `bp_has_member_type()`
		} else {

			// Bail if no valid member type was passed. 
			if ( empty( $member_type ) || ! bp_get_member_type_object( $member_type ) ) { 
				return false; 
			} 

			// Get all user's member types. 
			$types = bp_get_member_type( $user_id, false ); 
			if ( ! is_array( $types ) ) { 
				return false; 
			} 

			return in_array( $member_type, $types ); 
		}
	}

	/** Users **************************************************************/

	/**
	 * Filter whether the given user is VGSR.
	 * 
	 * @since 0.1.0
	 * 
	 * @uses VGSR_BuddyPress::is_user_lid()
	 * @uses VGSR_BuddyPress::is_user_oudlid()
	 * 
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return boolean User is VGSR
	 */
	public function is_user_vgsr( $is, $user_id = null ) {
		return ( $is ? $is : ( $this->is_user_lid( false, $user_id ) || $this->is_user_oudlid( false, $user_id ) ) );
	}

	/**
	 * Filter whether the given user is lid.
	 * 
	 * @since 0.1.0
	 * 
	 * @uses VGSR_BuddyPress::has_member_type()
	 * 
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return boolean User is lid
	 */
	public function is_user_lid( $is, $user_id = null ) {
		return ( $is ? $is : $this->has_member_type( $this->lid_member_type(), $user_id ) );
	}

	/**
	 * Filter whether the given user is oud-lid.
	 * 
	 * @since 0.1.0
	 * 
	 * @uses VGSR_BuddyPress::has_member_type()
	 * 
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return boolean User is oud-lid
	 */
	public function is_user_oudlid( $is, $user_id = null ) {
		return ( $is ? $is : $this->has_member_type( $this->oudlid_member_type(), $user_id ) );
	}
}

endif; // class_exists
