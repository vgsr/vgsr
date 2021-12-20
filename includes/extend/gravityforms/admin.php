<?php

/**
 * VGSR Gravity Forms Administration Functions
 *
 * @package VGSR
 * @subpackage Gravity Forms
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_GravityForms_Admin' ) ) :
/**
 * The VGSR Gravity Forms Admin class
 *
 * @since 0.3.0
 */
class VGSR_GravityForms_Admin {

	/**
	 * Setup this class
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 0.3.0
	 */
	private function setup_actions() {

		// Core
		add_action( 'admin_menu',            array( $this, 'admin_menu'            ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' )     );

		// Forms & Fields
		add_filter( 'admin_head',                        array( $this, 'admin_print_scripts'            )        );
		add_filter( 'gform_form_actions',                array( $this, 'admin_form_actions'             ), 10, 2 );
		add_action( 'gform_editor_js',                   array( $this, 'print_editor_scripts'           )        );
		add_filter( 'gform_tooltips',                    array( $this, 'tooltips'                       )        );
		add_filter( 'gform_form_settings_fields',        array( $this, 'register_form_settings_fields'  ), 10, 2 );
		add_action( 'gform_field_advanced_settings',     array( $this, 'register_field_legacy_settings' ), 10, 2 );
		if ( vgsr_gf_admin_use_legacy_settings() ) {
			add_filter( 'gform_form_settings',           array( $this, 'register_form_legacy_settings'  ), 10, 2 );
			add_filter( 'gform_pre_form_settings_save',  array( $this, 'update_form_legacy_settings'    )        );
		}
	}

	/** Public methods **************************************************/

	/**
	 * Apply a i18n function with a custom context
	 *
	 * This method is used to circumvent direct use of external plugin domains
	 * which is considered illegal by some translation coding standards.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $args I18n function argument(s)
	 * @param string $i18n Optional. I18n function name. Defaults to '__'.
	 * @param string $domain Optional. Translation domain. Defaults to 'gravityforms'.
	 * @return string Translated text
	 */
	public function i18n( $args, $i18n = '__', $domain = 'gravityforms' ) {

		// Bail when no arguments were passed
		if ( empty( $args ) )
			return '';

		// Append translation domain
		$args   = (array) $args;
		$args[] = $domain;

		return call_user_func_array( $i18n, $args );
	}

	/**
	 * Modify the admin menus
	 *
	 * @see GFForms::create_menu()
	 *
	 * @since 0.3.0
	 */
	public function admin_menu() {

		// For exporters
		if ( ! current_user_can( 'gform_full_access' ) && current_user_can( 'vgsr_gf_export_entries' ) ) {

			// Remove GF's menu structure
			remove_menu_page( 'gf_export' );

			/**
			 * Create Forms export menu, since it doens't exist yet for these users
			 */
			$self = isset( $_GET['page'] ) && 'vgsr_gf_export' === $_GET['page'];
			$menu_name = 'vgsr_gf_export';
			$callback  = 'vgsr_gf_admin_export_page';

			// Main page
			$admin_icon = GFForms::get_admin_icon_b64( $self ? '#fff' : false );
			$forms_hook_suffix = add_menu_page( __( 'Forms', 'gravityforms' ), __( 'Forms', 'gravityforms' ), 'vgsr_gf_export_entries', $menu_name, $callback, $admin_icon, apply_filters( 'gform_menu_position', '16.9' ) );

			// Export sub page
			$export_hook = add_submenu_page( $menu_name, __( 'Export', 'vgsr' ), __( 'Export', 'vgsr' ), 'vgsr_gf_export_entries', $menu_name, $callback );

			// Load tooltips
			if ( $self ) {
				require_once( GFCommon::get_base_path() . '/tooltips.php' );
			}
		}
	}

	/**
	 * Enqueue admin styles and scripts
	 *
	 * @see GFForms::enqeuue_admin_scripts()
	 *
	 * @since 0.3.0
	 */
	public function admin_enqueue_scripts() {
		$export_page = is_admin() && isset( $_GET['page'] ) && 'vgsr_gf_export' === $_GET['page'];

		// Export form entries
		if ( $export_page ) {
			foreach ( array(
				'jquery-ui-datepicker',
				'gform_form_admin',
				'gform_field_filter',
				'sack',
			) as $script ) {
				wp_enqueue_script( $script );
			}
		}
	}

	/**
	 * Label exclusive forms in the forms list
	 * 
	 * @since 0.0.6
	 *
	 * @param array $actions Form actions
	 * @param int $form_id Form ID
	 * @return array Form actions
	 */
	public function admin_form_actions( $actions, $form_id ) {

		// Form is exclusive
		if ( vgsr_gf_is_form_vgsr( $form_id ) ) {

			// Output hidden reference element. Used in JS
			echo '<span class="form_is_vgsr hidden"></span>';
		}

		return $actions;
	}

	/** Form Settings ******************************************************/

	/**
	 * Register the plugin form setting's field
	 *
	 * This filter is used since GF 2.5.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Form settings sections and their fields
	 * @param array $form Form object
	 * @return array Form settings
	 */
	public function register_form_settings_fields( $settings, $form ) {

		// Get form settings fields
		$fields = vgsr_gf_admin_get_form_settings_fields();

		// Loop through fields
		foreach ( $fields as $field_id => $field ) {
			$section = $field['section'];

			// Map attributes
			$field['name'] = $field_id;
			$field['label'] = $field['title'];
			unset( $field['callback'] );

			// Settings sections are stored by their key
			if ( ! isset( $settings[ $section ] ) ) {
				$settings[ $section ] = array( 'title' => $this->i18n( 'Restrictions', 'esc_html__' ), 'fields' => array() );
			}

			// Append field
			$settings[ $section ]['fields'][] = $field;
		}

		return $settings;

	}

	/**
	 * Modify the form settings sections
	 *
	 * @since 0.0.6
	 *
	 * @param array $settings Form settings sections
	 * @param object $form Form object
	 */
	public function register_form_legacy_settings( $settings, $form ) {

		// Get form settings fields
		$fields = vgsr_gf_admin_get_form_settings_fields();

		// Loop through fields
		foreach ( $fields as $field_id => $field ) {

			// Parse field args
			$field = wp_parse_args( $field, array(
				'title'    => '',
				'tooltip'  => '',
				'section'  => '',
				'callback' => '',
			) );

			// Settings sections are stored by their translated title
			$section = $this->i18n( $field['section'] );

			// Setup section when the setting's section does not exist
			if ( ! isset( $settings[ $section ] ) ) {
				$settings[ $section ] = array();
			}

			// Prefix tooltip with title
			if ( ! empty( $field['tooltip'] ) ) {
				$field['tooltip'] = '<h6>' . $field['title'] . '</h6>' . $field['tooltip'];
			}

			// Construct and fetch the field's content
			ob_start(); ?>

	<tr>
		<th><?php echo esc_html( $field['title'] ); ?> <?php gform_tooltip( $field['tooltip'], "tooltip tooltip_{$field_id}" ); ?></th>
		<td><?php call_user_func_array( $field['callback'], array( $form ) ); ?></td>
	</tr>

			<?php

			$settings[ $section ][ $field_id ] = ob_get_clean();
		}

		return $settings;
	}

	/**
	 * Modify the updated form when settings are saved
	 *
	 * @since 0.0.6
	 *
	 * @param array $form Updated form
	 * @return array Updated form
	 */
	public function update_form_legacy_settings( $form ) {

		// Get form settings fields
		$fields = vgsr_gf_admin_get_form_settings_fields();

		// Loop through fields
		foreach ( $fields as $field_id => $field ) {

			// Get value from saved data
			$value = isset( $_POST[ $field_id ] ) ? $_POST[ $field_id ] : null;

			// Sanitize value
			if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$value = call_user_func_array( $field['sanitize_callback'], array( $value ) );
			}

			// Set updated value
			$form[ $field_id ] = $value;
		}

		return $form;
	}

	/**
	 * Modify form field settings
	 *
	 * @since 0.0.6
	 *
	 * @param int $position Settings position
	 * @param int $form_id Form ID
	 */
	public function register_field_legacy_settings( $position, $form_id ) {

		// Following the Visibility settings and the form is not already exclusive
		if ( 450 === $position && ! vgsr_gf_is_form_vgsr( $form_id, false ) ) : ?>

		<li class="vgsr_only_setting field_setting">
			<input type="checkbox" id="vgsr_form_field_vgsr" name="vgsr_form_field_vgsr" value="1" onclick="SetFieldProperty( '<?php vgsr_gf_exclusivity_meta_key(); ?>', this.checked );" />
			<label for="vgsr_form_field_vgsr" class="inline"><?php printf( esc_html__( 'VGSR: %s', 'vgsr' ), esc_html__( 'Make this an exclusive field', 'vgsr' ) ); ?> <?php gform_tooltip( 'vgsr_field_setting' ); ?></label>
		</li>

		<?php endif;
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

		// When this is the forms listing page
		if ( 'toplevel_page_gf_edit_forms' === $hook_suffix && ! isset( $_GET['id'] ) ) : ?>

		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				// Find reference elements
				$( 'span.form_is_vgsr' ).each( function() {
					// Add class to row and remove reference element
					$( this ).closest( 'tr' ).addClass( 'vgsr' ).end().remove();
				});
			});
		</script>

		<style type="text/css">
			tr.vgsr .column-title strong:after {
				content: '\2014  <?php _ex( 'vgsr', 'exclusivity label', 'vgsr' ); ?>';
				text-transform: uppercase;
				margin-left: 4px;
			}
		</style>

		<?php endif;
	}

	/**
	 * Print scripts and styles for the form editor
	 *
	 * @since 0.2.0
	 */
	public function print_editor_scripts() { ?>

		<script type="text/javascript">
			// Enable vgsr-only setting input for all field types
			for ( var i in fieldSettings ) {
				fieldSettings[i] += ', .vgsr_only_setting';
			}

			// Hook to GF's field settings load trigger
			jQuery( document ).on( 'gform_load_field_settings', function( e, field, form ) {
				jQuery('#vgsr_form_field_vgsr').attr( 'checked', typeof field.<?php vgsr_gf_exclusivity_meta_key(); ?> === 'undefined' ? false : field.<?php vgsr_gf_exclusivity_meta_key(); ?> );
			});

			// Mark selected field
			jQuery('#vgsr_form_field_vgsr').on( 'change', function() {
				jQuery('.field_selected').removeClass('vgsr-only').filter( function() {
					return !! GetSelectedField()['<?php vgsr_gf_exclusivity_meta_key(); ?>'];
				}).addClass('vgsr-only');
			});
		</script>

		<style type="text/css">
			.gfield.vgsr-only .gfield_label .gfield_required:before {
				content: '\2014  <?php _ex( 'vgsr', 'exclusivity label', 'vgsr' ); ?>';
				color: #888;
				text-transform: uppercase;
				margin-right: 5px;
			}
		</style>

		<?php
	}

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
			'vgsr_form_setting'  => sprintf( $format, esc_html_x( 'VGSR', 'exclusivity title', 'vgsr' ), esc_html__( 'Make this form exclusively available to VGSR members.', 'vgsr' ) ),
			'vgsr_field_setting' => sprintf( $format, esc_html_x( 'VGSR', 'exclusivity title', 'vgsr' ), esc_html__( 'Make this field exclusively available to VGSR members.', 'vgsr' ) ),
			'vgsr_form_exporters'  => sprintf( $format, esc_html_x( 'Exporters', 'vgsr' ), esc_html__( "Provide a comma-separated list of ids of users who are allowed to export and download the form's entries along with their respondent's data.", 'vgsr' ) ),
		) );

		return $tips;
	}
}

/**
 * Setup the admin logic for the Gravity Forms extension
 *
 * @since 0.3.0
 *
 * @uses VGSR_GravityForms_Admin
 */
function vgsr_gf_admin() {
	vgsr()->extend->gravityforms->admin = new VGSR_GravityForms_Admin;
}

endif; // class_exists
