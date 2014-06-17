<?php

/**
 * Main VGSR Gravity Forms Class
 *
 * @package VGSR
 * @subpackage Plugins
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'VGSR_GravityForms' ) ) :
/**
 * Loads Gravity Forms Extension
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
		$vgsr = vgsr();

		/** Paths **********************************************************/

		$this->includes_dir = trailingslashit( $vgsr->includes_dir . 'extend/gravityforms' );
		$this->includes_url = trailingslashit( $vgsr->includes_url . 'extend/gravityforms' );
	}

	/**
	 * Include the required files
	 *
	 * @since 0.0.6
	 */
	private function includes() {
		// require( $this->includes_dir . 'settings.php' );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 0.0.6
	 */
	private function setup_actions() {

		// Settings
		// add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_gf_settings_sections' );
		// add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_gf_settings_fields'   );
		// add_filter( 'vgsr_map_settings_meta_caps', array( $this, 'map_meta_caps' ), 10, 4 );

		// VGSR-only
		add_filter( 'gform_form_settings',          array( $this, 'display_form_settings' ), 10, 2 );
		add_filter( 'gform_pre_form_settings_save', array( $this, 'save_form_settings'    )        );

	}

	/** Capabilities *******************************************************/

	/**
	 * Map VGSR Gravity Forms settings capabilities
	 *
	 * @since 0.0.6
	 * 
	 * @param  array   $caps    Required capabilities
	 * @param  string  $cap     Requested capability
	 * @param  integer $user_id User ID
	 * @param  array   $args    Additional arguments
	 * @return array Required capabilities
	 */
	public function map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

		switch ( $cap ) {

			case 'vgsr_settings_gf_general' :
				$caps = array( vgsr()->admin->minimum_capability );
				break;
		}

		return $caps;
	}

	/** Groups *************************************************************/

	/**
	 * Manipulate the form settings sections
	 *
	 * @since 0.0.6
	 *
	 * @param array $settings Form settings sections
	 * @param object $form Form object
	 */
	public function display_form_settings( $settings, $form ) {

		// Walk settings
		foreach ( $settings as $title => $fields ) {

			// Check settings section. Translatable section titles are used as key (!)
			switch ( $title ) {

				// Restrictions
				case __( 'Restrictions', 'gravityforms' ) :

					// Build settings field
					ob_start(); ?>

					<tr>
						<th><?php _e( 'VGSR-only', 'vgsr' ); ?></th>
						<td>
							<input type="checkbox" id="vgsr_form_vgsr_only" name="vgsr_form_vgsr_only" value="1" <?php checked( $this->is_form_vgsr_only( $form ) ); ?> />
							<label for="vgsr_form_vgsr_only"><?php _e( 'Mark this form as VGSR-only', 'vgsr' ); ?></label>
						</td>
					</tr>

					<?php

					// Append field
					$settings[ $title ]['vgsr-only'] = ob_get_contents();

					// End object buffer
					ob_end_clean();
					break;
			}
		}

		return $settings;
	}

	/**
	 * Return the sanitized form settings to save
	 *
	 * @since 0.0.6
	 * 
	 * @param array $settings Settings to be updated
	 * @return array Settings
	 */
	public function save_form_settings( $settings ) {
		
		// Append vgsr-only setting
		$settings['vgsr-only'] = isset( $_POST['vgsr_form_vgsr_only'] ) ? 1 : 0;

		return $settings;
	}

	/**
	 * Return whether the given form is marked vgsr-only
	 *
	 * @since 0.0.6
	 * 
	 * @param int|array $form Form ID or form meta array
	 * @return bool Form is marked vgsr-only
	 */
	public function is_form_vgsr_only( $form_id = 0 ) {

		// Get form metadata
		if ( is_array( $form_id ) ) {
			$form    = $form_id;
			$form_id = (int) rgget('id');
		} else {
			$form = RGFormsModel::get_form_meta( (int) $form_id );
		}

		$is = isset( $form['vgsr-only'] ) && $form['vgsr-only'];

		return (bool) apply_filters( 'vgsr_gf_is_form_vgsr_only', $is, $form_id, $form );
	}
}

endif; // class_exists
