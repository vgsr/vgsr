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
		add_filter( 'gform_pre_render',             array( $this, 'hide_form_vgsr_only'   ), 10, 2 );

		// Admin
		add_filter( 'gform_form_actions', array( $this, 'admin_form_actions' ), 10, 2 );

		// GF-Pages
		add_filter( 'gf_pages_hide_single_form', array( $this, 'gf_pages_hide_form_vgsr_only' ), 10, 2 );
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

	/**
	 * Prevent vgsr-only forms to display for non-vgsr users
	 *
	 * Still results in an 'Oops! We could not locate your form.'
	 * message, but it's the only way to hide before form process.
	 *
	 * @since 0.0.6
	 * 
	 * @param array $form Form meta data
	 * @param bool $ajax Whether the form is AJAX based
	 * @return null|array Form meta data
	 */
	public function hide_form_vgsr_only( $form, $ajax ) {

		// Bail if user _is_ VGSR
		if ( user_is_vgsr() )
			return $form;

		// Set form to null to block display
		if ( $this->is_form_vgsr_only( $form ) )
			$form = null;

		return $form;
	}

	/** Admin **************************************************************/

	/**
	 * Prepend to form actions whether the form is marked vgsr-only
	 *
	 * Title filters or appending actions are not available.
	 *
	 * @since 0.0.6
	 * 
	 * @param array $actions Form actions
	 * @param int $form_id Form ID
	 * @return array Form actions
	 */
	public function admin_form_actions( $actions, $form_id ) {

		// Form is marked vgsr-only
		if ( $this->is_form_vgsr_only( $form_id ) ) {

			// Prepend non-action
			$actions['vgsr-only'] = array(
				'label'    => __( 'VGSR', 'vgsr' ),
				'title'    => __( 'This form is marked VGSR-only', 'vgsr' ),
				'url'      => '#',
				'priority' => 1100,
			);
		}

		return $actions;
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
	public function gf_pages_hide_form_vgsr_only( $hide, $form ) {

		// Bail if user _is_ VGSR
		if ( user_is_vgsr() )
			return $hide;

		// Set form to null to block display
		if ( $this->is_form_vgsr_only( $form->id ) )
			$hide = true;

		return $hide;		
	}
}

endif; // class_exists
