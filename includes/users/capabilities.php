<?php

/**
 * VGSR User Capabilites
 *
 * Used to map user capabilities to WordPress's existing capabilities.
 *
 * @package VGSR
 * @subpackage Capabilities
 */

/**
 * Add private read caps to VGSR users
 *
 * @since 0.0.6
 *
 * @uses is_user_vgsr()
 * @uses vgsr_get_private_reading_post_types()
 * @uses WP_User::add_cap()
 * @uses get_post_type_object()
 * @param int $user_id User ID
 */
function vgsr_user_add_private_caps( $user_id = 0 ) {

	// Bail if user is not VGSR member
	if ( ! is_user_vgsr( $user_id ) )
		return;

	// Get user object and add private read caps
	$user = new WP_User( $user_id );
	foreach ( vgsr_get_private_reading_post_types() as $post_type ) {
		$user->add_cap( get_post_type_object( $post_type )->cap->read_private_posts );
	}
}

/**
 * Remove private read caps from VGSR users
 *
 * @since 0.0.6
 *
 * @uses is_user_vgsr()
 * @uses vgsr_get_private_reading_post_types()
 * @uses get_post_type_object()
 * @uses WP_User::remove_cap()
 * @uses WP_User::add_cap()
 * @global WP_Roles
 * @param int $user_id User ID
 */
function vgsr_user_remove_private_caps( $user_id = 0 ) {

	// Bail if user is still VGSR member
	if ( is_user_vgsr( $user_id ) )
		return;

	global $wp_roles;

	if ( ! isset( $wp_roles ) )
		$wp_roles = new WP_Roles();

	// Get user object and collect all role caps...
	$user      = new WP_User( $user_id );
	$role_caps = array();
	foreach ( (array) $user->roles as $role ) {
		$the_role  = $wp_roles->get_role( $role );
		$role_caps = array_merge( $role_caps, (array) $the_role->capabilities );
	}

	// ... and remove private read caps that are not part of its current roles
	foreach ( vgsr_get_private_reading_post_types() as $post_type ) {
		$cap = get_post_type_object( $post_type )->cap->read_private_posts;

		// Remove cap since its not part of a role
		if ( ! in_array( $cap, array_keys( $role_caps ) ) ) {
			$user->remove_cap( $cap );

		// Set it to role value
		} else {
			$user->add_cap( $cap, $role_caps[$cap] );
		}
	}
}
