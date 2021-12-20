<?php

/**
 * VGSR Gravity Forms Administration Functions
 *
 * @package VGSR
 * @subpackage Gravity Forms
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return whether to use GF's legacy settings
 *
 * @since 1.0.0
 *
 * @return Bool Use GF legacy settings?
 */
function vgsr_gf_admin_use_legacy_settings() {
	return version_compare( GFCommon::$version, '2.5', '<' );
}

/**
 * Get the form settings fields
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_gf_admin_get_form_settings_fields'
 * @return array Form settings fields
 */
function vgsr_gf_admin_get_form_settings_fields() {
	$legacy_settings = vgsr_gf_admin_use_legacy_settings();

	return (array) apply_filters( 'vgsr_gf_admin_get_form_settings_fields', array(

		// VGSR Only
		vgsr_gf_get_exclusivity_meta_key() => array(
			'section'           => $legacy_settings ? 'Restrictions' : 'form_basics',
			'type'              => 'toggle',
			'title'             => esc_html__( 'VGSR', 'vgsr' ),
			'tooltip'           => $legacy_settings ? 'vgsr_form_setting' : gform_tooltip( 'vgsr_form_setting', '', true ),
			'callback'          => 'vgsr_gf_admin_form_setting_callback_vgsr_only',
			'sanitize_callback' => 'intval',
		),

		// Exporters
		'vgsrExporters' => array(
			'section'           => $legacy_settings ? 'Form Options' : 'form_options',
			'type'              => 'text',
			'title'             => esc_html__( 'Exporters', 'vgsr' ),
			'tooltip'           => $legacy_settings ? 'vgsr_form_exporters' : gform_tooltip( 'vgsr_form_exporters', '', true ),
			'callback'          => 'vgsr_gf_admin_form_setting_callback_vgsr_exporters',
			'sanitize_callback' => 'vgsr_gf_sanitize_id_list_to_string',
		)
	) );
}

/**
 * Display the vgsr exclusivity form legacy settings field
 *
 * @since 1.0.0
 *
 * @param array $form Form data
 */
function vgsr_gf_admin_form_setting_callback_vgsr_only( $form ) {
	$meta_key = vgsr_gf_get_exclusivity_meta_key(); ?>

	<input type="checkbox" id="<?php echo $meta_key; ?>" name="<?php echo $meta_key; ?>" value="1" <?php checked( vgsr_gf_get_form_meta( $form, $meta_key ) ); ?> />
	<label for="<?php echo $meta_key; ?>"><?php esc_html_e( 'Make this an exclusive form', 'vgsr' ); ?></label>

	<?php
}

/**
 * Display the vgsr exporters form legacy settings field
 *
 * @since 1.0.0
 *
 * @param array $form Form data
 */
function vgsr_gf_admin_form_setting_callback_vgsr_exporters( $form ) {
	$exporters = vgsr_gf_get_form_meta( $form, 'vgsrExporters' );

	?>

	<input type="text" id="vgsrExporters" name="vgsrExporters" value="<?php echo $exporters; ?>" />

	<?php
}

/**
 * Sanitize id list and return concatenated string
 *
 * @since 1.0.0
 *
 * @param  string $value Input to sanitize
 * @return string Sanitized id list
 */
function vgsr_gf_sanitize_id_list_to_string( $value ) {
	return implode( ',', wp_parse_id_list( $value ) );
}
