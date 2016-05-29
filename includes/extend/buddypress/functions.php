<?php

/**
 * VGSR BuddyPress Functions
 * 
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Output the promote to member type url
 *
 * @since 1.0.0
 *
 * @uses vgsr_bp_get_member_type_promote_url()
 * @param string $member_type Member type
 * @param int $user_id User ID. Defaults to displayed user.
 * @param bool $append Whether to append or override the member type. Defaults to override.
 */
function vgsr_bp_member_type_promote_url( $member_type = '', $user_id = 0, $append = false ) {
	echo vgsr_bp_get_member_type_promote_url( $member_type, $append );
}

	/**
	 * Return the promote to member type url
	 *
	 * @since 1.0.0
	 *
	 * @uses bp_get_member_type_object()
	 * @uses bp_core_get_user_domain()
	 * @uses apply_filters() Calls 'vgsr_bp_get_member_type_promote_url'
	 *
	 * @param string $member_type Member type
	 * @param int $user_id User ID. Defaults to displayed user.
	 * @param bool $append Whether to append or override the member type. Defaults to override.
	 * @return string Member type promote url
	 */
	function vgsr_bp_get_member_type_promote_url( $member_type = '', $user_id = 0, $append = false ) {

		if ( empty( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

		// Define local variables
		$url = '';

		// Get the member type object
		$member_type_object = bp_get_member_type_object( $member_type );

		// Bail when the member type does not exist
		if ( ! empty( $member_type_object ) ) {

			// Get the args to add to the URL.
			$args = array(
				'action' => 'promote',
				'type'   => $member_type_object->name,
			);

			if ( $append ) {
				$args['append'] = 1;
			} 

			// Base unread URL.
			$url = trailingslashit( bp_core_get_user_domain( $user_id ) . 'promote' );

			// Add the args to the URL.
			$url = add_query_arg( $args, $url );

			// Add the nonce.
			$url = wp_nonce_url( $url, 'vgsr_bp_member_promote_member_type_' . $member_type );
		}

		return apply_filters( 'vgsr_bp_get_member_type_promote_url', $url, $member_type, $user_id, $append );
	}

/**
 * Handle promoting a member to a given member type
 *
 * @since 1.0.0
 *
 * @uses bp_is_user()
 * @uses bp_is_current_action()
 * @uses bp_verify_nonce_request()
 * @uses bp_set_member_type()
 * @uses bp_core_add_message()
 * @uses bp_core_redirect()
 *
 * @return false|null Returns false on failure. Otherwise redirects backc to the
 *                    member's home page.
 */
function vgsr_bp_member_promote_member_type() {

	// Bail when this is not a promotion action
	if ( ! bp_is_user() )
		return false;

	$action = ! empty( $_GET['action'] ) ? $_GET['action'] : '';
	$nonce  = ! empty( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
	$type   = ! empty( $_GET['type'] ) ? bp_get_member_type_object( $_GET['type'] ) : '';
	$append = ! empty( $_GET['append'] ) ? intval( $_GET['append'] ) : '';

	// Bail if no action or no ID
	if ( 'promote' !== $action || empty( $type ) || empty( $nonce ) )
		return false;

	// Check the nonce
	if ( ! bp_verify_nonce_request( 'vgsr_bp_member_promote_member_type_' . $type->name ) )
		return false;

	// Check user moderation cap
	if ( ! bp_current_user_can( 'bp_moderate' ) )
		return false;

	// Execute promotion
	if ( bp_set_member_type( bp_displayed_user_id(), $type->name, (bool) $append ) ) {
		bp_core_add_message( sprintf( __( 'Member promoted to %s.', 'vgsr' ), $type->labels['singular_name'] ) );
	} else {
		bp_core_add_message( __( 'There was a problem promoting the member.', 'vgsr' ), 'error' );
	}

	// Redirect back to the member's home page
	bp_core_redirect( bp_displayed_user_domain() );
}
add_action( 'bp_actions', 'vgsr_bp_member_promote_member_type' );
