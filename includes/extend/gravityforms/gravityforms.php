<?php

/**
 * VGSR Extension for Gravity Forms
 *
 * @package VGSR
 * @subpackage Gravity Forms
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_GravityForms' ) ) :
/**
 * The VGSR Gravity Forms class
 *
 * @since 0.0.6
 */
class VGSR_GravityForms {

	/** Setup Methods ******************************************************/

	/**
	 * The main VGSR Gravity Forms loader
	 *
	 * @since 0.0.6
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Define default class globals
	 *
	 * @since 0.0.6
	 */
	private function setup_globals() {

		/** Paths **********************************************************/

		$this->includes_dir = trailingslashit( vgsr()->extend_dir . 'gravityforms' );
		$this->includes_url = trailingslashit( vgsr()->extend_url . 'gravityforms' );
	}

	/**
	 * Include the required files
	 *
	 * @since 0.0.6
	 */
	private function includes() {

		/** Core ***********************************************************/

		require( $this->includes_dir . 'actions.php'   );
		require( $this->includes_dir . 'functions.php' );

		/** Admin **********************************************************/

		if ( is_admin() ) {
			require( $this->includes_dir . 'admin.php'           );
			require( $this->includes_dir . 'admin-functions.php' );
		}
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 0.0.6
	 */
	private function setup_actions() {

		// Core
		add_filter( 'vgsr_map_meta_caps', array( $this, 'map_meta_caps' ), 10, 4 );

		// Forms & Fields
		add_filter( 'gform_get_form_filter', array( $this, 'handle_form_display'  ), 99, 2 );
		add_filter( 'gform_field_content',   array( $this, 'handle_field_display' ), 10, 5 );
		add_filter( 'gform_field_css_class', array( $this, 'add_field_class'      ), 10, 3 );

		// Widgets
		add_filter( 'widget_display_callback', array( $this, 'handle_widget_display' ), 5, 3 );

		// GF-Pages
		add_filter( 'gf_pages_hide_form', array( $this, 'gf_pages_hide_form_vgsr' ), 10, 2 );

		// Admin
		if ( is_admin() ) {
			add_action( 'vgsr_init', 'vgsr_gf_admin' );
		}
	}

	/** Capabilities *******************************************************/

	/**
	 * Map plugin capabilities
	 *
	 * @since 0.3.0
	 *
	 * @param  array   $caps    Required capabilities
	 * @param  string  $cap     Requested capability
	 * @param  integer $user_id User ID
	 * @param  array   $args    Additional arguments
	 * @return array Required capabilities
	 */
	public function map_meta_caps( $caps, $cap, $user_id, $args ) {

		switch ( $cap ) {

			// Export form entries
			case 'vgsr_gf_export_entries' :

				// Allow super admins
				if ( is_super_admin( $user_id ) ) {
					$caps = array( 'manage_options' );

				// A particular form
				} elseif ( isset( $args[0] ) ) {
					if ( vgsr_gf_can_user_export_form( $args[0], $user_id ) ) {
						$caps = array( 'read' );
					} else {
						$caps = array( 'do_not_allow' );
					}

				// No form specified
				} elseif ( vgsr_gf_can_user_export( $user_id ) ) {
					$caps = array( 'read' );

				// Prevent otherwise
				} else {
					$caps = array( 'do_not_allow' );
				}

				break;

			// GF export form entries
			case 'gravityforms_export_entries' :
				$export_page = is_admin() && isset( $_GET['page'] ) && 'vgsr_gf_export' === $_GET['page'];

				// Allow exporters on plugin's export page or when ajaxing
				if ( user_can( $user_id, 'vgsr_gf_export_entries' ) && ( $export_page || wp_doing_ajax() ) ) {
					$caps = array( 'read' );
				}

				break;
		}

		return $caps;
	}

	/** Public Methods *****************************************************/

	/**
	 * Do not display exclusive forms to non-vgsr users
	 *
	 * @since 0.0.7
	 * 
	 * @param string $content The form HTML content
	 * @param array $form Form meta data
	 * @return string Form HTML
	 */
	public function handle_form_display( $content, $form ) {

		// Return empty content when user is not VGSR
		if ( ! empty( $form ) && vgsr_gf_is_form_vgsr( $form ) && ! is_user_vgsr() ) {
			$content = '';
		}

		return $content;
	}

	/**
	 * Do not display exclusive fields to non-vgsr users
	 *
	 * @since 0.0.7
	 *
	 * @param string $content The field HTML content
	 * @param array $field Field meta data
	 * @param mixed $value The field's value
	 * @param int $empty 0
	 * @param int $form_id The field's form ID
	 * @return string Field HTML
	 */
	public function handle_field_display( $content, $field, $value, $empty, $form_id ) {

		// Bail when form is already exclusive
		if ( vgsr_gf_is_form_vgsr( $form_id ) )
			return $content;

		// On the front end, return empty content when user is not VGSR
		if ( ! is_admin() && ! empty( $field ) && vgsr_gf_is_field_vgsr( $field, $form_id ) && ! is_user_vgsr() ) {
			$content = '';
		}

		return $content;
	}

	/**
	 * Modify the form field classes
	 *
	 * @since 0.0.7
	 *
	 * @param string $classes Classes
	 * @param array $field Field object
	 * @param array $form Form object
	 * @return string Classes
	 */
	public function add_field_class( $classes, $field, $form ) {

		// Field is exclusive, not the form
		if ( ! vgsr_gf_is_form_vgsr( $form, false ) && vgsr_gf_is_field_vgsr( $field, $form ) ) {
			$classes .= ' vgsr-only';
		}

		return $classes;
	}

	/** Widgets ************************************************************/

	/**
	 * Do not display exclusive form widgets to non-vgsr users
	 *
	 * @since 0.1.0
	 *
	 * @param array     $instance The current widget instance's settings.
	 * @param WP_Widget $widget   The current widget instance.
	 * @param array     $args     An array of default widget arguments.
	 * @return array|bool The widget instance or False when not to display.
	 */
	public function handle_widget_display( $instance, $widget, $args ) {

		// When this is a GF widget
		if ( is_a( $widget, 'GFWidget' ) && isset( $instance['form_id'] ) ) {

			// The form is exclusive and the user is not VGSR
			if ( vgsr_gf_is_form_vgsr( $instance['form_id'] ) && ! is_user_vgsr() ) {
				$instance = false;
			}
		}

		return $instance;
	}

	/** GF-Pages ***********************************************************/

	/**
	 * Hide single form for GF-Pages plugin
	 *
	 * @since 0.0.6
	 * 
	 * @param bool $hide Whether to hide the form
	 * @param object $form Form data
	 * @return bool Whether to hide the form
	 */
	public function gf_pages_hide_form_vgsr( $hide, $form ) {

		// Set form to hide when the current user is not VGSR
		if ( vgsr_gf_is_form_vgsr( $form->id ) && ! is_user_vgsr() ) {
			$hide = true;
		}

		return $hide;
	}
}

/**
 * Setup the extension logic for Gravity Forms
 *
 * @since 0.0.6
 *
 * @uses VGSR_GravityForms
 */
function vgsr_setup_gravityforms() {
	vgsr()->extend->gravityforms = new VGSR_GravityForms;
}

endif; // class_exists
