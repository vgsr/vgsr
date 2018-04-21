<?php

/**
 * VGSR BuddyPress Settings Functions
 *
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Settings ***************************************************************/

/**
 * Add settings sections for BuddyPress options
 *
 * @since 0.0.1
 * 
 * @param array $sections Settings sections
 * @return array Settings sections
 */
function vgsr_bp_admin_settings_sections( $sections = array() ) {

	// Profile fields
	$sections['bp_profile_fields'] = array(
		'title'    => esc_html__( 'Profile Fields', 'vgsr' ),
		'callback' => 'vgsr_bp_admin_setting_callback_profile_fields',
		'page'     => 'vgsr'
	);

	return $sections;
}

/**
 * Add settings fields for BuddyPress options
 *
 * @since 0.0.1
 * 
 * @param array $fields Settings fields
 * @return array Settings fields
 */
function vgsr_bp_admin_settings_fields( $fields = array() ) {

	// Profile fields
	$fields['bp_profile_fields'] = array(

		// Jaargroep field
		'_vgsr_bp_jaargroep_field' => array(
			'title'             => esc_html__( 'Jaargroep Field', 'vgsr' ),
			'callback'          => 'vgsr_bp_admin_setting_callback_xprofile_field',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'setting'     => '_vgsr_bp_jaargroep_field',
				'description' => esc_html__( "Select the field that holds the member's Jaargroep value.", 'vgsr' )
			)
		),

		// Ancienniteit field
		'_vgsr_bp_ancienniteit_field' => array(
			'title'             => esc_html__( 'Ancienniteit Field', 'vgsr' ),
			'callback'          => 'vgsr_bp_admin_setting_callback_xprofile_field',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'setting'     => '_vgsr_bp_ancienniteit_field',
				'description' => esc_html__( "Select the field that holds the member's Anciennteit value.", 'vgsr' )
			)
		),

	);

	return $fields;
}

/** Profile Fields ********************************************************/

/**
 * Display the description for the Profile Fields section
 *
 * @since 1.0.0
 */
function vgsr_bp_admin_setting_callback_profile_fields() { /* Nothing to display */ }

/**
 * Display an XProfile field selector for the setting's input
 *
 * @since 1.0.0
 *
 * @param array $args Settings field arguments
 */
function vgsr_bp_admin_setting_callback_xprofile_field( $args = array() ) {

	// Bail when the setting isn't defined
	if ( ! isset( $args['setting'] ) || empty( $args['setting'] ) )
		return;

	// Bail when the XProfile component is not active
	if ( ! bp_is_active( 'xprofile' ) ) {
		echo '<p>' . esc_html__( 'Activate the Extended Profiles component to use this setting.', 'vgsr' ) . '</p>';
		return;
	}

	// Get the settings field
	$field = xprofile_get_field( get_site_option( $args['setting'], 0 ) );

	// Fields dropdown
	vgsr_bp_xprofile_fields_dropdown( array(
		'name'     => $args['setting'],
		'selected' => $field ? $field->id : false,
		'echo'     => true,
	) );

	// Display View link
	if ( current_user_can( 'bp_moderate' ) && $field ) {
		printf( ' <a class="button button-secondary" href="%s" target="_blank">%s</a>',
			esc_url( add_query_arg(
				array(
					'page'     => 'bp-profile-setup',
					'group_id' => $field->group_id,
					'field_id' => $field->id,
					'mode'     => 'edit_field'
				),
				bp_get_admin_url( 'users.php' )
			) ),
			esc_html__( 'View', 'vgsr' )
		);
	}

	// Output description
	if ( isset( $args['description'] ) && ! empty( $args['description'] ) ) {
		echo '<p class="description">' . $args['description'] . '</p>';
	}
}
