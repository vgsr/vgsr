<?php

/**
 * VGSR Extension for BuddyPress
 *
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_BuddyPress' ) ) :
/**
 * The VGSR BuddyPress class
 *
 * @since 0.1.0
 */
class VGSR_BuddyPress {

	/**
	 * Holds all users per member type
	 *
	 * @since 0.1.0
	 * @var array
	 */
	protected $member_type_users = array();

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
	 */
	private function setup_globals() {

		/** Paths **********************************************************/

		$this->includes_dir = trailingslashit( vgsr()->extend_dir . 'buddypress' );
		$this->includes_url = trailingslashit( vgsr()->extend_url . 'buddypress' );

		// Assets
		$this->assets_dir = trailingslashit( $this->includes_dir . 'assets' );
		$this->assets_url = trailingslashit( $this->includes_url . 'assets' );

		/** Misc ***********************************************************/

		$this->minimum_capability = is_multisite() ? 'manage_network_options' : 'manage_options';
	}

	/**
	 * Include the required files
	 *
	 * @since 0.1.0
	 */
	private function includes() {
		require( $this->includes_dir . 'actions.php'   );
		require( $this->includes_dir . 'activity.php'  );
		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'members.php'   );
		require( $this->includes_dir . 'settings.php'  );
		require( $this->includes_dir . 'xprofile.php'  );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 0.1.0
	 */
	private function setup_actions() {

		// General
		add_action( 'bp_init',       array( $this, 'bp_init'       ), 11 );
		add_filter( 'is_buddypress', array( $this, 'is_buddypress' )     );

		// Admin
		add_filter( 'vgsr_admin_redirect_url', array( $this, 'admin_redirect_url' ) );

		// Define member types and setup user checks
		add_action( 'bp_register_member_types',        array( $this, 'register_member_types'  )        );
		add_filter( 'is_user_vgsr',                    array( $this, 'is_user_vgsr'           ), 10, 2 );
		add_filter( 'is_user_lid',                     array( $this, 'is_user_lid'            ), 10, 2 );
		add_filter( 'is_user_oudlid',                  array( $this, 'is_user_oudlid'         ), 10, 2 );
		add_filter( 'is_user_exlid',                   array( $this, 'is_user_exlid'          ), 10, 2 );
		add_filter( 'vgsr_pre_user_query',             array( $this, 'pre_user_query'         ), 10, 2 );
		add_filter( 'bp_user_query_uid_clauses',       array( $this, 'user_query_uid_clauses' ), 10, 2 );
		add_action( 'bp_members_admin_user_metaboxes', array( $this, 'admin_user_metaboxes'   ), 10, 2 );

		// Caps
		add_filter( 'vgsr_map_settings_meta_caps', array( $this, 'map_meta_caps' ), 10, 4 );

		// Members
		add_action( 'bp_set_member_type', array( $this, 'set_member_type' ), 10, 3 );

		// Pages & Templates
		add_action( 'bp_enqueue_scripts',                        array( $this, 'enqueue_scripts'            ), 90    );
		add_filter( 'bp_get_template_part',                      array( $this, 'get_template_part'          ), 20, 3 );
		add_filter( 'bp_get_button',                             array( $this, 'get_button'                 ), 20, 2 );
		add_filter( 'bp_get_directory_title',                    array( $this, 'directory_title'            ), 10, 2 );
		add_filter( 'bp_get_total_member_count',                 array( $this, 'total_member_count'         ),  2    );
		add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'dummy_post_set_post_parent' ), 11    );

		// Hide BuddyPress for non-vgsr, not for admins
		if ( ! current_user_can( $this->minimum_capability ) ) {
			add_action( 'bp_core_loaded', array( $this, 'hide_buddypress' ), 20 );
			add_action( 'bp_setup_nav',   array( $this, 'bp_setup_nav'    ), 99 );
		}
	}

	/** General ************************************************************/

	/**
	 * Setup general BP manipulations for VGSR
	 *
	 * @since 0.1.2
	 */
	public function bp_init() {

		// Get BuddyPress
		$bp = buddypress();

		// Do not allow simple users to edit their member type(s)
		if ( ! current_user_can( 'bp_moderate' ) && is_admin() ) {
			remove_action( 'bp_members_admin_load', array( $bp->members->admin, 'process_member_type_update' ) );
		}
	}

	/** Admin **************************************************************/

	/**
	 * Modify the vgsr admin redirect url
	 *
	 * @since 0.2.0
	 *
	 * @param string $location Redirect url
	 * @return string Redirect url
	 */
	public function admin_redirect_url( $location ) {

		// For non-admin non-vgsr users block all admin pages *and* the profile page
		if ( ! current_user_can( $this->minimum_capability ) && ! is_user_vgsr() ) {

			// Always redirect to the user's own BP profile
			$location = bp_core_get_user_domain( bp_loggedin_user_id() );
		}

		return $location;
	}

	/** Hide BP ************************************************************/

	/**
	 * Modify the return value for `is_buddypress()`
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is Is this a BuddyPress page?
	 * @return bool Is BuddyPress
	 */
	public function is_buddypress( $is ) {

		/**
		 * Define true for all directory pages, whether their component
		 * is active or not. By default, an inactive component's directory
		 * page has no content, but continues to live as an ordinary page.
		 */
		if ( ! $is && is_page() ) {
			$is = in_array( get_queried_object_id(), (array) bp_core_get_directory_page_ids( 'all' ) );
		}

		return $is;
	}

	/**
	 * Setup actions and filters to hide BP for non-vgsr users
	 *
	 * @since 0.1.0
	 */
	public function hide_buddypress() {
		add_filter( 'bp_active_components',     array( $this, 'active_components'       )        );
		add_action( 'bp_template_redirect',     array( $this, 'block_components'        ),  1    ); // Before bp_actions and bp_screens
		add_action( 'bp_setup_canonical_stack', array( $this, 'setup_default_component' ),  5    ); // Before default priority
		add_filter( 'get_comment_author_url',   array( $this, 'comment_author_url'      ), 12, 3 );
		add_filter( 'wp_setup_nav_menu_item',   array( $this, 'setup_nav_menu_item'     ), 11    ); // After BP's hook
	}

	/**
	 * Modify the list of active components for the current user
	 *
	 * @since 0.1.0
	 *
	 * @param array $components Active components
	 * @return array Active components
	 */
	public function active_components( $components ) {

		// Default to array
		if ( empty( $components ) ) {
			$components = array();
		}

		// If the user is not logged-in, completely unload BP
		if ( ! get_current_user_id() ) {
			$components = array();

		// Hide for user, so don't load exclusive components
		} elseif ( vgsr_bp_hide_for_user() ) {
			$components = array_diff_key( $components, array_flip( vgsr_bp_components() ) );
		}

		return $components;
	}

	/**
	 * Block BuddyPress pages for non-vgsr by 404-ing or unhooking
	 *
	 * @since 0.1.0
	 */
	public function block_components() {

		// Hide BP for the current user and page is BP
		if ( is_buddypress() && vgsr_bp_hide_for_user() ) {

			// When not fully hiding BuddyPress, redirect to own profile from the members directory
			if ( ! vgsr_hide_buddypress() && bp_is_members_directory() ) {
				bp_core_redirect( bp_core_get_user_domain( bp_loggedin_user_id() ) );
				exit;
			}

			// 404 when fully hiding BP or not on own profile
			if ( vgsr_hide_buddypress() || ! bp_is_my_profile() ) {

				// 404 and prevent components from loading their templates
				remove_all_actions( 'bp_template_redirect' );
				bp_do_404();
				return;
			}

		// Viewing a non-vgsr user's profile
		} elseif ( bp_is_user() && ! is_user_vgsr( bp_displayed_user_id() ) ) {

			// Remove component nav items properly
			$this->unhook_bp_nav_items();

			// Unhook theme-compat component hooks
			$this->unhook_theme_compat();
		}
	}

	/**
	 * Unhook exclusive registered BP nav items
	 *
	 * The following is a combination of logic found in {@see BP_Core_Nav::delete_nav()},
	 * where the nav items are collected, and {@see bp_core_remove_nav_item()} where the
	 * actual unhooking is done after the screen_functions are collected.
	 *
	 * The thing is, we'd still want to keep the nav items, because they are also used
	 * for the current user beyond the displayed user's profile navigation. See for example
	 * the collection of available logged-in pages in {@see bp_nav_menu_get_loggedin_pages()}.
	 *
	 * @since 0.1.0
	 */
	public function unhook_bp_nav_items() {

		// Get BuddyPress
		$bp = buddypress();

		// Define local variable(s)
		$items = array();

		// For now, only Members and Groups have a nav
		foreach ( array( 'members', 'groups' ) as $nav_component ) {

			// Component has no navigation
			if ( ! bp_is_active( $nav_component ) || ! isset( $bp->{$nav_component}->nav ) )
				continue;

			// Get the nav object
			$nav = $bp->{$nav_component}->nav;

			// Walk BP's components
			foreach ( array_keys( $bp->active_components ) as $component ) {

				// Skip non-exclusive components
				if ( ! empty( $component ) && ! vgsr_bp_is_vgsr_component( $component ) )
					continue;

				// Get the component's primary nav slug or skip
				if ( is_callable( "bp_get_{$component}_slug" ) ) {
					$slug = call_user_func( "bp_get_{$component}_slug" );
				} else {
					$slug = $component;
				}

				// Hide the nav item and get the nav item and subnav items
				$items[] = $nav->edit_nav( array( 'show_for_displayed_user' => false ), $slug );
				$items  += $nav->get_secondary( array( 'parent_slug' => $slug ), false );
			}
		}

		// Remove falsey findings
		$items = array_filter( $items );

		/**
		 * Unhook the nav item's screen functions
		 *
		 * Screen functions deliver the actual content of the pages. If they
		 * are not providing content, BP does a 404, which is what we want.
		 */
		foreach ( $items as $item ) {
			if ( isset( $item->screen_function ) && is_callable( $item->screen_function ) ) {
				remove_action( 'bp_screens', $item->screen_function, 3 );
			}
		}
	}

	/**
	 * Unhook theme-compat component hooks in bp-legacy
	 *
	 * @since 0.1.0
	 */
	public function unhook_theme_compat() {

		// What template pack is used?
		switch ( bp_get_theme_compat_id() ) {

			/**
			 * BP Legacy template pack
			 *
			 * @see BP_Legacy::setup_actions()
			 */
			case 'legacy':

				// Friends component
				if ( vgsr_bp_is_vgsr_component( 'friends' ) ) {
					remove_action( 'bp_member_header_actions', 'bp_add_friend_button', 5 );
				}

				// Activity component
				if ( vgsr_bp_is_vgsr_component( 'activity' ) ) {
					remove_action( 'bp_member_header_actions', 'bp_send_public_message_button', 20 );
				}

				// Messages component
				if ( vgsr_bp_is_vgsr_component( 'messages' ) ) {
					remove_action( 'bp_member_header_actions', 'bp_send_private_message_button', 20 );
				}

				// Groups component
				if ( vgsr_bp_is_vgsr_component( 'groups' ) ) {
					remove_action( 'bp_group_header_actions',          'bp_group_join_button',               5           );
					remove_action( 'bp_group_header_actions',          'bp_group_new_topic_button',         20           );
					remove_action( 'bp_directory_groups_actions',      'bp_group_join_button'                            );
					remove_action( 'bp_groups_directory_group_filter', 'bp_legacy_theme_group_create_nav', 999           );
					remove_action( 'bp_after_group_admin_content',     'bp_legacy_groups_admin_screen_hidden_input'      );
					remove_action( 'bp_before_group_admin_form',       'bp_legacy_theme_group_manage_members_add_search' );
				}

				// Blogs component
				if ( vgsr_bp_is_vgsr_component( 'blogs' ) ) {
					remove_action( 'bp_directory_blogs_actions',    'bp_blogs_visit_blog_button'           );
					remove_action( 'bp_blogs_directory_blog_types', 'bp_legacy_theme_blog_create_nav', 999 );
				}

			break;
		}
	}

	/**
	 * Define the default component for non-vgsr displayed users
	 *
	 * When active, the Activity component is set as the default component in
	 * {@see BP_Members_Component::setup_canonical_stack()}. For non-vgsr displayed
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
		// ... AND BP should be hidden for the displayed user
		if ( bp_is_active( 'activity' ) && vgsr_bp_is_vgsr_component( 'activity' ) && vgsr_bp_hide_for_user( bp_displayed_user_id() ) ) {
			$bp = buddypress();

			// Set the default component to XProfile
			if ( ! defined( 'BP_DEFAULT_COMPONENT' ) ) {
				define( 'BP_DEFAULT_COMPONENT', ( 'xprofile' === $bp->profile->id ) ? 'profile' : $bp->profile->id );
			}
		}
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
	 * Invalidate BP nav menu items for non-vgsr users
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $menu_item Nav menu item
	 * @return WP_Post Nav menu item
	 */
	public function setup_nav_menu_item( $menu_item ) {

		// Post type menu item, when user is non-vgsr
		if ( 'post_type' == $menu_item->type && ! is_user_vgsr() ) {
			$page_ids  = bp_core_get_directory_page_ids( 'all' );
			$component = array_search( $menu_item->object_id, $page_ids );

			// Invalidate all component's directory page menu items
			if ( $component ) {
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

		// Bail when the current user is vgsr or not logged-in
		if ( ! get_current_user_id() || is_user_vgsr() )
			return;

		// Settings component
		if ( bp_is_active( 'settings' ) ) {
			remove_all_actions( 'bp_notification_settings' ); // Eliminates need for Email (admin) nav, but may be too restrictive
			bp_core_remove_subnav_item( bp_get_settings_slug(), 'notifications' );
			bp_core_remove_subnav_item( bp_get_settings_slug(), 'profile'       ); // See BP_XProfile_Component::setup_settings_nav()
		}
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
		foreach ( vgsr_bp_get_member_types() as $type => $args ) {

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
	 * @return bool User has member type
	 */
	public function has_member_type( $member_type, $user_id = null ) {

		// Default to the current user
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		/*
		 * When this is called before `bp_register_taxonomies()` has fired,
		 * verify member-types by the cached list of member type users.
		 *
		 * NOTE: this ignores any existing member-type filters, like 'bp_get_member_type'.
		 */
		if ( ! did_action( 'bp_register_taxonomies' ) ) {
			return in_array( $user_id, $this->get_member_type_users( $member_type ) );
		}

		return bp_has_member_type( $user_id, $member_type );
	}

	/**
	 * Return the collection of users per member type
	 *
	 * @since 0.1.0
	 *
	 * @param string $member_type Member type name
	 * @return array User ids having the member type
	 */
	public function get_member_type_users( $member_type = '' ) {

		// Bail early when the member type is invalid
		if ( empty( $member_type ) ) {
			return array();
		}

		/**
		 * Cache users per member type
		 *
		 * NOTE: Using WP_Tax_Query returns no results here, because it checks
		 * whether the member-type taxonomy is registered, which it probably isn't.
		 */
		if ( ! isset( $this->member_type_users[ $member_type ] ) ) {
			global $wpdb;

			// Get the taxonomy name or guess like we're educated
			$tax = function_exists( 'bp_get_member_type_tax_name' ) ? bp_get_member_type_tax_name() : 'bp_member_type';

			// Switch to the root blog, where member type taxonomies live.
			$site_id  = bp_get_taxonomy_term_site_id( $tax );
			$switched = false;
			if ( $site_id !== get_current_blog_id() ) {
				switch_to_blog( $site_id );
				$switched = true;
			}

			// Setup SQL clause
			$sql = $wpdb->prepare( "SELECT tr.object_id FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = %s AND t.name = %s", $tax, $member_type );
			$result = $wpdb->get_col( $sql );

			if ( $switched ) {
				restore_current_blog();
			}

			// Sanitize user ids
			$user_ids = array_unique( array_map( 'intval', $result ) );

			$this->member_type_users[ $member_type ] = array_values( $user_ids );
		}

		return $this->member_type_users[ $member_type ];
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

	/**
	 * Modifty the metaboxes on the user's Extended Profile admin page
	 *
	 * @since 0.1.2
	 *
	 * @param bool $is_self_profile Whether or not it is the current user's profile.
	 * @param int $user_id Current user ID.
	 */
	public function admin_user_metaboxes( $is_self_profile, $user_id ) {

		// Do not allow simple users to edit their member type(s)
		if ( ! current_user_can( 'bp_moderate' ) ) {
			remove_meta_box( 'bp_members_admin_member_type', null, 'side' );
		}
	}

	/** Users **************************************************************/

	/**
	 * Filter whether the given user is VGSR.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return bool Is user VGSR?
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
	 * @return bool Is user lid?
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
	 * @return bool Is user oud-lid?
	 */
	public function is_user_oudlid( $is, $user_id = null ) {
		return ( $is ? $is : $this->has_member_type( vgsr_bp_oudlid_member_type(), $user_id ) );
	}

	/**
	 * Filter whether the given user is Ex-lid.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is User validation
	 * @param int $user_id User ID
	 * @return bool Is user Ex-lid?
	 */
	public function is_user_exlid( $is, $user_id = null ) {
		return ( $is ? $is : $this->has_member_type( vgsr_bp_exlid_member_type(), $user_id ) );
	}

	/**
	 * Modify the user query to return only vgsr users
	 *
	 * @since 0.1.0
	 *
	 * @global WPDB $wpdb
	 *
	 * @param array $sql_clauses SQL clauses to append
	 * @param WP_User_Query $query
	 * @return array SQL clauses
	 */
	public function pre_user_query( $sql_clauses, $query ) {
		global $wpdb;

		// Add query part for the 'vgsr' query parameter
		if ( $type = $query->get( 'vgsr' ) ) {
			$sql_clauses['where'] = vgsr_bp_query_vgsr_where_arg( $type, $wpdb->users );

			// Consider sorting for multi-type queries
			if ( in_array( $type, array( 'all', true ), true ) ) {

				// First leden, then others
				if ( 'ancienniteit-relevance' === $query->get( 'orderby' ) ) {
					$sql_clauses['orderby'] = vgsr_bp_query_vgsr_orderby_arg( $type, $wpdb->users );
				}
			}
		}

		return $sql_clauses;
	}

	/**
	 * Modify the BP user query to return only vgsr users
	 *
	 * Adds vgsr conditions to the uid query that prefetches all user ids that
	 * are later run through `WP_User_Query`. The other filter on `WP_User_Query`
	 * will run too late for this.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sql_clauses SQL clauses
	 * @param BP_User_Query $query
	 * @return array SQL clauses
	 */
	public function user_query_uid_clauses( $sql_clauses, $query ) {

		// Add query part for the 'vgsr' query parameter
		if ( ! empty( $query->query_vars['vgsr'] ) ) {
			$sql_clauses['where'][] = vgsr_bp_query_vgsr_where_arg( $query->query_vars['vgsr'], 'u' );
		}

		return $sql_clauses;
	}

	/** Pages & Templates **************************************************/

	/**
	 * Enqueue scripts and styles
	 *
	 * @since 0.2.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'vgsr-buddypress', $this->assets_url . 'js/vgsr-buddypress.js', array( 'jquery', 'bp-legacy-js' ) );
	}

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
		if ( isset( $args['id'] ) && 'public_message' === $args['id'] && vgsr_bp_block_activity_posting() ) {
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

/**
 * Setup the extension logic for BuddyPress
 *
 * @since 0.0.2
 *
 * @uses VGSR_BuddyPress
 */
function vgsr_setup_buddypress() {
	vgsr()->extend->bp = new VGSR_BuddyPress;
}

endif; // class_exists
