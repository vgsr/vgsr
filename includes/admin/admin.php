<?php

/**
 * Main VGSR Admin Class
 *
 * @package VGSR
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Admin' ) ) :
/**
 * Loads the VGSR plugin admin area
 *
 * @since 0.0.1
 */
class VGSR_Admin {

	/**
	 * The main VGSR admin loader
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Admin globals
	 *
	 * @since 0.0.1
	 */
	private function setup_globals() {
		$vgsr = vgsr();

		/** Paths *************************************************************/

		$this->admin_dir = trailingslashit( $vgsr->includes_dir . 'admin'  ); // Admin path
		$this->admin_url = trailingslashit( $vgsr->includes_url . 'admin'  ); // Admin url

		/** Details ***********************************************************/

		$this->parent_page        = is_multisite() ? 'settings.php' : 'options-general.php';
		$this->minimum_capability = is_multisite() ? 'manage_network_options' : 'manage_options';
		$this->plugin_screen_id   = 'settings_page_vgsr';

		if ( is_network_admin() ) {
			$this->plugin_screen_id .= '-network';
		}
	}

	/**
	 * Include required files
	 *
	 * @since 0.0.1
	 */
	private function includes() {

		// Core
		require( $this->admin_dir . 'functions.php'   );
		require( $this->admin_dir . 'settings.php'    );

		// Actions
		require( $this->admin_dir . 'actions.php'     );
		require( $this->admin_dir . 'sub-actions.php' );
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @since 0.0.1
	 */
	private function setup_actions() {

		// Bail to prevent interfering with the deactivation process
		if ( vgsr_is_deactivation() )
			return;

		/** General Actions ***************************************************/

		add_action( 'vgsr_admin_menu',              array( $this, 'admin_menu'        ) ); // Add menu item to settings menu
		add_action( 'vgsr_register_admin_settings', array( $this, 'register_settings' ) ); // Add settings

		/** Filters ***********************************************************/

		// Plugin action links
		add_filter( 'plugin_action_links',               array( $this, 'plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

		// Map settings capabilities
		add_filter( 'vgsr_map_meta_caps', array( $this, 'map_settings_meta_caps' ), 10, 4 );

		// Post exclusivity
		add_action( 'vgsr_admin_init',     array( $this, 'setup_vgsr_post_columns' )        );
		add_filter( 'display_post_states', array( $this, 'display_post_states'     ), 10, 2 );

		/** Dependencies ******************************************************/

		// Allow plugins to modify these actions
		do_action_ref_array( 'vgsr_admin_loaded', array( &$this ) );
	}

	/**
	 * Add the admin menus
	 *
	 * @since 0.0.1
	 */
	public function admin_menu() {

		// Bail when settings are not enabled
		if ( ! current_user_can( 'vgsr_settings_page' ) )
			return;

		// Register admin page
		$hook = add_submenu_page(
			$this->parent_page,
			_x( 'VGSR Settings', 'settings page title', 'vgsr' ),
			_x( 'VGSR',          'settings menu title', 'vgsr' ),
			$this->minimum_capability,
			'vgsr',
			'vgsr_admin_page'
		);

		// Register admin page hooks
		add_action( "load-$hook",         'vgsr_load_admin_page'   );
		add_action( "admin_head-$hook",   'vgsr_admin_page_head'   );
		add_action( "admin_footer-$hook", 'vgsr_admin_page_footer' );
	}

	/**
	 * Maps settings capabilities
	 *
	 * @since 0.0.1
	 *
	 * @uses apply_filters() Calls 'vgsr_map_settings_meta_caps'
	 *
	 * @param array $caps Capabilities for meta capability
	 * @param string $cap Capability name
	 * @param int $user_id User id
	 * @param mixed $args Arguments
	 * @return array Actual capabilities for meta capability
	 */
	public function map_settings_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

		// What capability is being checked?
		switch ( $cap ) {

			// Admins
			case 'vgsr_settings_page' :   // Settings - Page
			case 'vgsr_settings_main' :   // Settings - General
			case 'vgsr_settings_access' : // Settings - Access
				$caps = array( vgsr()->admin->minimum_capability );
				break;
		}

		return apply_filters( 'vgsr_map_settings_meta_caps', $caps, $cap, $user_id, $args );
	}

	/**
	 * Register the settings
	 *
	 * @since 0.0.1
	 */
	public function register_settings() {

		// Bail if no sections available
		$sections = vgsr_admin_get_settings_sections();
		if ( empty( $sections ) )
			return false;

		// Loop through sections
		foreach ( (array) $sections as $section_id => $section ) {

			// Only proceed if current user can see this section
			if ( ! current_user_can( $section_id ) )
				continue;

			// Only add section and fields if section has fields
			$fields = vgsr_admin_get_settings_fields_for_section( $section_id );
			if ( empty( $fields ) )
				continue;

			// Define section page
			if ( ! empty( $section['page'] ) ) {
				$page = $section['page'];
			} else {
				$page = 'vgsr';
			}

			// Add the section
			add_settings_section( $section_id, $section['title'], $section['callback'], $page );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {

				// Add the field
				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					add_settings_field( $field_id, $field['title'], $field['callback'], $page, $section_id, $field['args'] );
				}

				// Register the setting
				if ( ! empty( $field['sanitize_callback'] ) ) {
					register_setting( $page, $field_id, $field['sanitize_callback'] );
				}
			}
		}
	}

	/**
	 * Modify the plugin action links
	 *
	 * @since 0.0.1
	 *
	 * @param array $links Plugin action links
	 * @param string $basename The plugin basename
	 * @return array Plugin action links
	 */
	public function plugin_action_links( $links, $basename ) {

		// Append plugin links, when user can manage
		if ( $basename === vgsr()->basename && current_user_can( $this->minimum_capability ) ) {

			// Settings link
			$links['settings'] = sprintf( '<a href="%s">%s</a>', esc_url( vgsr_admin_url() ), esc_html__( 'Settings', 'vgsr' ) );
		}

		return $links;
	}

	/** Posts *****************************************************************/

	/**
	 * Setup post administration column actions
	 *
	 * @since 0.1.0
	 */
	public function setup_vgsr_post_columns() {

		// Walk the post types
		foreach ( vgsr_post_types() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns",       array( $this, 'get_post_columns'     )        );
			add_filter( "manage_{$post_type}_posts_custom_column", array( $this, 'post_columns_content' ), 10, 2 );
		}
	}

	/**
	 * Filter the post administration columns
	 *
	 * @since 0.0.6
	 *
	 * @param array $columns Columns
	 * @return array
	 */
	public function get_post_columns( $columns ) {

		// Use screen object
		$screen = get_current_screen();

		// Dummy column to enable quick edit
		$columns['vgsr'] = _x( 'VGSR', 'exclusivity title', 'vgsr' );

		// Hide dummy column by default
		add_filter( "get_user_option_manage{$screen->id}columnshidden", array( $this, 'get_post_columns_hidden' ) );

		return $columns;
	}

	/**
	 * Filter the hidden post administration columns
	 *
	 * @since 0.0.6
	 *
	 * @param array $columns Hidden columns
	 * @return array
	 */
	public function get_post_columns_hidden( $columns ) {

		// Hide vgsr dummy column by default
		$columns[] = 'vgsr';

		return $columns;
	}

	/**
	 * Display dedicated column content
	 *
	 * @since 0.0.6
	 *
	 * @param string $column Column name
	 * @param int $post_id Post ID
	 */
	public function post_columns_content( $column, $post_id ) {

		// Display whether the post is exclusive (for Quick Edit)
		if ( 'vgsr' === $column && vgsr_is_post_vgsr( $post_id ) ) {
			echo '<i class="dashicons-before dashicons-yes"></i>';
		}
	}

	/**
	 * Manipulate post states
	 *
	 * @since 0.0.6
	 *
	 * @param array $states Post states
	 * @param WP_Post $post Post object
	 * @return array $states
	 */
	public function display_post_states( $states, $post ) {

		// Post is exclusive: big notation.
		if ( vgsr_is_post_vgsr( $post->ID ) ) {
			$states['vgsr'] = _x( 'VGSR', 'exclusivity label', 'vgsr' );

		// Some parent is exclusive: small notation.
		} elseif ( vgsr_is_post_vgsr( $post->ID, true ) ) {
			$states['vgsr'] = _x( 'vgsr', 'exclusivity label', 'vgsr' );
		}

		return $states;
	}
}

endif; // class_exists

/**
 * Load the VGSR Admin
 *
 * @since 0.0.1
 *
 * @uses VGSR_Admin
 */
function vgsr_admin() {
	vgsr()->admin = new VGSR_Admin;
}
