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
 * @since 0.1.0
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
	 * @since 0.1.0
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

		// When the member type does exist
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
 * Display a members query tab for the given member type
 *
 * @since 0.1.0
 *
 * @uses vgsr_bp_get_members_member_type_tab()
 * @param string $member_type Member type
 */
function vgsr_bp_members_member_type_tab( $member_type ) {
	echo vgsr_bp_get_members_member_type_tab( $member_type );
}

	/**
	 * Return a members query tab for the given member type
	 *
	 * @since 0.1.0
	 *
	 * @uses bp_get_member_type_directory_permalink()
	 * @uses vgsr_bp_get_total_member_count()
	 * @uses apply_filters() Calls 'vgsr_bp_get_members_member_type_tab'
	 * @param string $member_type Member type
	 */
	function vgsr_bp_get_members_member_type_tab( $member_type ) {

		// Define local variables
		$tab   = '';
		$count = 0;

		// Get the member type object
		$member_type_object = bp_get_member_type_object( $member_type );

		if ( ! empty( $member_type_object ) ) {

			// Only display tab when there are members
			if ( $count = vgsr_bp_get_get_total_member_count( array( 'member_type__in' => $member_type ) ) ) {
				$tab = sprintf( '<li id="members-%s"><a href="%s">%s <span>%s</span></a></li>',
					"member_type_{$member_type_object->name}",
					esc_url( bp_get_member_type_directory_permalink( $member_type ) ),
					$member_type_object->labels['name'],
					$count
				);
			}
		}

		return apply_filters( 'vgsr_bp_get_members_member_type_tab', $tab, $member_type, $count );
	}

/**
 * Modify the total member count
 *
 * @since 0.1.0
 *
 * @uses BP_User_Query
 * @uses bp_core_get_total_member_count()
 *
 * @param array $args Query args for `BP_User_Query`
 * @return int Total member count
 */
function vgsr_bp_get_total_member_count( $args = array() ) {

	// With args, do custom count query
	if ( ! empty( $args ) ) {
		$args = wp_parse_args( $args, array(
			'type' => ''
		) );

		if ( $query = new BP_User_Query( $args ) ) {
			$count = $query->total_users;
		}

	// Return the *full* total member count
	} else {
		$count = bp_core_get_total_member_count();
	}

	return $count;
}
