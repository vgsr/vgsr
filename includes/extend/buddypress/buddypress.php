<?php

/**
 * VGSR BuddyPress Extension
 *
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_BuddyPress' ) ) :
/**
 * The VGSR BuddyPress extension class
 *
 * @since 0.1.0
 */
class VGSR_BuddyPress {

	/**
	 * Holds the components that are exclusive for VGSR users
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
	}

	/**
	 * Include the required files
	 *
	 * @since 0.1.0
	 */
	private function includes() {
		require( $this->includes_dir . 'actions.php'   );
		require( $this->includes_dir . 'activity.php'  );
		require( $this->includes_dir . 'filters.php'   );
		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'settings.php'  );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 0.1.0
	 */
	private function setup_actions() {

		// Define member types and define user checks
		add_action( 'bp_register_member_types', array( $this, 'register_member_types' )        );
		add_filter( 'is_user_vgsr',             array( $this, 'is_user_vgsr'          ), 10, 2 );
		add_filter( 'is_user_lid',              array( $this, 'is_user_lid'           ), 10, 2 );
		add_filter( 'is_user_oudlid',           array( $this, 'is_user_oudlid'        ), 10, 2 );

		// Caps
		add_filter( 'vgsr_map_settings_meta_caps', array( $this, 'map_meta_caps' ), 10, 4 );

		// Members
		add_action( 'bp_set_member_type',                array( $this, 'set_member_type'            ), 10, 3 );
		add_action( 'bp_member_header_actions',          array( $this, 'add_member_header_actions'  )        );
		add_action( 'bp_members_directory_member_types', array( $this, 'add_members_directory_tabs' )        );
		add_filter( 'bp_legacy_theme_ajax_querystring',  array( $this, 'legacy_ajax_querystring'    ), 10, 7 );

		// Pages & Templates
		add_filter( 'bp_get_template_part',                      array( $this, 'get_template_part'          ), 20, 3 );
		add_filter( 'bp_get_button',                             array( $this, 'get_button'                 ), 20, 2 );
		add_filter( 'bp_get_directory_title',                    array( $this, 'directory_title'            ), 10, 2 );
		add_filter( 'bp_get_total_member_count',                 array( $this, 'total_member_count'         ),  9    );
		add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'dummy_post_set_post_parent' ), 11    );

		// Hide BuddyPress for non-vgsr
		add_filter( 'is_buddypress',  array( $this, 'is_buddypress'   )     );
		add_action( 'bp_core_loaded', array( $this, 'hide_buddypress' ), 20 );
		add_action( 'bp_setup_nav',   array( $this, 'bp_setup_nav'    ), 99 );
	}

	/** Hide BP ************************************************************/

	/**
	 * Modify the return value for `is_buddypress()`
	 *
	 * @since 0.1.0
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
	 * Setup actions and filters to hide BP for non-vgsr users
	 *
	 * @since 0.1.0
	 *
	 * @todo Invalidate BP's items in nav menus for non-vgsr
	 */
	public function hide_buddypress() {

		// Bail when the user can manage BP, but is non-vgsr (e.g. when testing)
		if ( bp_current_user_can( 'bp_moderate' ) )
			return;

		// If the user is not logged-in, hide BP completely
		if ( ! get_current_user_id() ) {
			/**
			 * Modify the list of active components before activating them
			 *
			 * At this point, the 'members' component isn't loaded yet, so we
			 * cannot use the member-type logic to determine vgsr-ship.
			 */
			add_filter( 'bp_active_components', '__return_empty_array' );
		}

		add_action( 'bp_register_taxonomies',   array( $this, 'deactivate_components'   ), 99    );
		add_action( 'bp_setup_canonical_stack', array( $this, 'setup_default_component' ),  5    ); // Before default priority
		add_action( 'bp_template_redirect',     array( $this, 'do_404'                  ),  1    );
		add_filter( 'get_comment_author_url',   array( $this, 'comment_author_url'      ), 12, 3 );
		add_filter( 'wp_setup_nav_menu_item',   array( $this, 'setup_nav_menu_item'     ), 11    ); // After BP's hook
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
	 * @param string $component Optional. Defaults to the current component
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
	 * Modify the logic order of registered component parts
	 *
	 * At this point, the member-type logic has just become available.
	 *
	 * @since 0.1.0
	 */
	public function deactivate_components() {

		// Get BuddyPress
		$bp = buddypress();

		// Unhook selected components' elements
		foreach ( $this->vgsr_bp_components() as $component ) {

			// Skip logic when component is not active
			if ( ! bp_is_active( $component ) )
				continue;

			$class = $bp->{$component};

			/**
			 * Unhook default added component actions, but keep component
			 * globals and included files.
			 * 
			 * @see BP_Component::setup_actions()
			 */
			if ( ! is_user_vgsr( bp_loggedin_user_id() ) ) {
				remove_action( 'bp_setup_nav',       array( $class, 'setup_nav'       ), 10 );
				remove_action( 'bp_setup_admin_bar', array( $class, 'setup_admin_bar' ), $class->adminbar_myaccount_order );
			}

			// Remove display component hooks for displayed user
			if ( bp_is_user() && ! is_user_vgsr( bp_displayed_user_id() ) ) {
				remove_action( 'bp_setup_nav',   array( $class, 'setup_nav'   ), 10 );
				remove_action( 'bp_setup_title', array( $class, 'setup_title' ), 10 );
			}

			// Provide hook for further unhooking
			do_action( 'vgsr_bp_deactivated_component', $component );
		}

		/**
		 * Mark the component as inactive (but do not remove) under certain
		 * conditions for component checks after this point.
		 *
		 * @todo See whether this is still viable, since `bp_is_active()` is also
		 *       already called before 'bp_init', i.e. on `after_setup_theme()` by
		 *       the theme-compat's buddypress-functions.php.
		 */
		// add_filter( 'bp_is_active', array( $this, 'bp_is_active' ), 10, 2 );
	}

	/**
	 * 404 exclusive BuddyPress pages for non-vgsr
	 *
	 * @since 0.1.0
	 */
	public function do_404() {

		// Bail when this is not BP or the user is vgsr
		if ( ! is_buddypress() || is_user_vgsr() )
			return;

		// Bail when:
		// ... this is the user's own profile AND this is a common component
		// ... OR this is the registration page
		// ... OR this is the activation page
		if ( ( bp_is_my_profile() && ! $this->is_vgsr_bp_component() ) || bp_is_register_page() || bp_is_activation_page() )
			return;

		// 404 and prevent components from loading their templates
		remove_all_actions( 'bp_template_redirect' );
		bp_do_404();
	}

	/**
	 * Define the default component for non-vgsr displayed users
	 *
	 * When active, the Activity component is set as the default component in
	 * BP_Members_Component::setup_canonical_stack(). For non-vgsr displayed
	 * users, with the activity component being exclusive, this results in a
	 * 404 when visiting 'members/<non-vgsr-user>'. This is solved by making
	 * the profile component default for this situation.
	 *
	 * @since 0.1.0
	 */
	public function setup_default_component() {

		// Define the default component when
		// ... the activity component is active
		// ... AND the activity component is exclusive
		// ... AND the displayed user is non-vgsr
		if ( bp_is_active( 'activity' ) && $this->is_vgsr_bp_component( 'activity' ) && ! is_user_vgsr( bp_displayed_user_id() ) ) {
			$bp = buddypress();

			// Set the default component to XProfile
			if ( ! defined( 'BP_DEFAULT_COMPONENT' ) ) {
				define( 'BP_DEFAULT_COMPONENT', ( 'xprofile' === $bp->profile->id ) ? 'profile' : $bp->profile->id );
			}
		}
	}

	/**
	 * Modify the return value for active components
	 *
	 * @since 0.1.0
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

	/**
	 * Invalidate BP menu items for non-vgsr users
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $menu_item Nav menu item
	 * @return WP_Post Nav menu item
	 */
	public function setup_nav_menu_item( $menu_item ) {

		// Post type menu item
		if ( 'post_type' == $menu_item->type ) {
			$page_ids  = bp_core_get_directory_page_ids( 'all' );
			$component = array_search( $menu_item->object_id, $page_ids );

			// Invalidate any component's directory page menu item
			if ( $component && ! is_user_vgsr() ) {
				$menu_item->_invalid = true;
			}
		}

		return $menu_item;
	}

	/**
	 * Modify the registered navigation elements
	 *
	 * @since 0.1.0
	 */
	public function bp_setup_nav() {

		// Bail when the current user is vgsr
		if ( is_user_vgsr() )
			return;

		// Settings
		remove_all_actions( 'bp_notification_settings' ); // Eliminates need for Email (admin) nav, but may be too restrictive
		bp_core_remove_subnav_item( bp_get_settings_slug(), 'notifications' );
		bp_core_remove_subnav_item( bp_get_settings_slug(), 'profile'       ); // See BP_XProfile_Component::setup_settings_nav()
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
				$caps = array( vgsr()->admin->minimum_capability );
				break;
		}

		return $caps;
	}

	/** Member Types *******************************************************/

	/**
	 * Register VGSR member types
	 *
	 * @since 0.1.0
	 */
	public function register_member_types() {

		// Walk our member types
		foreach ( vgsr_bp_member_types() as $type => $args ) {

			// Register the member type
			bp_register_member_type( $type, $args );
		}
	}

	/**
	 * Return whether the user has the given member type
	 *
	 * Ensures that the member-type taxonomy is registered before using it.
	 *
	 * @since 0.1.0
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
		 * called before `bp_register_taxonomies()`.
		 */
		if ( ! did_action( 'bp_register_taxonomies' ) ) {
			bp_register_taxonomies();
		}

		return bp_has_member_type( $user_id, $member_type );
	}

	/**
	 * Act when a user's member type has been changed or added
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id User ID
	 * @param string $member_type Member type name
	 * @param bool $append Whether the member type was appended
	 */
	public function set_member_type( $user_id, $member_type, $append ) {

		// When assigning Oud-lid or Ex-lid member types, remove the Lid member type
		if ( in_array( $member_type, array( vgsr_bp_oudlid_member_type(), vgsr_bp_exlid_member_type() ) ) ) {
			bp_remove_member_type( $user_id, vgsr_bp_lid_member_type() );
		}
	}

	/** Members ************************************************************/

	/**
	 * Display additional member profile action links
	 *
	 * @since 0.1.0
	 */
	public function add_member_header_actions() {

		// Bail when the user cannot moderate
		if ( ! bp_current_user_can( 'bp_moderate' ) )
			return;

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

		// Promote to Lid action
		if ( ! is_user_vgsr( bp_displayed_user_id() ) ) {
			$mt = bp_get_member_type_object( vgsr_bp_lid_member_type() );

			bp_button( array(
				'id'                => 'promote_member_lid',
				'component'         => 'members',
				'must_be_logged_in' => true,
				'block_self'        => true,
				'link_href'         => vgsr_bp_get_member_type_promote_url( $mt->name ),
				'link_title'        => __( 'Change the member type of this member.', 'vgsr' ),
				'link_text'         => sprintf( __( 'Promote to %s', 'vgsr' ), $mt->labels['singular_name'] ),
				'link_class'        => 'promote-member confirm'
			) );
		}

		// Promote to Oud-lid action
		if ( ! is_user_oudlid( bp_displayed_user_id() ) ) {
			$mt = bp_get_member_type_object( vgsr_bp_oudlid_member_type() );

			bp_button( array(
				'id'                => 'promote_member_oudlid',
				'component'         => 'members',
				'must_be_logged_in' => true,
				'block_self'        => true,
				'link_href'         => vgsr_bp_get_member_type_promote_url( $mt->name ),
				'link_title'        => __( 'Change the member type of this member.', 'vgsr' ),
				'link_text'         => sprintf( __( 'Promote to %s', 'vgsr' ), $mt->labels['singular_name'] ),
				'link_class'        => 'promote-member confirm'
			) );
		}
	}

	/**
	 * Add additional query tabs to the Members directory
	 *
	 * @since 0.1.0
	 */
	public function add_members_directory_tabs() {

		// Bail when current user is non-vgsr
		if ( ! is_user_vgsr() )
			return;

		// Add tabs for Lid and Oud-lid member type
		vgsr_bp_members_member_type_tab( vgsr_bp_lid_member_type()    );
		vgsr_bp_members_member_type_tab( vgsr_bp_oudlid_member_type() );
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
		if ( 'members' == $object && 0 == strpos( $object_scope, 'vgsr_member_type_' ) ) {
			$member_type = str_replace( 'vgsr_member_type_', '', $object_scope );
			$member_type = bp_get_member_type_object( $member_type );

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
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return boolean User is VGSR
	 */
	public function is_user_vgsr( $is, $user_id = null ) {
		return ( $is ? $is : ( $this->is_user_lid( false, $user_id ) || $this->is_user_oudlid( false, $user_id ) ) );
	}

	/**
	 * Filter whether the given user is Lid.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return boolean User is Lid
	 */
	public function is_user_lid( $is, $user_id = null ) {
		return ( $is ? $is : $this->has_member_type( vgsr_bp_lid_member_type(), $user_id ) );
	}

	/**
	 * Filter whether the given user is Oud-lid.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return boolean User is Oud-lid
	 */
	public function is_user_oudlid( $is, $user_id = null ) {
		return ( $is ? $is : $this->has_member_type( vgsr_bp_oudlid_member_type(), $user_id ) );
	}

	/**
	 * Modify the user query SQL to query by all vgsr member types
	 *
	 * @since 0.1.0
	 *
	 * @param array $sql User query SQL
	 */
	public function query_is_user_vgsr( $sql ) {

		// Define SQL clauses for member types
		$this->query_user_by_member_type( $sql, array( vgsr_bp_lid_member_type(), vgsr_bp_oudlid_member_type() ) );
	}

	/**
	 * Modify the user query SQL to query by Lid member type
	 *
	 * @since 0.1.0
	 *
	 * @param array $sql User query SQL
	 */
	public function query_is_user_lid( $sql ) {

		// Define SQL clauses for member types
		$this->query_user_by_member_type( $sql, vgsr_bp_lid_member_type() );
	}

	/**
	 * Modify the user query SQL to query by oudlid member type
	 *
	 * @since 0.1.0
	 *
	 * @param array $sql User query SQL
	 */
	public function query_is_user_oudlid( $sql ) {

		// Define SQL clauses for member types
		$this->query_users_by_member_type( $sql, vgsr_bp_oudlid_member_type() );
	}

	/**
	 * @todo Modify the SQL of WP_User_Query to query by member type
	 *
	 * @since 0.1.0
	 *
	 * @see BP_User_Query::prepare_user_ids_query()
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
	 * @param array $templates Templates to locate
	 * @param string $slug Template part slug requested
	 * @param string $name Template part name requested
	 * @return array Templates
	 */
	public function get_template_part( $templates, $slug, $name ) {

		// When blocking custom activity posting, prevent loading the Activity post form
		if ( vgsr_bp_block_activity_posting() && 'activity/post-form' == $slug ) {
			$templates = array( '' );
		}

		return $templates;
	}

	/**
	 * Filter the parsed button contents
	 *
	 * @since 0.1.0
	 *
	 * @param string $button Parsed button
	 * @param array $args Button arguments
	 * @return string Button
	 */
	public function get_button( $button, $args ) {

		// When blocking custom activity posting, remove the public message button
		if ( 'public_message' === $args['id'] && vgsr_bp_block_activity_posting() ) {
			$button = '';
		}

		return $button;
	}

	/**
	 * Modify the directory page's title
	 *
	 * @since 0.1.0
	 *
	 * @param string $title Page title
	 * @param string $component Component name
	 * @return string Page title
	 */
	public function directory_title( $title, $component ) {

		// Get directory page ids
		$page_ids = bp_core_get_directory_page_ids( 'all' );

		// Use the actual directory page's title. Only for BP pre-2.7
		if ( version_compare( buddypress()->version, '2.7', '<' ) && isset( $page_ids[ $component ] ) ) {
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
	 * This affects how non-pages like `bp_is_user()` are listed in breadcrumbs functions.
	 *
	 * @since 0.1.0
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
