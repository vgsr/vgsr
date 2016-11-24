<?php

/**
 * VGSR Settings Functions
 *
 * @package VGSR
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined('ABSPATH' ) ) exit;

/**************************************************************************/

/**
 * Return admin settings sections
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_admin_get_settings_sections' with the sections
 * @return array Settings sections
 */
function vgsr_admin_get_settings_sections() {
	return (array) apply_filters( 'vgsr_admin_get_settings_sections', array(

		// Main settings
		'vgsr_settings_main' => array(
			'title'    => __( 'Main Settings', 'vgsr' ),
			'callback' => 'vgsr_admin_setting_callback_main_section',
			'page'     => 'vgsr'
		),

		// Access settings
		'vgsr_settings_access' => array(
			'title'    => __( 'Access Settings', 'vgsr' ),
			'callback' => 'vgsr_admin_setting_callback_access_section',
			'page'     => 'vgsr'
		)
	) );
}

/**
 * Return admin settings fields
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_admin_get_settings_fields' with the fields
 * @return array Settings fields
 */
function vgsr_admin_get_settings_fields() {
	return (array) apply_filters( 'vgsr_admin_get_settings_fields', array(

		/** Main Section **************************************************/
		
		'vgsr_settings_main' => array(
		),

		/** Access Section ************************************************/
		
		'vgsr_settings_access' => array(

			// Private reading post types
			// '_vgsr_private_reading_post_types' => array(
			// 	'title'             => __( 'Private Reading', 'vgsr' ),
			// 	'callback'          => 'vgsr_setting_callback_private_reading_post_types',
			// 	'sanitize_callback' => '',
			// 	'args'              => array()
			// )
		)
	) );
}

/**
 * Get settings fields by section
 *
 * @since 0.0.1
 * 
 * @param string $section_id Section id
 * @return mixed False if section is invalid, array of fields otherwise
 */
function vgsr_admin_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) )
		return false;

	$fields = vgsr_admin_get_settings_fields();
	$retval = isset( $fields[$section_id] ) ? $fields[$section_id] : false;

	return (array) apply_filters( 'vgsr_admin_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Return whether the admin page has registered settings
 *
 * @since 0.1.0
 *
 * @param string $page
 * @return bool Does the admin page have settings?
 */
function vgsr_admin_page_has_settings( $page = '' ) {

	// Bail when page is empty
	if ( empty( $page ) )
		return false;

	// Loop through the available sections
	$sections = wp_list_filter( vgsr_admin_get_settings_sections(), array( 'page' => $page ) );
	foreach ( (array) $sections as $section_id => $section ) {

		// Find out whether the section has fields
		$fields = vgsr_admin_get_settings_fields_for_section( $section_id );
		if ( ! empty( $fields ) ) {
			return true;
		}
	}

	return false;
}

/** Main Section **********************************************************/

/**
 * Main settings section description for the settings page
 * 
 * @since 0.0.1
 */
function vgsr_admin_setting_callback_main_section() { ?>

	<p><?php esc_html_e( 'Set the main functionality options for VGSR.', 'vgsr' ); ?></p>

	<?php
}

/** Access Section ********************************************************/

/**
 * Access settings section description for the settings page
 * 
 * @since 0.0.6
 */
function vgsr_admin_setting_callback_access_section() { ?>

	<p><?php esc_html_e( "Tweak here the settings about access to your site's contents.", 'vgsr' ); ?></p>

	<?php
}

/**
 * Private reading post types settings field
 *
 * @since 0.0.6
 *
 * @uses vgsr_get_private_reading_post_types()
 */
function vgsr_setting_callback_private_reading_post_types() {
	global $wp_post_types;

	// Get the saved post types
	$option = vgsr_get_private_reading_post_types(); ?>

	<p><?php _e( 'Select the post types of which VGSR users will read posts that are privately published.', 'vgsr' ); ?></p>
	<ul>
		<?php foreach ( $wp_post_types as $post_type ) : ?>

		<li>
			<label>
				<input type="checkbox" name="_vgsr_private_reading_post_types[]" value="<?php echo $post_type->name; ?>" <?php checked( in_array( $post_type->name, $option ) ); ?>/> 
				<?php echo $post_type->labels->name; ?>
			</label>
		</li>

		<?php endforeach; ?>
	</ul>

	<?php
}

/** Settings Page *************************************************************/

/**
 * The main plugin settings page
 *
 * @since 0.0.1
 *
 * @uses do_action() Calls 'vgsr_admin_page-{$page}'
 */
function vgsr_admin_page() {

	// Define the form action
	$form_action = is_network_admin() ? vgsr_admin_url() : 'options.php';

	?>

	<div class="wrap">

		<h1><?php esc_html_e( 'VGSR Settings', 'vgsr' ); ?></h1>

		<h2 class="nav-tab-wrapper"><?php vgsr_admin_page_tabs(); ?></h2>

		<?php switch ( vgsr_admin_page_get_current_page() ) :
			case 'vgsr' : ?>

		<form action="<?php echo $form_action; ?>" method="post">

			<?php settings_fields( 'vgsr' ); ?>

			<?php do_settings_sections( 'vgsr' ); ?>

			<?php submit_button(); ?>

		</form>

			<?php
				break;

			// Default to custom page content
			default :
				do_action( 'vgsr_admin_page-' . vgsr_admin_page_get_current_page() );

		endswitch; ?>

	</div>

	<?php
}

/**
 * Display the admin settings page tabs items
 *
 * @since 0.1.0
 */
function vgsr_admin_page_tabs() {

	// Get the admin pages
	$pages = vgsr_admin_page_get_pages();
	$page  = vgsr_admin_page_get_current_page();

	// Walk registered pages
	foreach ( $pages as $slug => $label ) {

		// Skip empty pages
		if ( empty( $label ) )
			continue;

		// Print the tab item
		printf( '<a class="nav-tab%s" href="%s">%s</a>',
			( $page === $slug ) ? ' nav-tab-active' : '',
			esc_url( add_query_arg( array( 'page' => $slug ), vgsr_admin_url() ) ),
			$label
		);
	}
}

/**
 * Return the admin page pages
 *
 * @since 0.0.7
 *
 * @uses apply_filters() Calls 'vgsr_admin_page_get_pages'
 * @return array Tabs as $page-slug => $label
 */
function vgsr_admin_page_get_pages() {

	// Setup return value
	$pages = array();

	// Add the main page for 
	if ( vgsr_admin_page_has_settings( 'vgsr' ) ) {
		$pages['vgsr'] = esc_html__( 'Main', 'vgsr' );
	}

	return (array) apply_filters( 'vgsr_admin_page_get_pages', $pages );
}

/**
 * Return whether any admin page pages are registered
 *
 * @since 0.1.0
 *
 * @return bool Haz admin page pages?
 */
function vgsr_admin_page_has_pages() {
	return (bool) vgsr_admin_page_get_pages();
}

/**
 * Return the current admin page
 *
 * @since 0.1.0
 *
 * @return string The current admin page. Defaults to the first page.
 */
function vgsr_admin_page_get_current_page() {

	$pages = array_keys( vgsr_admin_page_get_pages() );
	$page  = ( isset( $_GET['page'] ) && in_array( $_GET['page'], $pages ) ) ? $_GET['page'] : false;

	// Default to the first page
	if ( ! $page && $pages ) {
		$page = reset( $pages );
	}

	return $page;
}

/**
 * Save admin settings
 *
 * @see bp_core_admin_settings_save()
 *
 * @since 1.0.0
 */
function vgsr_admin_settings_save() {
	global $wp_settings_fields;

	// Core settings are submitted
	if ( is_network_admin() && isset( $_GET['page'] ) && 'vgsr' == $_GET['page'] && ! empty( $_POST['submit'] ) ) {
		check_admin_referer( 'vgsr-options' );

		// Because many settings are saved with checkboxes, and thus will have no values
		// in the $_POST array when unchecked, we loop through the registered settings.
		if ( isset( $wp_settings_fields['vgsr'] ) ) {
			foreach ( (array) $wp_settings_fields['vgsr'] as $section => $settings ) {
				foreach ( $settings as $setting_name => $setting ) {
					$value = isset( $_POST[ $setting_name ] ) ? $_POST[ $setting_name ] : '';

					update_network_option( null, $setting_name, $value );
				}
			}
		}

		wp_safe_redirect( add_query_arg( array( 'updated' => 'true' ), vgsr_admin_url() ) );
		die;
	}
}

/** Helpers *******************************************************************/

/**
 * Output settings API option
 *
 * @since 0.0.1
 *
 * @param string $option
 * @param string $default
 * @param bool $slug
 */
function vgsr_form_option( $option, $default = '' , $slug = false ) {
	echo vgsr_get_form_option( $option, $default, $slug );
}

	/**
	 * Return settings API option
	 *
	 * @since 0.0.1
	 *
	 * @uses apply_filters() Calls 'vgsr_get_form_option'
	 *
	 * @param string $option
	 * @param string $default
	 * @param bool $slug
	 * @return mixed Option form value
	 */
	function vgsr_get_form_option( $option, $default = '', $slug = false ) {

		// Get the option and sanitize it
		$value = get_network_option( null, $option, $default );

		// Slug?
		if ( true === $slug ) {
			$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
		} else {
			$value = esc_attr( $value );
		}

		// Fallback to default
		if ( empty( $value ) )
			$value = $default;

		// Allow plugins to further filter the output
		return apply_filters( 'vgsr_get_form_option', $value, $option );
	}
