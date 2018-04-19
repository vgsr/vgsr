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
 * @param  array $field_types List of field types
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
 * @param  array $children Field children
 * @param  bool $for_editing Whether the children are for editing
 * @param  BP_XProfile_Field $field Field object
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

/** Template ***************************************************************/

/**
 * Output or return a dropdown with XProfile fields
 *
 * @since 1.0.0
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
