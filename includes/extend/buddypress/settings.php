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

		// First Name field
		'_vgsr_bp_first_name_field' => array(
			'title'             => esc_html_x( 'First name', 'Profile field name', 'vgsr' ),
			'callback'          => 'vgsr_bp_admin_setting_callback_xprofile_field',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'setting'     => '_vgsr_bp_first_name_field',
				'description' => esc_html__( "Select the field that holds the member's value for this data.", 'vgsr' )
			)
		),

		// Surname Prefix field
		'_vgsr_bp_surname_prefix_field' => array(
			'title'             => esc_html_x( 'Surname prefix', 'Profile field name', 'vgsr' ),
			'callback'          => 'vgsr_bp_admin_setting_callback_xprofile_field',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'setting'     => '_vgsr_bp_surname_prefix_field',
				'description' => esc_html__( "Select the field that holds the member's value for this data.", 'vgsr' )
			)
		),

		// Last Name field
		'_vgsr_bp_last_name_field' => array(
			'title'             => esc_html_x( 'Last name', 'Profile field name', 'vgsr' ),
			'callback'          => 'vgsr_bp_admin_setting_callback_xprofile_field',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'setting'     => '_vgsr_bp_last_name_field',
				'description' => esc_html__( "Select the field that holds the member's value for this data.", 'vgsr' )
			)
		),

		// Jaargroep field
		'_vgsr_bp_jaargroep_field' => array(
			'title'             => esc_html_x( 'Jaargroep', 'Profile field name', 'vgsr' ),
			'callback'          => 'vgsr_bp_admin_setting_callback_xprofile_field',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'setting'     => '_vgsr_bp_jaargroep_field',
				'description' => esc_html__( "Select the field that holds the member's value for this data.", 'vgsr' )
			)
		),

		// Ancienniteit field
		'_vgsr_bp_ancienniteit_field' => array(
			'title'             => esc_html_x( 'Anciënniteit', 'Profile field name', 'vgsr' ),
			'callback'          => 'vgsr_bp_admin_setting_callback_xprofile_field',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'setting'     => '_vgsr_bp_ancienniteit_field',
				'description' => esc_html__( "Select the field that holds the member's value for this data.", 'vgsr' )
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
function vgsr_bp_admin_setting_callback_profile_fields() { ?>

	<p><?php esc_html_e( 'The following field settings enable synchronization between BuddyPress profile fields and WordPress user data. When either field is updated, the associated data is updated as well.', 'vgsr' ); ?></p>

	<?php
}

/**
 * Display an XProfile field selector for the setting field
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
