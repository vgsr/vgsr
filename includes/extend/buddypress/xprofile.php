<?php

/**
 * VGSR BuddyPress XProfile Functions
 *
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Modify the list of registered XProfile field types
 *
 * @since 0.2.0
 *
 * @param array $field_types List of field types
 * @return array List of field types
 */
function vgsr_bp_xprofile_register_field_types( $field_types ) {

	// Load class files
	require_once( vgsr()->extend->bp->includes_dir . 'classes/class-vgsr-bp-xprofile-field-type-vgsr-users.php' );

	// VGSR Users field type
	$field_types['vgsr_users'] = 'VGSR_BP_XProfile_Field_Type_VGSR_Users';

	return $field_types;
}

/**
 * Modify the field's children/options
 *
 * @since 0.2.0
 *
 * @param array $children Field children
 * @param bool $for_editing Whether the children are for editing
 * @param BP_XProfile_Field $field Field object
 * @return array Field children
 */
function vgsr_bp_xprofile_field_get_children( $children, $for_editing, $field ) {

	// VGSR Users field type
	if ( 'vgsr_users' === $field->type ) {

		// Get users
		$data = vgsr_get_users( array(
			'fields' => array( 'ID', 'display_name' ),
			'vgsr'   => bp_xprofile_get_meta( $field->id, 'field', 'user_type' )
		) );

		// Setup children list
		if ( $data ) {
			$children = array();

			foreach ( $data as $key => $user ) {
				// Mock an option child with default property values
				$children[] = (object) array(
					'id'                => "{$user->ID}",
					'group_id'          => '0',
					'parent_id'         => "{$field->id}",
					'type'              => 'option',
					'name'              => $user->display_name,
					'description'       => '',
					'is_required'       => '0',
					'is_default_option' => '0',
					'field_order'       => '0',
					'option_order'      => "{$key}",
					'order_by'          => '',
					'can_delete'        => '0'
				);
			}
		}
	}

	return $children;
}

/**
 * Save profile field object
 *
 * @since 0.2.0
 *
 * @param BP_XProfile_Field $field Field object
 */
function vgsr_bp_xprofile_save_field( $field ) {

	// VGSR Users field type
	if ( 'vgsr_users' === $field->type ) {

		// Skip when the option was not posted
		if ( isset( $_POST[ "user_type_{$field->type}" ] ) ) {
			bp_xprofile_update_meta( $field->id, 'field', 'user_type', $_POST[ "user_type_{$field->type}" ] );
		}
	}
}

/** Profile sync ***********************************************************/

/**
 * Return the profile fields to sync with user meta
 *
 * @since 0.2.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_xprofile_sync_get_fields'
 * @return array Profile fields to sync with user meta
 */
function vgsr_bp_xprofile_sync_get_fields() {
	return (array) apply_filters( 'vgsr_bp_xprofile_sync_get_fields', array(
		'jaargroep'    => get_site_option( '_vgsr_bp_jaargroep_field' ),
		'ancienniteit' => get_site_option( '_vgsr_bp_ancienniteit_field' )
	) );
}

/**
 * Return the meta key for which to sync the profile field
 *
 * @since 0.2.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_xprofile_sync_get_meta_for_field'
 *
 * @param BP_XProfile_Field|int $field Profile field object or ID
 * @return string|bool Meta key to sync or False when not found
 */
function vgsr_bp_xprofile_sync_get_meta_for_field( $field ) {
	$field    = xprofile_get_field( $field );
	$meta_key = false;

	if ( $field ) {
		foreach ( vgsr_bp_xprofile_sync_get_fields() as $_meta_key => $field_id ) {
			if ( $field_id && $field_id == $field->id ) {
				$meta_key = $_meta_key;
				break;
			}
		}
	}

	return apply_filters( 'vgsr_bp_xprofile_sync_get_meta_for_field', $meta_key, $field );
}

/**
 * Return the field for which to sync the user meta
 *
 * @since 0.2.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_xprofile_sync_get_field_for_meta'
 *
 * @param string $meta_key Meta key
 * @return BP_XProfile_Field|bool Profile field to sync or False when not found
 */
function vgsr_bp_xprofile_sync_get_field_for_meta( $meta_key ) {
	$field = false;

	foreach ( vgsr_bp_xprofile_sync_get_fields() as $_meta_key => $field_id ) {
		if ( $meta_key === $_meta_key && $_field = xprofile_get_field( $field_id ) ) {
			$field = $_field;
			break;
		}
	}

	return apply_filters( 'vgsr_bp_xprofile_sync_get_field_for_meta', $field, $meta_key );
}

/**
 * Update user meta value when the profile data changed
 *
 * @since 0.2.0
 *
 * @param BP_XProfile_ProfileData $profile_data Profile data
 */
function vgsr_bp_xprofile_sync_field_to_meta( $profile_data ) {

	// When synching meta, update profile field
	if ( $meta_key = vgsr_bp_xprofile_sync_get_meta_for_field( $profile_data->field_id ) ) {
		remove_action( 'updated_user_meta', 'vgsr_bp_xprofile_sync_meta_to_field', 10, 4 );
		update_user_meta( $profile_data->user_id, $meta_key, $profile_data->value );
		add_action( 'updated_user_meta', 'vgsr_bp_xprofile_sync_meta_to_field', 10, 4 );
	}
}

/**
 * Update profile data when the user meta value changed
 *
 * @since 0.2.0
 *
 * @param int $meta_id Meta record ID
 * @param int $user_id User ID
 * @param string $meta_key Meta key
 * @param mixed $meta_value Meta value
 */
function vgsr_bp_xprofile_sync_meta_to_field( $meta_id, $user_id, $meta_key, $meta_value ) {

	// When synching meta, update profile field
	if ( $field = vgsr_bp_xprofile_sync_get_field_for_meta( $meta_key ) ) {
		remove_action( 'xprofile_data_after_save', 'vgsr_bp_xprofile_sync_field_to_meta' );
		xprofile_set_field_data( $field->id, $user_id, $meta_value, $field->is_required );
		add_action( 'xprofile_data_after_save', 'vgsr_bp_xprofile_sync_field_to_meta' );
	}
}

/** Template ***************************************************************/

/**
 * Output or return a dropdown with XProfile fields
 *
 * @since 0.2.0
 *
 * @param array $args Dropdown arguments
 * @return string Dropdown HTML markup
 */
function vgsr_bp_xprofile_fields_dropdown( $args = array() ) {

	// Parse default args
	$args = wp_parse_args( $args, array(
		'id' => '', 'name' => '', 'multiselect' => false, 'selected' => 0, 'echo' => false,
	) );

	// Bail when missing attributes
	if ( empty( $args['name'] ) )
		return '';

	// Default id attribute to name
	if ( empty( $args['id'] ) ) {
		$args['id'] = $args['name'];
	}

	// Get all field groups with their fields
	$xprofile = bp_xprofile_get_groups( array( 'fetch_fields' => true, 'hide_empty_groups' => true ) );

	// Start dropdown markup
	$dd  = sprintf( '<select id="%s" name="%s" %s>', esc_attr( $args['id'] ), esc_attr( $args['name'] ), $args['multiselect'] ? 'multiple="multiple"' : '' );
	$dd .= '<option value="">' . esc_html__( '&mdash; No Field &mdash;', 'vgsr' )  . '</option>';

	// Walk profile groups
	foreach ( $xprofile as $field_group ) {

		// Start optgroup
		$dd .= sprintf( '<optgroup label="%s">', esc_attr( $field_group->name ) );

		// Walk profile group fields
		foreach ( $field_group->fields as $field ) {
			$dd .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $field->id ), selected( $args['selected'], $field->id, false ), esc_html( $field->name ) );
		}

		// Close optgroup
		$dd .= '</optgroup>';
	}

	// Close dropdown
	$dd .= '</select>';

	if ( $args['echo'] ) {
		echo $dd;
	} else {
		return $dd;
	}
}
