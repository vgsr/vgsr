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
 * Modify the form settings sections
 *
 * @since 0.0.6
 *
 * @param array $settings Form settings sections
 * @param object $form Form object
 */
function vgsr_gf_admin_register_form_setting( $settings, $form ) {

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

	// Settings sections are stored by their translatable title.
	// Append the field to the section and end the output buffer
	$settings[ vgsr_gf_i18n( 'Restrictions' ) ][ vgsr_gf_get_exclusivity_meta_key() ] = ob_get_clean();

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

	// Settings sections are stored by their translatable title.
	// Append the field to the section and end the output buffer
	$settings[ vgsr_gf_i18n( 'Form Options' ) ]['vgsrExporters'] = ob_get_clean();

	return $settings;
}

/**
 * Modify form field settings
 *
 * @since 0.0.6
 *
 * @param int $position Settings position
 * @param int $form_id Form ID
 */
function vgsr_gf_admin_register_field_setting( $position, $form_id ) {

	// Following the Visibility settings and the form is not already exclusive
	if ( 450 === $position && ! vgsr_gf_is_form_vgsr( $form_id, false ) ) : ?>

	<li class="vgsr_only_setting field_setting">
		<input type="checkbox" id="vgsr_form_field_vgsr" name="vgsr_form_field_vgsr" value="1" onclick="SetFieldProperty( '<?php vgsr_gf_exclusivity_meta_key(); ?>', this.checked );" />
		<label for="vgsr_form_field_vgsr" class="inline"><?php printf( esc_html__( 'VGSR: %s', 'vgsr' ), esc_html__( 'Make this an exclusive field', 'vgsr' ) ); ?> <?php gform_tooltip( 'vgsr_field_setting' ); ?></label>
	</li>

	<?php endif;
}
