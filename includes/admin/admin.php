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

		/** Network Actions ***************************************************/

		add_action( 'network_admin_menu',           array( $this, 'admin_menus'             ) ); // Add menu item to settings menu
		add_action( 'network_admin_edit_vgsr',      array( $this, 'handle_network_settings' ) ); // Update network settings

		/** Filters ***********************************************************/

		// Plugin action links
		add_filter( 'plugin_action_links',               array( $this, 'plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

		// Map settings capabilities
		add_filter( 'vgsr_map_meta_caps',  array( $this, 'map_settings_meta_caps'     ), 10, 4 );

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
	 *
	 * @uses current_user_can()
	 * @uses is_plugin_active_for_network()
	 * @uses is_network_admin()
	 * @uses add_submenu_page()
	 * @uses add_options_page() To add the VGSR settings page
	 */
	public function admin_menus() {

		// Bail when settings are not enabled
		if ( ! current_user_can( 'vgsr_settings_page' ) )
			return;

		// Do not register admin page for single sites on the network
		if ( is_multisite() && doing_action( 'admin_menu' ) )
			return;

		// Register admin page
		$hook = add_submenu_page(
			is_multisite() ? 'settings.php' : 'options-general.php',
			_x( 'VGSR', 'settings page title', 'vgsr' ),
			_x( 'VGSR', 'settings menu title', 'vgsr' ),
			$this->minimum_capability,
			'vgsr',
			'vgsr_admin_page'
		);

		// Register admin page hooks
		add_action( "load-$hook",         array( $this, 'load_admin_page'   ) );
		add_action( "admin_head-$hook",   array( $this, 'admin_page_head'   ) );
		add_action( "admin_footer-$hook", array( $this, 'admin_page_footer' ) );
	}

	/**
	 * Register a dedicated hook on admin page load
	 *
	 * @since 0.1.0
	 *
	 * @uses do_action() Calls 'vgsr_load_admin_page'
	 */
	public function load_admin_page() {
		do_action( 'vgsr_load_admin_page' );
	}

	/**
	 * Register a dedicated hook in the admin page head
	 *
	 * @since 0.1.0
	 *
	 * @uses do_action() Calls 'vgsr_admin_page_head'
	 */
	public function admin_page_head() {
		do_action( 'vgsr_admin_page_head' );
	}

	/**
	 * Register a dedicated hook in the admin page footer
	 *
	 * @since 0.1.0
	 *
	 * @uses do_action() Calls 'vgsr_admin_page_footer'
	 */
	public function admin_page_footer() {
		do_action( 'vgsr_admin_page_footer' );
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
	 * Run our own Settings API for the network context
	 *
	 * This method follows the logic of the Settings API for single sites
	 * very closely as it is in {@link wp-admin/options.php}.
	 *
	 * @since 0.0.7
	 * 
	 * @link http://core.trac.wordpress.org/ticket/15691
	 *
	 * @uses wp_reset_vars()
	 * @uses is_multisite()
	 * @uses apply_filters() Calls 'option_page_capability_{$option_page}'
	 * @uses current_user_can()
	 * @uses is_super_admin()
	 * @uses wp_die()
	 * @uses check_admin_referer()
	 * @uses apply_filters() Calls 'whitelist_options'
	 * @uses update_site_option()
	 * @uses get_settings_errors()
	 * @uses add_settings_error()
	 * @uses set_transient()
	 * @uses add_query_arg()
	 * @uses wp_get_referer()
	 * @uses wp_redirect()
	 */
	public function handle_network_settings() {
		global $action, $option_page;

		// Redefine global variable(s)
		wp_reset_vars( array( 'action', 'option_page' ) );

		// Bail when not using within multisite
		if ( ! is_multisite() )
			return;

		/* This filter is documented in wp-admin/options.php */
		$capability = apply_filters( "option_page_capability_{$option_page}", 'manage_options' );

		// Bail when current user is not allowed
		if ( ! current_user_can( $capability ) || ( is_multisite() && ! is_super_admin() ) )
			wp_die( __( 'Cheatin&#8217; uh?' ), 403 );

		// We are saving settings sent from a settings page
		if ( 'update' == $action ) {

			// Check admin referer
			check_admin_referer( $option_page . '-options' );

			/* This filter is documented in wp-admin/options.php */
			$whitelist_options = apply_filters( 'whitelist_options', '' );

			// Bail when settings page is not registered
			if ( ! isset( $whitelist_options[ $option_page ] ) )
				wp_die( __( '<strong>ERROR</strong>: options page not found.' ) );

			$options = $whitelist_options[ $option_page ];

			if ( $options ) {
				foreach ( $options as $option ) {
					$option = trim( $option );
					$value = null;
					if ( isset( $_POST[ $option ] ) ) {
						$value = $_POST[ $option ];
						if ( ! is_array( $value ) )
							$value = trim( $value );
						$value = wp_unslash( $value );
					}
					update_site_option( $option, $value );
				}
			}

			/**
			 * Handle settings errors and return to options page
			 */
			// If no settings errors were registered add a general 'updated' message.
			if ( !count( get_settings_errors() ) )
				add_settings_error('general', 'settings_updated', __('Settings saved.'), 'updated');
			set_transient('settings_errors', get_settings_errors(), 30);

			/**
			 * Redirect back to the settings page that was submitted
			 */
			$goback = add_query_arg( 'settings-updated', 'true',  wp_get_referer() );
			wp_redirect( $goback );
			exit;
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

		// Append plugin links
		if ( $basename === vgsr()->basename ) {

			// What do you see, Mindy from the Network?
			$menu = is_multisite() ? 'settings.php' : 'options-general.php';

			// Settings link
			$links['settings'] = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'page', 'vgsr', self_admin_url( $menu ) ) ), esc_html__( 'Settings', 'vgsr' ) );
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
