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
 * Version:           0.0.7
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

	/** Singleton *************************************************************/

	/**
	 * Main VGSR Instance
	 *
	 * Insures that only one instance of VGSR exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 0.0.1
	 *
	 * @uses VGSR::setup_globals() Setup the globals needed
	 * @uses VGSR::includes() Include the required files
	 * @uses VGSR::setup_actions() Setup the hooks and actions
	 * @see vgsr()
	 * @return The one true VGSR
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been ran previously
		if ( null === $instance ) {
			$instance = new VGSR;
			$instance->setup_globals();
			$instance->includes();
			$instance->setup_actions();
		}

		// Always return the instance
		return $instance;
	}

	/** Magic Methods *********************************************************/

	/**
	 * A dummy constructor to prevent VGSR from being loaded more than once.
	 *
	 * @since 0.0.1
	 *
	 * @see VGSR::instance()
	 * @see vgsr();
	 */
	private function __construct() { /* Do nothing here */ }

	/**
	 * A dummy magic method to prevent VGSR from being cloned
	 *
	 * @since 0.0.1
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'vgsr' ), '0.1' ); }

	/**
	 * A dummy magic method to prevent VGSR from being unserialized
	 *
	 * @since 0.0.1
	 */
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'vgsr' ), '0.1' ); }

	/**
	 * Magic method to prevent notices and errors from invalid method calls
	 *
	 * @since 0.0.1
	 */
	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }

	/** Private Methods *******************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 0.0.1
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version    = '0.0.7';
		$this->db_version = '007';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes'  );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes'  );

		// Assets
		$this->assets_dir   = trailingslashit( $this->includes_dir . 'assets'  );
		$this->assets_url   = trailingslashit( $this->includes_url . 'assets'  );

		// Extenders
		$this->extend_dir   = trailingslashit( $this->includes_dir . 'extend'  );
		$this->extend_url   = trailingslashit( $this->includes_url . 'extend'  );

		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );

		// Templates
		$this->themes_dir   = trailingslashit( $this->plugin_dir . 'templates' );
		$this->themes_url   = trailingslashit( $this->plugin_url . 'templates' );

		/** Misc **************************************************************/

		$this->domain       = 'vgsr';         // Unique identifier for retrieving translated strings
		$this->extend       = new stdClass(); // Plugins add data here
		$this->errors       = new WP_Error(); // Feedback
	}

	/**
	 * Include required files
	 *
	 * @since 0.0.1
	 */
	private function includes() {

		/** Core **************************************************************/

		require( $this->includes_dir . 'core/functions.php'     );
		require( $this->includes_dir . 'core/options.php'       );
		require( $this->includes_dir . 'core/sub-actions.php'   );
		require( $this->includes_dir . 'core/update.php'        );

		/** Components ********************************************************/

		// Posts
		require( $this->includes_dir . 'posts/functions.php'    );

		// Users
		require( $this->includes_dir . 'users/capabilities.php' );
		require( $this->includes_dir . 'users/functions.php'    );

		/** Hooks *************************************************************/

		require( $this->includes_dir . 'core/extend.php'  );
		require( $this->includes_dir . 'core/actions.php' );
		require( $this->includes_dir . 'core/filters.php' );

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

		// If VGSR is being deactivated, do not add any actions
		if ( vgsr_is_deactivation( $this->basename ) )
			return;

		// Array of VGSR core actions
		$actions = array(
			'load_textdomain', // Load textdomain (vgsr)
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
	 * @uses load_textdomain() To load the textdomain
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
