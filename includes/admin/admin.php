<?php

/**
 * Main VGSR Admin Class
 *
 * @package VGSR
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'VGSR_Admin' ) ) :
/**
 * Loads the VGSR plugin admin area
 *
 * @since 0.0.1
 */
class VGSR_Admin {

	/** Directory *************************************************************/

	/**
	 * @var string Path to the VGSR admin directory
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * @var string URL to the VGSR admin directory
	 */
	public $admin_url = '';

	/** Capability ************************************************************/

	/**
	 * @var bool Minimum capability to access Settings
	 */
	public $minimum_capability = 'manage_options';

	/** Functions *************************************************************/

	/**
	 * The main VGSR admin loader
	 *
	 * @since 0.0.1
	 *
	 * @uses VGSR_Admin::setup_globals() Setup the globals needed
	 * @uses VGSR_Admin::includes() Include the required files
	 * @uses VGSR_Admin::setup_actions() Setup the hooks and actions
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
	 * @access private
	 */
	private function setup_globals() {
		$vgsr = vgsr();

		/** Paths *************************************************************/

		$this->admin_dir = trailingslashit( $vgsr->includes_dir . 'admin'  ); // Admin path
		$this->admin_url = trailingslashit( $vgsr->includes_url . 'admin'  ); // Admin url
	}

	/**
	 * Include required files
	 *
	 * @since 0.0.1
	 * @access private
	 */
	private function includes() {
		require( $this->admin_dir . 'functions.php' );
		require( $this->admin_dir . 'settings.php'  );
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @since 0.0.1
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 */
	private function setup_actions() {

		// Bail to prevent interfering with the deactivation process
		if ( vgsr_is_deactivation() )
			return;

		/** General Actions ***************************************************/

		add_action( 'vgsr_admin_menu',              array( $this, 'admin_menus'             ) ); // Add menu item to settings menu
		add_action( 'vgsr_register_admin_settings', array( $this, 'register_admin_settings' ) ); // Add settings

		/** Filters ***********************************************************/

		// Modify VGSR's admin links
		add_filter( 'plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

		// Map settings capabilities
		add_filter( 'vgsr_map_meta_caps',  array( $this, 'map_settings_meta_caps'     ), 10, 4 );

		// VGSR-only columns
		add_filter( 'display_post_states',        array( $this, 'display_post_states'  ), 10, 2 );
		add_filter( 'manage_posts_columns',       array( $this, 'get_post_columns'     )        );
		add_filter( 'manage_pages_columns',       array( $this, 'get_post_columns'     )        );
		add_filter( 'manage_posts_custom_column', array( $this, 'post_columns_content' ), 10, 2 );
		add_filter( 'manage_pages_custom_column', array( $this, 'post_columns_content' ), 10, 2 );

		/** Network Admin *****************************************************/

		// Add menu item to settings menu
		add_action( 'network_admin_menu',  array( $this, 'network_admin_menus' ) );

		/** Dependencies ******************************************************/

		// Allow plugins to modify these actions
		do_action_ref_array( 'vgsr_admin_loaded', array( &$this ) );
	}

	/**
	 * Add the admin menus
	 *
	 * @since 0.0.1
	 *
	 * @uses add_options_page() To add the VGSR settings page
	 */
	public function admin_menus() {

		// Are settings enabled?
		if ( current_user_can( 'vgsr_settings_page' ) ) {
			add_options_page(
				__( 'VGSR',  'vgsr' ),
				__( 'VGSR',  'vgsr' ),
				$this->minimum_capability,
				'vgsr',
				'vgsr_admin_settings'
			);
		}
	}

	/**
	 * Add the network admin menus
	 *
	 * @since 0.0.1
	 * @uses add_options_page() To add the VGSR page
	 */
	public function network_admin_menus() {

		// Bail if plugin is not network activated
		if ( ! is_plugin_active_for_network( vgsr()->basename ) )
			return;

		add_options_page(
			__( 'VGSR Network', 'vgsr' ),
			__( 'VGSR Network', 'vgsr' ),
			'manage_network',
			'vgsr',
			'vgsr_admin_settings'
		);
	}

	/**
	 * Maps settings capabilities
	 *
	 * @param array $caps Capabilities for meta capability
	 * @param string $cap Capability name
	 * @param int $user_id User id
	 * @param mixed $args Arguments
	 * @uses apply_filters() Calls 'vgsr_map_settings_meta_caps' with caps, cap, user id and
	 *                        args
	 * @return array Actual capabilities for meta capability
	 */
	public static function map_settings_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

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
	 *
	 * @uses add_settings_section() To add our own settings section
	 * @uses add_settings_field() To add various settings fields
	 * @uses register_setting() To register various settings
	 * @todo Put fields into multidimensional array
	 */
	public static function register_admin_settings() {

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

			// Add the section
			add_settings_section( $section_id, $section['title'], $section['callback'], $section['page'] );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {

				// Add the field
				if ( ! empty( $field['callback'] ) && !empty( $field['title'] ) ) {
					add_settings_field( $field_id, $field['title'], $field['callback'], $section['page'], $section_id, $field['args'] );
				}

				// Register the setting
				register_setting( $section['page'], $field_id, $field['sanitize_callback'] );
			}
		}
	}

	/**
	 * Add Settings link to plugins area
	 *
	 * @since 0.0.1
	 *
	 * @param array $links Links array in which we would prepend our link
	 * @param string $file Current plugin basename
	 * @return array Processed links
	 */
	public static function modify_plugin_action_links( $links, $file ) {

		// Return normal links if not VGSR
		if ( plugin_basename( vgsr()->basename ) !== $file )
			return $links;

		// Add a few links to the existing links array
		return array_merge( $links, array(
			'settings' => '<a href="' . add_query_arg( array( 'page' => 'vgsr' ), admin_url( 'options-general.php' ) ) . '">' . esc_html__( 'Settings', 'vgsr' ) . '</a>',
		) );
	}

	/** Posts *****************************************************************/

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

		// Only if this post type applies
		if ( isset( $screen->post_type ) && is_vgsr_only_post_type( $screen->post_type ) ) {

			// Dummy column to enable quick edit
			$columns['vgsr-only'] = __( 'VGSR Only', 'vgsr' );

			// Hide dummy column by default
			add_filter( "get_user_option_manage{$screen->id}columnshidden", array( $this, 'get_post_columns_hidden' ) );
		}

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

		// Hide vgsr-only dummy column by default
		$columns[] = 'vgsr-only';

		return $columns;
	}

	/**
	 * Display dedicated column content
	 *
	 * @since 0.0.6
	 *
	 * @uses vgsr_is_post_vgsr_only()
	 */
	public function post_columns_content( $column, $post_id ) {

		// Check column name
		switch ( $column ) {
			case 'vgsr-only' :

				// Output quick edit value reference
				// @todo Display check mark. It's existence will do for quick edit data finding.
				echo '<input type="hidden" value="' . (int) vgsr_is_post_vgsr_only( $post_id ) . '" />';
				break;
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

		// Post is vgsr-only: big notation.
		if ( vgsr_is_post_vgsr_only( $post->ID ) ) {
			$states['vgsr-only'] = __( 'VGSR', 'vgsr' );

		// Some parent is vgsr-only: small notation.
		} elseif ( vgsr_is_post_vgsr_only( $post->ID, true ) ) {
			$states['vgsr-only'] = __( 'vgsr', 'vgsr' );
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
