<?php

/**
 * VGSR BuddyPress Actions
 * 
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Handle promoting a member to a given member type
 *
 * @since 0.1.0
 *
 * @return false|null Returns false on failure. Otherwise redirects backc to the
 *                    member's home page.
 */
function vgsr_bp_member_promote_member_type() {

	// Bail when this is not an action for a user
	if ( ! bp_is_user() )
		return;

	$action = ! empty( $_GET['action']   ) ? $_GET['action'] : '';
	$nonce  = ! empty( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
	$type   = ! empty( $_GET['type']     ) ? bp_get_member_type_object( $_GET['type'] ) : '';
	$append = ! empty( $_GET['append']   ) ? intval( $_GET['append'] ) : true;

	// Bail if no action or no ID
	if ( 'vgsr_promote' !== $action || empty( $type ) || empty( $nonce ) )
		return;

	// Check the nonce
	if ( ! bp_verify_nonce_request( 'vgsr_promote_member_type_' . $type->name ) )
		return;

	// Check user moderation cap
	if ( ! bp_current_user_can( 'bp_moderate' ) )
		return;

	// Execute promotion
	if ( bp_set_member_type( bp_displayed_user_id(), $type->name, (bool) $append ) ) {
		bp_core_add_message( sprintf( __( 'Member promoted to %s.', 'vgsr' ), $type->labels['singular_name'] ) );
	} else {
		bp_core_add_message( __( 'There was a problem promoting this member.', 'vgsr' ), 'error' );
	}

	// Redirect back to the member's home page
	bp_core_redirect( bp_displayed_user_domain() );
}
add_action( 'bp_actions', 'vgsr_bp_member_promote_member_type' );
