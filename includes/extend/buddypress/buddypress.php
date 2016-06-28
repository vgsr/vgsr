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
 * VGSR BuddyPress extension
 *
 * @since 0.1.0
 */
class VGSR_BuddyPress {

	/**
	 * Holds the components that are exlusive for VGSR users.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	protected $components;

	/** Setup Methods ******************************************************/

	/**
	 * The main VGSR BuddyPress loader
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Define default class globals
	 *
	 * @since 0.1.0
	 * 
	 * @uses apply_filters() Calls 'vgsr_bp_components'
	 */
	private function setup_globals() {
		$vgsr = vgsr();

		/** Paths **********************************************************/

		$this->includes_dir = trailingslashit( $vgsr->extend_dir . 'buddypress' );
		$this->includes_url = trailingslashit( $vgsr->extend_url . 'buddypress' );

		/** Identifiers ****************************************************/

		$this->components   = apply_filters( 'vgsr_bp_components', array(
			'activity',
			'friends',
			'groups',
			'messages',
			'notifications'
		) );

		/** Supports *******************************************************/

		// BP Group Hierarchy plugin
		$this->bp_group_hierarchy = defined( 'BP_GROUP_HIERARCHY_VERSION' );
	}

	/**
	 * Include the required files
	 *
	 * @since 0.1.0
	 */
	private function includes() {
		require( $this->includes_dir . 'actions.php'   );
		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'settings.php'  );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 0.1.0
	 *
	 * @uses bp_is_active() To check for active components
	 * @uses add_action()
	 * @uses add_filter()
	 */
	private function setup_actions() {

		// Define member types. Dedicated hook per BP 2.3+
		$member_type_hook = function_exists( 'bp_has_member_type' ) ? 'bp_register_member_types' : 'bp_loaded';
		add_action( $member_type_hook, array( $this, 'register_member_types' ) );

		// Define vgsr membership by member type
		add_filter( 'is_user_vgsr',   array( $this, 'is_user_vgsr'   ), 10, 2 );
		add_filter( 'is_user_lid',    array( $this, 'is_user_lid'    ), 10, 2 );
		add_filter( 'is_user_oudlid', array( $this, 'is_user_oudlid' ), 10, 2 );

		// Hide most of BP for non-vgsr
		add_action( 'bp_template_redirect',     array( $this, 'bp_no_access'             ),  0    );
		add_action( 'bp_init',                  array( $this, 'deactivate_components'    ),  5    ); // After members component setup
		add_filter( 'is_buddypress',            array( $this, 'is_buddypress'            )        );
		add_action( 'bp_setup_canonical_stack', array( $this, 'define_default_component' ),  5    ); // Before default priority
		add_filter( 'get_comment_author_url',   array( $this, 'comment_author_url'       ), 12, 3 );

		// Settings
		add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_bp_settings_sections' );
		add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_bp_settings_fields'   );

		// Caps
		add_filter( 'vgsr_map_settings_meta_caps', array( $this, 'map_meta_caps' ), 10, 4 );

		// Members
		add_action( 'bp_set_member_type',                array( $this, 'set_member_type'            ), 10, 3 );
		add_action( 'bp_member_header_actions',          array( $this, 'add_member_header_actions'  )        );
		add_action( 'bp_members_directory_member_types', array( $this, 'add_members_directory_tabs' )        );
		add_filter( 'bp_legacy_theme_ajax_querystring',  array( $this, 'legacy_ajax_querystring'    ), 10, 7 );

		// Pages & Templates
		add_filter( 'bp_get_template_part',                      array( $this, 'get_template_part'          ), 20, 3 );
		add_filter( 'bp_get_directory_title',                    array( $this, 'directory_title'            ), 10, 2 );
		add_filter( 'bp_get_total_member_count',                 array( $this, 'total_member_count'         ),  9    );
		add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'dummy_post_set_post_parent' ), 11    );
	}

	/** Hide BP ************************************************************/

	/**
	 * Hide exclusive BuddyPress pages for the unpriviledged
	 *
	 * @since 0.1.0
	 *
	 * @uses is_buddypress()
	 * @uses is_user_vgsr()
	 * @uses bp_is_my_profile()
	 * @uses VGSR_BuddyPress::is_vgsr_bp_component()
	 * @uses bp_is_register_page()
	 * @uses bp_is_activation_page()
	 * @uses bp_core_no_access()
	 */
	public function bp_no_access() {

		// Set the page to 404 when:
		// ... this is a BP page
		// ... AND the user is not VGSR or a guest
		if ( is_buddypress() && ! is_user_vgsr() ) {

			// Make an exception when:
			// ... this is the user's own profile AND this is a common component
			// ... OR this is the registration page
			// ... OR this is the activation page
			if ( ( bp_is_my_profile() && ! $this->is_vgsr_bp_component() ) || bp_is_register_page() || bp_is_activation_page() )
				return;

			// Let BP handle the redirection (default = wp-login.php)
			bp_core_no_access();
		}
	}

	/**
	 * Modify the return value for `is_buddypress()`
	 *
	 * @since 0.1.0
	 *
	 * @uses is_page()
	 * @uses get_queried_object_id()
	 * @uses bp_core_get_directory_page_ids()
	 *
	 * @param bool $is Is this a BuddyPress page?
	 * @return boolean Is BuddyPress
	 */
	public function is_buddypress( $is ) {

		if ( ! $is && is_page() ) {
			// Define true for all directory pages, whether their component
			// is active or not. By default, an inactive component's directory
			// page has no content, but continues to live as an ordinary page.
			$is = in_array( get_queried_object_id(), (array) bp_core_get_directory_page_ids( 'all' ) );
		}

		return $is;
	}

	/**
	 * Return exclusive BP components
	 *
	 * @since 0.1.0
	 *
	 * @return array Exclusive BP components
	 */
	public function vgsr_bp_components() {
		return $this->components;
	}

	/**
	 * Return whether the given component is exclusive
	 *
	 * @since 0.1.0
	 *
	 * @uses bp_current_component()
	 * @uses VGSR_BuddyPress::vgsr_bp_components()
	 *
	 * @param string $component Optional. Defaults to current component
	 * @return bool Component is exclusive
	 */
	public function is_vgsr_bp_component( $component = '' ) {

		// Default to the current component
		if ( empty( $component ) ) {
			$component = bp_current_component();
		}

		$is = in_array( $component, $this->vgsr_bp_components() );

		return $is;
	}

	/**
	 * Define the default component for non-vgsr displayed users
	 *
	 * When active, the activity component is set as the default component in
	 * BP_Members_Component::setup_canonical_stack(). For non-vgsr displayed
	 * users, with the activity component being exclusive, this results in a
	 * 404 when visiting 'members/<non-vgsr-user>'. This is solved by making
	 * the profile component default for this situation.
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::vgsr_bp_components()
	 * @uses bp_is_active()
	 * @uses is_user_vgsr()
	 */
	public function define_default_component() {
		$bp = buddypress();

		// Define the default component when
		// ... the activity component is active
		// ... AND the activity component is exclusive
		// ... AND the displayed user is non-vgsr
		if ( bp_is_active( 'activity' ) && $this->is_vgsr_bp_component( 'activity' ) && ! is_user_vgsr( bp_displayed_user_id() ) ) {

			// Set the default component to XProfile
			if ( ! defined( 'BP_DEFAULT_COMPONENT' ) ) {
				define( 'BP_DEFAULT_COMPONENT', ( 'xprofile' === $bp->profile->id ) ? 'profile' : $bp->profile->id );
			}
		}
	}

	/**
	 * Deactivate selected components for non-vgsr users
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::vgsr_bp_components()
	 * @uses bp_is_active()
	 * @uses is_user_vgsr()
	 * @uses remove_action()
	 * @uses bp_is_user()
	 * @uses do_action() Calls 'vgsr_bp_deactivated_component'
	 * @uses add_filter()
	 */
	public function deactivate_components() {
		$bp = buddypress();

		// Unhook selected components' elements
		foreach ( $this->vgsr_bp_components() as $component ) {

			// Skip logic when component is not active
			if ( ! bp_is_active( $component ) )
				continue;

			$class = $bp->{$component};

			// Unhook default added component actions
			// Keep component globals and included files
			// See BP_Component::setup_actions()

			// Remove core component hooks for current user
			if ( ! is_user_vgsr( bp_loggedin_user_id() ) ) {
				remove_action( 'bp_setup_canonical_stack',  array( $class, 'setup_canonical_stack'  ), 10 );
				remove_action( 'bp_setup_admin_bar',        array( $class, 'setup_admin_bar'        ), $class->adminbar_myaccount_order );
				remove_action( 'bp_setup_cache_groups',     array( $class, 'setup_cache_groups'     ), 10 );
				remove_action( 'bp_register_post_types',    array( $class, 'register_post_types'    ), 10 );
				remove_action( 'bp_register_taxonomies',    array( $class, 'register_taxonomies'    ), 10 );
				remove_action( 'bp_add_rewrite_tags',       array( $class, 'add_rewrite_tags'       ), 10 );
				remove_action( 'bp_add_rewrite_rules',      array( $class, 'add_rewrite_rules'      ), 10 );
				remove_action( 'bp_add_permastructs',       array( $class, 'add_permastructs'       ), 10 );
				remove_action( 'bp_parse_query',            array( $class, 'parse_query'            ), 10 );
				remove_action( 'bp_generate_rewrite_rules', array( $class, 'generate_rewrite_rules' ), 10 );
			}

			// Remove display component hooks for displayed user
			if ( bp_is_user() && ! is_user_vgsr( bp_displayed_user_id() ) ) {
				remove_action( 'bp_setup_nav',   array( $class, 'setup_nav'   ), 10 );
				remove_action( 'bp_setup_title', array( $class, 'setup_title' ), 10 );
			}

			// Provide hook for further unhooking
			do_action( 'vgsr_bp_deactivated_component', $class, $component );
		}

		// Mark the component as inactive (but do not remove) under certain
		// conditions for component checks after this point.
		add_filter( 'bp_is_active', array( $this, 'bp_is_active' ), 10, 2 );
	}

	/**
	 * Modify the return value for active components
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress:vgsr_bp_components()
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

		// Component is vgsr specific
		if ( $this->is_vgsr_bp_component( $component ) ) {

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

	/**
	 * Remove the comment author member url for non-vgsr users
	 *
	 * @since 0.1.0
	 *
	 * @see bp_core_filter_comments() Assigns member urls to the comment object.
	 *
	 * @uses bp_loggedin_user_id()
	 * @uses is_user_vgsr()
	 *
	 * @param string $url Comment author url
	 * @param int $comment_id Comment ID
	 * @param WP_Comment $comment Comment object
	 * @return string Comment author url
	 */
	public function comment_author_url( $url, $comment_id, $comment ) {

		// Define local variable
		$user_id = bp_loggedin_user_id();

		// Hide member-url for non-vgsr, non-self, vgsr-member authors
		if ( ! is_user_vgsr( $user_id ) && $user_id != $comment->user_id && is_user_vgsr( $comment->user_id ) ) {
			$url = '';
		}

		return $url;
	}

	/** Capabilities *******************************************************/

	/**
	 * Map VGSR BuddyPress settings capabilities
	 *
	 * @since 0.1.0
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
	 * The following code in this section is legacy stuff since the
	 * introduction and use of the Member Type API. Though the logic
	 * will remain here for a while for it may be considered valuable
	 * in future projects.
	 *
	 *  // Groups Component
	 *  if ( bp_is_active( 'groups' ) ) {
	 *  	add_filter( 'is_user_vgsr',   array( $this, 'in_vgsr_group'     ), 10, 2 );
	 *  	add_filter( 'is_user_lid',    array( $this, 'in_leden_group'    ), 10, 2 );
	 *  	add_filter( 'is_user_oudlid', array( $this, 'in_oudleden_group' ), 10, 2 );
	 *  }
	 */

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
	 * @since 0.1.0
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
	 * Return the collection of VGSR member types
	 *
	 * @since 0.1.0
	 *
	 * @uses apply_filters() Calls 'vgsr_bp_member_types'
	 * @return array Member type data
	 */
	public function vgsr_member_types() {
		return (array) apply_filters( 'vgsr_bp_member_types', array(

			// Lid
			$this->lid_member_type() => array(
				'labels' => array(
					'name'          => __( 'Leden', 'vgsr' ),
					'singular_name' => __( 'Lid',   'vgsr' ),
					'plural_name'   => __( 'Leden', 'vgsr' ),
				)
			),

			// Oud-lid
			$this->oudlid_member_type() => array(
				'labels' => array(
					'name'          => __( 'Oud-leden', 'vgsr' ),
					'singular_name' => __( 'Oud-lid',   'vgsr' ),
					'plural_name'   => __( 'Oud-leden', 'vgsr' ),
				)
			),

			// Ex-lid
			$this->exlid_member_type() => array(
				'labels' => array(
					'name'          => __( 'Ex-leden', 'vgsr' ),
					'singular_name' => __( 'Ex-lid',   'vgsr' ),
					'plural_name'   => __( 'Ex-leden', 'vgsr' ),
				)
			),
		) );
	}

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
		return apply_filters( 'vgsr_bp_oudlid_member_type', 'oudlid' );
	}

	/**
	 * Return the member type for ex-lid
	 *
	 * @since 0.1.0
	 *
	 * @uses apply_filters() Calls 'vgsr_bp_exlid_member_type'
	 *
	 * @return string Oud-lid member type name
	 */
	public function exlid_member_type() {
		return apply_filters( 'vgsr_bp_exlid_member_type', 'exlid' );
	}

	/**
	 * Register VGSR member types
	 *
	 * Since BP 2.2.0
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress:vgsr_member_types()
	 * @uses bp_register_member_type()
	 */
	public function register_member_types() {

		// Walk our member types
		foreach ( $this->vgsr_member_types() as $type => $data ) {

			// Register the member type
			bp_register_member_type( $type, $data );
		}
	}

	/**
	 * Return whether the given user has the given member type
	 *
	 * @since 0.1.0
	 *
	 * @uses bp_register_taxonomies()
	 * @uses bp_has_member_type()
	 *
	 * @param string $member_type Member type name
	 * @param int $user_id Optional. User ID. Defaults to current user.
	 * @return boolean User has member type
	 */
	public function has_member_type( $member_type, $user_id = null ) {

		// Default to the current user
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		/*
		 * Ensure BP's taxonomies are registered in case this is
		 * called before `bp_init()`.
		 */
		if ( ! did_action( 'bp_init' ) ) {
			bp_register_taxonomies();
		}

		return bp_has_member_type( $user_id, $member_type );
	}

	/**
	 * Act when a user's member type has been changed or added
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::oudlid_member_type()
	 * @uses VGSR_BuddyPress::exlid_member_type()
	 * @uses is_user_lid()
	 * @uses bp_remove_member_type()
	 * @uses VGSR_BuddyPress::lid_member_type()
	 *
	 * @param int $user_id User ID
	 * @param string $member_type Member type name
	 * @param bool $append Whether the member type was appended
	 */
	public function set_member_type( $user_id, $member_type, $append ) {

		// Remove the lid member type for new oud-lid or ex-lid members
		if ( in_array( $member_type, array( $this->oudlid_member_type(), $this->exlid_member_type() ) ) && is_user_lid( $user_id ) ) {
			bp_remove_member_type( $user_id, $this->lid_member_type() );
		}
	}

	/** Members ************************************************************/

	/**
	 * Output additional member action links
	 *
	 * @since 0.1.0
	 *
	 * @uses bp_current_user_can()
	 * @uses bp_button()
	 * @uses is_user_vgsr()
	 * @uses is_user_lid()
	 * @uses vgsr_bp_get_member_type_promote_url()
	 * @uses bp_get_member_type_object()
	 * @uses VGSR_BuddyPress::lid_member_type()
	 * @uses VGSR_BuddyPress::oudlid_member_type()
	 */
	public function add_member_header_actions() {

		// For moderators
		if ( bp_current_user_can( 'bp_moderate' ) ) {

			// Edit user in wp-admin link
			bp_button( array(
				'id'                => 'dashboard_profile',
				'component'         => 'members',
				'must_be_logged_in' => true,
				'block_self'        => false,
				'link_href'         => add_query_arg( array( 'user_id' => bp_displayed_user_id() ), admin_url( 'user-edit.php' ) ),
				'link_title'        => __( 'Edit this user in the admin.', 'vgsr' ),
				'link_text'         => __( 'Dashboard Profile', 'vgsr' ),
				'link_class'        => 'dashboard-profile'
			) );

			// Promote to lid action
			if ( ! is_user_vgsr( bp_displayed_user_id() ) ) {
				bp_button( array(
					'id'                => 'promote_member_lid',
					'component'         => 'members',
					'must_be_logged_in' => true,
					'block_self'        => true,
					'link_href'         => vgsr_bp_get_member_type_promote_url( $this->lid_member_type() ),
					'link_title'        => __( 'Change the member type of this member.', 'vgsr' ),
					'link_text'         => sprintf( __( 'Promote to %s', 'vgsr' ), bp_get_member_type_object( $this->lid_member_type() )->labels['singular_name'] ),
					'link_class'        => 'promote-member confirm'
				) );
			}

			// Promote to oud-lid action
			if ( ! is_user_oudlid( bp_displayed_user_id() ) ) {
				bp_button( array(
					'id'                => 'promote_member_oudlid',
					'component'         => 'members',
					'must_be_logged_in' => true,
					'block_self'        => true,
					'link_href'         => vgsr_bp_get_member_type_promote_url( $this->oudlid_member_type() ),
					'link_title'        => __( 'Change the member type of this member.', 'vgsr' ),
					'link_text'         => sprintf( __( 'Promote to %s', 'vgsr' ), bp_get_member_type_object( $this->oudlid_member_type() )->labels['singular_name'] ),
					'link_class'        => 'promote-member confirm'
				) );
			}
		}
	}

	/**
	 * Add additional query tabs to the Members directory
	 *
	 * @since 0.1.0
	 *
	 * @uses is_user_vgsr()
	 * @uses vgsr_bp_members_member_type_tab()
	 * @uses VGSR_BuddyPress::lid_member_type()
	 * @uses VGSR_BuddyPress::oudlid_member_type()
	 */
	public function add_members_directory_tabs() {

		// Bail when current user is not vgsr
		if ( ! is_user_vgsr() )
			return;

		// Add tabs for Lid and Oud-lid member type
		vgsr_bp_members_member_type_tab( $this->lid_member_type() );
		vgsr_bp_members_member_type_tab( $this->oudlid_member_type() );
	}

	/**
	 * Modify the ajax query string from the legacy theme
	 *
	 * @since 0.1.0
	 *
	 * @param string $query_string        The query string we are working with.
	 * @param string $object              The type of page we are on.
	 * @param string $object_filter       The current object filter.
	 * @param string $object_scope        The current object scope.
	 * @param string $object_page         The current object page.
	 * @param string $object_search_terms The current object search terms.
	 * @param string $object_extras       The current object extras.
	 * @return string The query string
	 */
	public function legacy_ajax_querystring( $query_string, $object, $object_filter, $object_scope, $object_page, $object_search_terms, $object_extras ) {

		// Handle the members member type scope
		if ( 'members' == $object && 0 == strpos( $object_scope, 'member_type_' ) ) {
			$member_type = bp_get_member_type_object( str_replace( 'member_type_', '', $object_scope ) );

			// Query only member type'd users
			if ( ! empty( $member_type ) ) {
				$query_string .= "&member_type__in={$member_type->name}";
			}
		}

		return $query_string;
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
	 * @uses VGSR_BuddyPress::lid_member_type()
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
	 * @uses VGSR_BuddyPress::oudlid_member_type()
	 *
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return boolean User is oud-lid
	 */
	public function is_user_oudlid( $is, $user_id = null ) {
		return ( $is ? $is : $this->has_member_type( $this->oudlid_member_type(), $user_id ) );
	}

	/**
	 * Modify the user query SQL to query by all vgsr member types
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::query_users_by_member_type()
	 * @uses VGSR_BuddyPress::lid_member_type()
	 * @uses VGSR_BuddyPress::oudlid_member_type()
	 * @param array $sql User query SQL
	 */
	public function query_is_user_vgsr( $sql ) {

		// Define SQL clauses for member types
		$this->query_user_by_member_type( $sql, array( $this->lid_member_type(), $this->oudlid_member_type() ) );
	}

	/**
	 * Modify the user query SQL to query by lid member type
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::query_users_by_member_type()
	 * @uses VGSR_BuddyPress::lid_member_type()
	 * @param array $sql User query SQL
	 */
	public function query_is_user_lid( $sql ) {

		// Define SQL clauses for member types
		$this->query_user_by_member_type( $sql, $this->lid_member_type() );
	}

	/**
	 * Modify the user query SQL to query by oudlid member type
	 *
	 * @since 0.1.0
	 *
	 * @uses VGSR_BuddyPress::query_users_by_member_type()
	 * @uses VGSR_BuddyPress::oudlid_member_type()
	 * @param array $sql User query SQL
	 */
	public function query_is_user_oudlid( $sql ) {

		// Define SQL clauses for member types
		$this->query_users_by_member_type( $sql, $this->oudlid_member_type() );
	}

	/**
	 * @todo Modify the SQL of WP_User_Query to query by member type
	 *
	 * @since 0.1.0
	 *
	 * @see BP_User_Query::prepare_user_ids_query()
	 *
	 * @uses VGSR_BuddyPress::query_users_by_member_type()
	 * @uses bp_get_member_type_object()
	 * @uses switch_to_blog()
	 * @uses restore_current_blog()
	 *
	 * @param array $sql User query SQL, modified by reference
	 * @param string|array Member type name(s)
	 */
	private function query_user_by_member_type( &$sql, $member_type = '' ) {
		global $wpdb;

		$member_types = array();

		if ( ! is_array( $member_type ) ) {
			$member_type = preg_split( '/[,\s+]/', $member_type );
		}

		foreach ( $member_type as $type ) {
			if ( ! bp_get_member_type_object( $type ) ) {
				continue;
			}
			$member_types[] = $type;
		}

		// Bail when no valid member types provided
		if ( empty( $member_types ) )
			return;

		// Define member type tax query
		$tax_query = new WP_Tax_Query( array(
			array(
				'taxonomy' => 'bp_member_type',
				'field'    => 'name',
				'operator' => 'IN',
				'terms'    => $member_types,
			),
		) );

		// Switch to the root blog, where member type taxonomies live.
		if ( ! $root = bp_is_root_blog() ) {
			switch_to_blog( bp_get_root_blog_id() );
		}

		// Generete SQL clause
		$tq_sql_clauses = $tax_query->get_sql( 'u', 'ID' );

		if ( ! $root ) {
			restore_current_blog();
		}

		// Grab the first term_relationships clause and convert to a subquery.
		if ( preg_match( '/' . $wpdb->term_relationships . '\.term_taxonomy_id IN \([0-9, ]+\)/', $tq_sql_clauses['where'], $matches ) ) {
			$sql['where']['member_type'] = "u.ID IN ( SELECT object_id FROM $wpdb->term_relationships WHERE {$matches[0]} )";
		} elseif ( false !== strpos( $tq_sql_clauses['where'], '0 = 1' ) ) {
			$sql['where']['member_type'] = $this->no_results['where'];
		}
	}

	/** Pages & Templates **************************************************/

	/**
	 * Filter the template part in BP's template loading
	 *
	 * @since 0.1.0
	 *
	 * @uses vgsr_bp_is_activity_posting_blocked()
	 *
	 * @param array $templates Templates to locate
	 * @param string $slug Template part slug requested
	 * @param string $name Template part name requested
	 * @return array Templates
	 */
	public function get_template_part( $templates, $slug, $name ) {

		// When blocking custom activity posting, prevent loading the Activity post form
		if ( vgsr_bp_is_activity_posting_blocked() && 'activity/post-form' == $slug ) {
			$templates = array();
		}

		return $templates;
	}

	/**
	 * Modify the directory page's page title
	 *
	 * @since 0.1.0
	 *
	 * @uses bp_core_get_directory_page_ids()
	 * @uses get_the_title()
	 * @uses bp_get_member_type_object()
	 * @uses bp_get_current_member_type()
	 *
	 * @param string $title Page title
	 * @param string $component Component name
	 * @return string Page title
	 */
	public function directory_title( $title, $component ) {

		// Get directory page ids
		$page_ids = bp_core_get_directory_page_ids( 'all' );

		// Use the actual directory page's title
		if ( isset( $page_ids[ $component ] ) ) {
			$title = get_the_title( $page_ids[ $component ] );
		}

		// For member type directories, modify the title as well
		if ( $member_type = bp_get_member_type_object( bp_get_current_member_type() ) ) {
			$title = $member_type->labels['name'];
		}

		return $title;
	}

	/**
	 * Modify the total member count
	 *
	 * @since 0.1.0
	 *
	 * @uses bp_get_current_member_type()
	 * @uses vgsr_bp_get_total_member_count()
	 *
	 * @param int $count Member count
	 * @return int Total member count
	 */
	public function total_member_count( $count ) {

		$args = array();

		// Default to the current member type
		if ( $member_type = bp_get_current_member_type() ) {
			$args['member_type__in'] = $member_type;
		}

		// Get the real total member count
		$count = vgsr_bp_get_total_member_count( $args );

		return $count;	
	}

	/**
	 * Define BuddyPress's dummy global post's post parent correctly
	 *
	 * @since 0.1.0
	 *
	 * @uses is_buddypress()
	 * @uses bp_is_user()
	 * @uses bp_is_single_item()
	 * @uses bp_current_component()
	 * @uses get_post_ancestors()
	 *
	 * @global WP_Post $post
	 */
	public function dummy_post_set_post_parent() {
		global $post;

		// Bail when there is no global post
		if ( ! $post )
			return;

		// Get BuddyPress
		$bp = buddypress();

		// Define local variable
		$post_parent = false;

		// When the post parent is not defined
		if ( is_buddypress() && ( bp_is_user() || bp_is_single_item() || bp_get_current_member_type() ) && 0 == $post->post_parent ) {

			// Default all user pages to Members
			if ( bp_is_user() && ! bp_is_single_activity() ) {
				$component = 'members';
			} else {
				$component = bp_current_component();
			}

			// Define parent when component has a directory
			if ( ! empty( $bp->pages->{$component}->id ) ) {
				$post_parent = $bp->pages->{$component}->id;
			}
		}

		// Assign the global post's post parent
		if ( $post_parent ) {
			$post->post_parent = $post_parent;
		}
	}
}

endif; // class_exists
