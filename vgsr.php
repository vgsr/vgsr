<?php

/**
 * The VGSR Plugin
 *
 * @package VGSR
 * @subpackage Main
 */

/**
 * Plugin Name:       VGSR
 * Description:       Main utility plugin for vgsr.nl
 * Plugin URI:        https://github.com/vgsr/vgsr
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins
 * Version:           0.1.2
 * Network:           true
 * Text Domain:       vgsr
 * Domain Path:       /languages/
 * GitHub Plugin URI: vgsr/vgsr
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR' ) ) :
/**
 * Main VGSR Class
 *
 * @since 0.0.1
 */
final class VGSR {

	/**
	 * Setup and return the singleton pattern
	 *
	 * Ensures that only one instance of VGSR exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 0.0.1
	 *
	 * @return The single VGSR
	 */
	public static function instance() {

		// Store the instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new VGSR;
			$instance->setup_globals();
			$instance->includes();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * A dummy constructor to prevent VGSR from being loaded more than once.
	 */
	private function __construct() { /* Do nothing here */ }

	/** Private Methods *******************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 0.0.1
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version    = '0.1.2';
		$this->db_version = 12;

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes' );

		// Assets
		$this->assets_dir   = trailingslashit( $this->plugin_dir . 'assets' );
		$this->assets_url   = trailingslashit( $this->plugin_url . 'assets' );

		// Extenders
		$this->extend_dir   = trailingslashit( $this->includes_dir . 'extend' );
		$this->extend_url   = trailingslashit( $this->includes_url . 'extend' );

		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );

		// Templates
		$this->themes_dir   = trailingslashit( $this->plugin_dir . 'templates' );
		$this->themes_url   = trailingslashit( $this->plugin_url . 'templates' );

		/** Misc **************************************************************/

		$this->extend       = new stdClass(); // Plugins add data here
		$this->errors       = new WP_Error(); // Feedback
		$this->domain       = 'vgsr';         // Unique identifier for retrieving translated strings
	}

	/**
	 * Include required files
	 *
	 * @since 0.0.1
	 */
	private function includes() {

		/** Core **************************************************************/

		require( $this->includes_dir . 'actions.php'      );
		require( $this->includes_dir . 'extend.php'       );
		require( $this->includes_dir . 'functions.php'    );
		require( $this->includes_dir . 'options.php'      );
		require( $this->includes_dir . 'posts.php'        );
		require( $this->includes_dir . 'sub-actions.php'  );
		require( $this->includes_dir . 'theme-compat.php' );
		require( $this->includes_dir . 'update.php'       );
		require( $this->includes_dir . 'users.php'        );

		/** Admin *************************************************************/

		if ( is_admin() ) {
			require( $this->includes_dir . 'admin/admin.php' );
		}
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 0.0.1
	 */
	private function setup_actions() {

		// Add actions to plugin activation and deactivation hooks
		add_action( 'activate_'   . $this->basename, 'vgsr_activation'   );
		add_action( 'deactivate_' . $this->basename, 'vgsr_deactivation' );

		// Bail when plugin is being deactivated
		if ( vgsr_is_deactivation() )
			return;

		// Array of VGSR core actions
		$actions = array(
			'load_textdomain', // Load textdomain (vgsr)
			'widgets_init',    // Register widgets
		);

		// Add the actions
		foreach ( $actions as $class_action ) {
			add_action( 'vgsr_' . $class_action, array( $this, $class_action ), 5 );
		}

		// All VGSR actions are setup (includes vgsr-core-hooks.php)
		do_action_ref_array( 'vgsr_after_setup_actions', array( &$this ) );
	}

	/** Public Methods ********************************************************/

	/**
	 * Load the translation file for current language. Checks the languages
	 * folder inside the VGSR plugin first, and then the default WordPress
	 * languages folder.
	 *
	 * Note that custom translation files inside the VGSR plugin folder
	 * will be removed on VGSR updates. If you're creating custom
	 * translation files, please use the global language folder.
	 *
	 * @since 0.0.1
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/vgsr/' . $mofile;

		// Look in global /wp-content/languages/vgsr folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/vgsr/languages/ folder
		load_textdomain( $this->domain, $mofile_local );

		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
	}

	/**
	 * Register widgets
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'vgsr_register_widgets'
	 */
	public function widgets_init() {

		// Collect widget data as 'classname' => 'path/to/file.php'
		$widgets = apply_filters( 'vgsr_register_widgets', array() );

		// Register widgets
		foreach ( $widgets as $class => $path ) {
			require_once( $path );
			register_widget( $class );
		}
	}
}

/**
 * The main function responsible for returning the one true VGSR Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $vgsr = vgsr(); ?>
 *
 * Use this function to check if this plugin is activated.
 *
 * Example: <?php if ( ! function_exists( 'vgsr' ) ) return; ?>
 *
 * @return The one true VGSR Instance
 */
function vgsr() {
	return VGSR::instance();
}

// "Dat kan alleen in Rotterdam!"
vgsr();

endif; // class_exists
