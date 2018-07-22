<?php

/**
 * VGSR Extension for Responsive Lightbox
 *
 * @package VGSR
 * @subpackage Responsive Lightbox
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Responsive_Lightbox' ) ) :
/**
 * The VGSR Responsive Lightbox class
 *
 * @since 1.0.0
 */
class VGSR_Responsive_Lightbox {

	/**
	 * Setup this class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Bail if no Responsive Lightbox
		if ( ! class_exists( 'Responsive_Lightbox' ) )
			return;

		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Modify builder options
		add_filter( 'pre_option_responsive_lightbox_builder', array( $this, 'builder_options' ) );

		// Modify plugin logic
		add_action( 'init',       array( $this, 'after_load_settings' ), 20 );
		add_action( 'admin_head', array( $this, 'admin_head'          )     );
	}

	/** Public methods **************************************************/

	/**
	 * Modify the option for 'responsive_lightbox_builder'
	 *
	 * @since 1.0.0
	 *
	 * @return array Option data
	 */
	public function builder_options() {
		return array(
			// Force apply no-gallery mode. Removes gallery post type and taxonomy
			'gallery_builder' => false
		);
	}

	/**
	 * Modify the plugin's settings logic after it is loaded 
	 * 
	 * @see Responsive_Lightbox_Settings::load_defaults()
	 *
	 * @since 1.0.0
	 */
	public function after_load_settings() {
		$plugin = Responsive_Lightbox();

		// Remove gallery-related admin pages
		foreach ( array(
			'gallery',
			'builder',
			'addons'
		) as $key ) {
			if ( isset( $plugin->settings->tabs[ $key ] ) ) {
				unset( $plugin->settings->tabs[ $key ] );
			}
		}
	}

	/**
	 * Modify the admin layout
	 * 
	 * @see Responsive_Lightbox_Settings::admin_menu_options()
	 *
	 * @since 1.0.0
	 */
	public function admin_head() {
		global $parent_file, $submenu_file;

		// Remove admin menu items
		remove_menu_page( 'responsive-lightbox-settings' );
		remove_submenu_page( 'responsive-lightbox-settings', 'responsive-lightbox-configuration' );
		remove_submenu_page( 'responsive-lightbox-settings', 'responsive-lightbox-gallery'       );
		remove_submenu_page( 'responsive-lightbox-settings', 'responsive-lightbox-licenses'      );
		remove_submenu_page( 'responsive-lightbox-settings', 'responsive-lightbox-addons'        );

		// Move plugin settings below General Settings
		add_submenu_page( 'options-general.php', null, esc_html_x( 'Lightbox', 'Plugin admin menu name', 'vgsr' ), 'manage_options', 'responsive-lightbox-settings' );

		/**
		 * Tweak the plugin's subnav menus to show the right top menu and submenu item.
		 */

		// Point all admin pages to the new admin menu item
		if ( 'responsive-lightbox-settings' === $parent_file ) {
			$parent_file  = 'options-general.php';
			$submenu_file = 'responsive-lightbox-settings';
		}
	}
}

/**
 * Setup the extension logic for Responsive Lightbox
 *
 * @since 1.0.0
 *
 * @uses VGSR_Responsive_Lightbox
 */
function vgsr_setup_responsive_lightbox() {
	vgsr()->extend->responsive_lightbox = new VGSR_Responsive_Lightbox;
}

endif; // class_exists
