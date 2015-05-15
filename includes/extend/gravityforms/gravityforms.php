<?php

/**
 * Main VGSR Gravity Forms Class
 *
 * @package VGSR
 * @subpackage Plugins
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_GravityForms' ) ) :
/**
 * Loads Gravity Forms Extension
 *
 * @since 0.0.6
 */
class VGSR_GravityForms {

	/**
	 * The plugin setting's meta key
	 *
	 * @since 0.0.7
	 * @var string
	 */
	private $meta_key = 'vgsrOnly';

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

		$this->includes_dir = trailingslashit( $vgsr->extend_dir . 'gravityforms' );
		$this->includes_url = trailingslashit( $vgsr->extend_url . 'gravityforms' );
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

		// Forms
		add_filter( 'gform_get_form_filter',        array( $this, 'handle_form_display'   ), 99, 2 );
		add_filter( 'gform_form_settings',          array( $this, 'register_form_setting' ), 10, 2 );
		add_filter( 'gform_pre_form_settings_save', array( $this, 'update_form_settings'  )        );
		add_filter( 'gform_form_actions',           array( $this, 'admin_form_actions'    ), 10, 2 );
		add_filter( 'admin_head',                   array( $this, 'admin_print_scripts'   )        );

		// Fields
		add_filter( 'gform_field_content',           array( $this, 'handle_field_display'   ), 10, 5 );
		add_action( 'gform_field_advanced_settings', array( $this, 'register_field_setting' ), 10, 2 );
		add_filter( 'gform_field_css_class',         array( $this, 'add_field_class'        ), 10, 3 );

		// Tooltips
		add_filter( 'gform_tooltips', array( $this, 'tooltips' ) );

		// Export
		add_filter( 'gform_export_separator', array( $this, 'export_separator' ) );

		// GF-Pages
		add_filter( 'gf_pages_hide_single_form', array( $this, 'gf_pages_hide_form_vgsr_only' ), 10, 2 );
	}

	/** Capabilities *******************************************************/

	/**
	 * Map plugin settings capabilities
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

	/** Public Methods *****************************************************/

	/**
	 * Return the given form's meta value
	 *
	 * @since 0.0.7
	 *
	 * @uses GFFormsModel::get_form_meta()
	 * 
	 * @param array|int $form Form object or form ID
	 * @param string $meta_key Form meta key
	 * @return mixed Form setting's value or NULL when not found
	 */
	public function get_form_meta( $form, $meta_key ) {

		// Get form metadata
		if ( ! is_array( $form ) && is_numeric( $form ) ) {
			$form = GFFormsModel::get_form_meta( (int) $form );
		} elseif ( ! is_array( $form ) ) {
			return null;
		}

		// Get form setting
		return isset( $form[ $meta_key ] ) ? $form[ $meta_key ] : null;
	}

	/**
	 * Return the given field's meta value
	 *
	 * @since 0.0.7
	 *
	 * @uses GFFormsModel::get_form_meta()
	 * @uses GFFormsModel::get_field()
	 * 
	 * @param array|int $field Field object or field ID
	 * @param string $meta_key Field meta key
	 * @param array|int $form Form object or form ID
	 * @return mixed Field setting's value or NULL when not found
	 */
	public function get_field_meta( $field, $meta_key, $form = '' ) {

		// Get field metadata
		if ( is_numeric( $field ) && ! empty( $form ) ) {

			// Form ID provided
			if ( is_numeric( $form ) ) {
				$form = GFFormsModel::get_form_meta( (int) $form );
			}

			// Read the field from the form's data
			$field = GFFormsModel::get_field( $form, $field );

		} elseif ( ! is_array( $field ) ) {
			return null;
		}

		// Get field setting
		return isset( $field[ $meta_key ] ) ? $field[ $meta_key ] : null;
	}

	/**
	 * Return whether the given form is marked vgsr-only
	 *
	 * @since 0.0.6
	 *
	 * @uses VGSR_GravityForms::get_form_meta()
	 * @uses apply_filters() Calls 'vgsr_gf_is_form_vgsr_only'
	 *
	 * @param array|int $form Form object or form ID
	 * @return bool Form is marked vgsr-only
	 */
	public function is_form_vgsr_only( $form ) {
		return (bool) apply_filters( 'vgsr_gf_is_form_vgsr_only', (bool) $this->get_form_meta( $form, $this->meta_key ), $form );
	}

	/**
	 * Return whether the given field is marked vgsr-only
	 *
	 * @since 0.0.7
	 *
	 * @uses VGSR_GravityForms::get_field_meta()
	 * @uses apply_filters() Calls 'vgsr_gf_is_field_vgsr_only'
	 *
	 * @param array|int $field Field object or Field ID
	 * @param array|int $form Form object or form ID
	 * @return bool Field is marked vgsr-only
	 */
	public function is_field_vgsr_only( $field, $form = '' ) {
		return (bool) apply_filters( 'vgsr_gf_is_field_vgsr_only', (bool) $this->get_field_meta( $field, $this->meta_key, $form ), $field, $form );
	}

	/**
	 * Do not display vgsr-only marked forms to non-vgsr users
	 *
	 * @since 0.0.7
	 * 
	 * @uses VGSR_GravityForms::is_form_vgsr_only()
	 * @uses is_user_vgsr()
	 *
	 * @param string $content The form HTML content
	 * @param array $form Form meta data
	 * @return string Form HTML
	 */
	public function handle_form_display( $content, $form ) {

		// Return empty content when user is not VGSR
		if ( ! empty( $form ) && $this->is_form_vgsr_only( $form ) && ! is_user_vgsr() ) {
			$content = '';
		}

		return $content;
	}

	/**
	 * Do not display vgsr-only marked fields to non-vgsr users
	 *
	 * @since 0.0.7
	 *
	 * @uses is_admin()
	 * @uses VGSR_GravityForms::is_field_vgsr_only()
	 * @uses is_user_vgsr()
	 *
	 * @param string $content The field HTML content
	 * @param array $field Field meta data
	 * @param mixed $value The field's value
	 * @param int $empty 0
	 * @param int $form_id The field's form ID
	 * @return string Field HTML
	 */
	public function handle_field_display( $content, $field, $value, $empty, $form_id ) {

		// On the front end, return empty content when user is not VGSR
		if ( ! is_admin() && ! empty( $field ) && $this->is_field_vgsr_only( $field, $form_id ) && ! is_user_vgsr() ) {
			$content = '';
		}

		return $content;
	}

	/**
	 * Return a translated string with the 'gravityforms' context
	 *
	 * @since 0.0.7
	 *
	 * @uses call_user_func_array() To call __() indirectly
	 * @param string $string String to be translated
	 * @return string Translation
	 */
	public function get_gf_translation( $string ) {
		return call_user_func_array( '__', array( $string, 'gravityforms' ) );
	}

	/** Form Settings ******************************************************/

	/**
	 * Manipulate the form settings sections
	 *
	 * @since 0.0.6
	 *
	 * @uses gform_tooltip()
	 * @uses VGSR_GravityForms::get_form_meta()
	 * @uses VGSR_GravityForms::get_gf_translation()
	 *
	 * @param array $settings Form settings sections
	 * @param object $form Form object
	 */
	public function register_form_setting( $settings, $form ) {

		// Start output buffer and setup our settings field markup
		ob_start(); ?>

		<tr>
			<th><?php _e( 'VGSR-only', 'vgsr' ); ?> <?php gform_tooltip( 'vgsr_only_form_setting' ); ?></th>
			<td>
				<input type="checkbox" id="vgsr_form_vgsr_only" name="vgsr_form_vgsr_only" value="1" <?php checked( $this->get_form_meta( $form, $this->meta_key ) ); ?> />
				<label for="vgsr_form_vgsr_only"><?php _e( 'Mark this form as VGSR-only', 'vgsr' ); ?></label>
			</td>
		</tr>

		<?php

		// Settings sections are stored by their translatable title
		$section = $this->get_gf_translation( 'Restrictions' );

		// Append the field to the section and end the output buffer
		$settings[ $section ][ $this->meta_key ] = ob_get_clean();

		return $settings;
	}

	/**
	 * Run the update form setting logic
	 *
	 * @since 0.0.6
	 *
	 * @param array $settings Settings to be updated
	 * @return array Settings
	 */
	public function update_form_settings( $settings ) {

		// Sanitize form from $_POST var
		$settings[ $this->meta_key ] = isset( $_POST['vgsr_form_vgsr_only'] ) ? 1 : 0;

		return $settings;
	}

	/**
	 * Mark the form vgsr-only in the forms list
	 * 
	 * @since 0.0.6
	 *
	 * @uses VGSR_GravityForms::is_form_vgsr_only()
	 * 
	 * @param array $actions Form actions
	 * @param int $form_id Form ID
	 * @return array Form actions
	 */
	public function admin_form_actions( $actions, $form_id ) {

		// Form is marked vgsr-only
		if ( $this->is_form_vgsr_only( $form_id ) ) {

			// Output hidden reference element. Used in JS
			echo '<span class="form_is_vgsr_only hidden"></span>';
		}

		return $actions;
	}

	/**
	 * Output custom scripts
	 *
	 * @since 0.0.7
	 *
	 * @global string $hook_suffix
	 */
	public function admin_print_scripts() {
		global $hook_suffix;

		// This is the forms listing page
		if ( 'toplevel_page_gf_edit_forms' == $hook_suffix && ! isset( $_GET['id'] ) ) { ?>

			<script type="text/javascript">
				jQuery(document).ready( function($) {
					// Find reference elements
					$( 'span.form_is_vgsr_only' ).each( function() {
						// Add class to row and remove reference element
						$(this).parents('tr').addClass( 'vgsr_only' ).end().remove();
					});
				});
			</script>

			<style type="text/css">
				tr.vgsr_only .column-title strong:after {
					content: ' - <?php _e( 'vgsr', 'vgsr' ); ?>';
					font-size: 14px !important;
					text-transform: uppercase;
				}
			</style>

			<?php
		}
	}

	/** Field Settings *****************************************************/

	/**
	 * Display form field settings
	 *
	 * @since 0.0.6
	 *
	 * @uses gform_tooltip()
	 *
	 * @param int $position Settings position
	 * @param int $form_id Form ID
	 */
	public function register_field_setting( $position, $form_id ) {

		// After Visibility settings
		if ( 450 == $position ) { ?>

			<li class="vgsr_only_setting">
				<input type="checkbox" id="vgsr_form_field_vgsr_only" name="vgsr_form_field_vgsr_only" value="1" onclick="SetFieldProperty( '<?php echo $this->meta_key; ?>', this.checked );" />
				<label for="vgsr_form_field_vgsr_only" class="inline"><?php _e( 'Mark this field as VGSR-only', 'vgsr' ); ?> <?php gform_tooltip( 'vgsr_only_field_setting' ); ?></label>

				<script type="text/javascript">
					// Hook to GF's field settings load trigger
					jQuery( document ).on( 'gform_load_field_settings', function( e, field, form ) {
						jQuery( '#vgsr_form_field_vgsr_only' ).attr( 'checked', typeof field.<?php echo $this->meta_key; ?> === 'undefined' ? false : field.<?php echo $this->meta_key; ?> );
					});

					// Mark selected field
					jQuery( '#vgsr_form_field_vgsr_only' ).on( 'change', function() {
						jQuery( '.field_selected' ).removeClass( 'vgsr_only' ).filter( function() {
							return ! GetSelectedField()[ '<?php echo $this->meta_key; ?>' ];
						} ).addClass( 'vgsr_only' );
					});
				</script>

				<style type="text/css">
					.vgsr_only .gfield_label .gfield_required:before {
						content: '<?php _e( 'vgsr', 'vgsr' ); ?>';
						color: #888;
						text-transform: uppercase;
						margin-right: 5px;
					}
				</style>
			</li>

			<?php
		}
	}

	/**
	 * Modify the form field classes
	 *
	 * @since 0.0.7
	 *
	 * @uses VGSR_GravityForms::is_field_vgsr_only()
	 * 
	 * @param string $classes Classes
	 * @param array $field Field object
	 * @param array $form Form object
	 * @return string Classes
	 */
	public function add_field_class( $classes, $field, $form ) {

		// Field is marked vgsr-only
		if ( $this->is_field_vgsr_only( $field, $form ) ) {
			$classes .= ' vgsr_only';
		}

		return $classes;
	}

	/** Tooltips ***********************************************************/

	/**
	 * Append our custom tooltips to GF's tooltip collection
	 *
	 * @since 0.0.7
	 *
	 * @link gravityforms/tooltips.php
	 * 
	 * @param array $tips Tooltips
	 * @return array Tooltips
	 */
	public function tooltips( $tips ) {

		// Each tooltip consists of an <h6> header with a short description after it
		$format = '<h6>%s</h6>%s';

		// Append our tooltips
		$tips = array_merge( $tips, array(
			'vgsr_only_form_setting'  => sprintf( $format, __( 'VGSR-only', 'vgsr' ), __( 'Only show this form to members of the VGSR group(s).',  'vgsr' ) ),
			'vgsr_only_field_setting' => sprintf( $format, __( 'VGSR-only', 'vgsr' ), __( 'Only show this field to members of the VGSR group(s).', 'vgsr' ) ),
		) );

		return $tips;
	}

	/** Misc ***************************************************************/

	/**
	 * Modify the csv separator for exported GF data
	 *
	 * When using a semicolon as separator, Excel somehow interpretes
	 * cells with line breaks (/n,/r) correctly. This prevents values
	 * with line breaks to be parsed as separate rows.
	 *
	 * @since 0.0.8
	 *
	 * @param string $sep CSV separator
	 * @return string CSV separator
	 */
	public function export_separator( $sep ) {
		return ';';
	}

	/** GF-Pages ***********************************************************/

	/**
	 * Hide single form for GF-Pages plugin
	 *
	 * @since 0.0.6
	 * 
	 * @uses VGSR_GravityForms::is_form_vgsr_only()
	 * @uses is_user_vgsr()
	 *
	 * @param bool $hide Whether to hide the form
	 * @param object $form Form data
	 * @return bool Whether to hide the form
	 */
	public function gf_pages_hide_form_vgsr_only( $hide, $form ) {

		// Set form to hide when the current user is not VGSR
		if ( $this->is_form_vgsr_only( $form->id ) && ! is_user_vgsr() ) {
			$hide = true;
		}

		return $hide;
	}
}

endif; // class_exists
