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
		require( $this->includes_dir . 'functions.php' );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 0.0.6
	 */
	private function setup_actions() {

		// Core
		add_action( 'admin_menu',            array( $this, 'admin_menu'            ), 50    );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' )        );
		add_filter( 'vgsr_map_meta_caps',    array( $this, 'map_meta_caps'         ), 10, 4 );

		// Settings
		// add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_gf_settings_sections' );
		// add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_gf_settings_fields'   );
		// add_filter( 'vgsr_map_settings_meta_caps', array( $this, 'map_settings_meta_caps' ), 10, 4 );

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
		add_action( 'gform_editor_js',               array( $this, 'print_editor_scripts'   )        );

		// Widgets
		add_filter( 'widget_display_callback', array( $this, 'handle_widget_display' ), 5, 3 );

		// Tooltips
		add_filter( 'gform_tooltips', array( $this, 'tooltips' ) );

		// Export
		add_filter( 'gform_export_separator', array( $this, 'export_separator' ) );

		// GF-Pages
		add_filter( 'gf_pages_hide_form', array( $this, 'gf_pages_hide_form_vgsr' ), 10, 2 );
	}

	/** Capabilities *******************************************************/

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
	public function map_settings_meta_caps( $caps, $cap, $user_id, $args ) {

		switch ( $cap ) {
			case 'vgsr_settings_gf_general' :
				$caps = array( vgsr()->admin->minimum_capability );
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
	 * Return a translated string with the 'gravityforms' context
	 *
	 * @since 0.0.7
	 *
	 * @param string $string String to be translated
	 * @return string Translation
	 */
	public function i18n( $string ) {
		return call_user_func_array( '__', array( $string, 'gravityforms' ) );
	}

	/** Form Settings ******************************************************/

	/**
	 * Manipulate the form settings sections
	 *
	 * @since 0.0.6
	 *
	 * @param array $settings Form settings sections
	 * @param object $form Form object
	 */
	public function register_form_setting( $settings, $form ) {

		/** Exclusivity ****************************************************/

		// Start output buffer and setup our settings field markup
		ob_start(); ?>

		<tr>
			<th><?php esc_html_e( 'VGSR', 'vgsr' ); ?> <?php gform_tooltip( 'vgsr_form_setting' ); ?></th>
			<td>
				<input type="checkbox" id="vgsr_form_vgsr" name="vgsr_form_vgsr" value="1" <?php checked( vgsr_gf_get_form_meta( $form, vgsr_gf_get_exclusivity_meta_key() ) ); ?> />
				<label for="vgsr_form_vgsr"><?php esc_html_e( 'Make this an exclusive form', 'vgsr' ); ?></label>
			</td>
		</tr>

		<?php

		// Settings sections are stored by their translatable title
		$section = $this->i18n( 'Restrictions' );

		// Append the field to the section and end the output buffer
		$settings[ $section ][ vgsr_gf_get_exclusivity_meta_key() ] = ob_get_clean();

		/** Exporters ******************************************************/

		$exporters = vgsr_gf_get_form_meta( $form, 'vgsrExporters' );
		$exporters = $exporters ? array_map( 'intval', $exporters ) : array();

		// Start output buffer and setup our settings field markup
		ob_start(); ?>

		<tr>
			<th><?php esc_html_e( 'Exporters', 'vgsr' ); ?> <?php gform_tooltip( 'vgsr_form_exporters' ); ?></th>
			<td>
				<input type="text" id="vgsr_form_exporters" name="vgsr_form_exporters" value="<?php echo implode( ',', $exporters ); ?>" />
			</td>
		</tr>

		<?php

		// Settings sections are stored by their translatable title
		$section = $this->i18n( 'Form Options' );

		// Append the field to the section and end the output buffer
		$settings[ $section ]['vgsrExporters'] = ob_get_clean();

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
		$settings[ vgsr_gf_get_exclusivity_meta_key() ] = isset( $_POST['vgsr_form_vgsr'] ) ? 1 : 0;
		$settings['vgsrExporters'] = array_map( 'absint', explode( ',', $_POST['vgsr_form_exporters'] ) );

		return $settings;
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

	/**
	 * Output custom scripts
	 *
	 * @since 0.0.7
	 *
	 * @global string $hook_suffix
	 */
	public function admin_print_scripts() {
		global $hook_suffix; 

		// Bail when this is not the forms listing page
		if ( 'toplevel_page_gf_edit_forms' != $hook_suffix || isset( $_GET['id'] ) )
			return;

		?>

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

		<?php
	}

	/** Field Settings *****************************************************/

	/**
	 * Display form field settings
	 *
	 * @since 0.0.6
	 *
	 * @param int $position Settings position
	 * @param int $form_id Form ID
	 */
	public function register_field_setting( $position, $form_id ) {

		// Bail when not after the Visibility settings or the form is already exclusive
		if ( 450 !== $position || vgsr_gf_is_form_vgsr( $form_id, false ) )
			return;

		?>

		<li class="vgsr_only_setting field_setting">
			<input type="checkbox" id="vgsr_form_field_vgsr" name="vgsr_form_field_vgsr" value="1" onclick="SetFieldProperty( '<?php vgsr_gf_exclusivity_meta_key(); ?>', this.checked );" />
			<label for="vgsr_form_field_vgsr" class="inline"><?php printf( esc_html__( 'VGSR: %s', 'vgsr' ), esc_html__( 'Make this an exclusive field', 'vgsr' ) ); ?> <?php gform_tooltip( 'vgsr_field_setting' ); ?></label>
		</li>

		<?php
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
			'vgsr_form_setting'  => sprintf( $format, esc_html_x( 'VGSR', 'exclusivity title', 'vgsr' ), esc_html__( 'Make this form exclusively available to VGSR members.', 'vgsr' ) ),
			'vgsr_field_setting' => sprintf( $format, esc_html_x( 'VGSR', 'exclusivity title', 'vgsr' ), esc_html__( 'Make this field exclusively available to VGSR members.', 'vgsr' ) ),
			'vgsr_form_exporters'  => sprintf( $format, esc_html_x( 'Exporters', 'vgsr' ), esc_html__( "Provide a comma-separated list of ids of users who are allowed to export and download the form's entries along with their respondent's data.", 'vgsr' ) ),
		) );

		return $tips;
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

	/** Export *************************************************************/

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
