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
