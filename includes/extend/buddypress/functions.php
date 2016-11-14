<?php

/**
 * VGSR BuddyPress Functions
 * 
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Member Types ***********************************************************/

/**
 * Return the collection of VGSR member types
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_member_types'
 * @return array Member types
 */
function vgsr_bp_member_types() {
	return (array) apply_filters( 'vgsr_bp_member_types', array(

		// Lid
		vgsr_bp_lid_member_type() => array(
			'labels' => array(
				'name'          => __( 'Leden', 'vgsr' ),
				'singular_name' => __( 'Lid',   'vgsr' ),
				'plural_name'   => __( 'Leden', 'vgsr' ),
			)
		),

		// Oud-lid
		vgsr_bp_oudlid_member_type() => array(
			'labels' => array(
				'name'          => __( 'Oud-leden', 'vgsr' ),
				'singular_name' => __( 'Oud-lid',   'vgsr' ),
				'plural_name'   => __( 'Oud-leden', 'vgsr' ),
			)
		),

		// Ex-lid
		vgsr_bp_exlid_member_type() => array(
			'labels' => array(
				'name'          => __( 'Ex-leden', 'vgsr' ),
				'singular_name' => __( 'Ex-lid',   'vgsr' ),
				'plural_name'   => __( 'Ex-leden', 'vgsr' ),
			)
		),
	) );
}

/**
 * Return the member type for Lid
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_lid_member_type'
 *
 * @return string Lid member type name
 */
function vgsr_bp_lid_member_type() {
	return apply_filters( 'vgsr_bp_lid_member_type', 'lid' );
}

/**
 * Return the member type for Oud-lid
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_oudlid_member_type'
 *
 * @return string Oud-lid member type name
 */
function vgsr_bp_oudlid_member_type() {
	return apply_filters( 'vgsr_bp_oudlid_member_type', 'oudlid' );
}

/**
 * Return the member type for Ex-lid
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_exlid_member_type'
 *
 * @return string Oud-lid member type name
 */
function vgsr_bp_exlid_member_type() {
	return apply_filters( 'vgsr_bp_exlid_member_type', 'exlid' );
}

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

		// Default to the displayed user
		if ( empty( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

		// Define local variable(s)
		$url = '';

		// Get the member type object
		$member_type_object = bp_get_member_type_object( $member_type );

		// When the member type does exist
		if ( ! empty( $member_type_object ) ) {

			// Get the args to add to the URL.
			$args = array(
				'action' => 'vgsr_promote',
				'type'   => $member_type_object->name,
				'append' => (int) (bool) $append,
			);

			// Construct action url
			$url = trailingslashit( bp_core_get_user_domain( $user_id ) . 'vgsr_promote' );
			$url = add_query_arg( $args, $url );
			$url = wp_nonce_url( $url, 'vgsr_promote_member_type_' . $member_type );
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
			if ( $count = vgsr_bp_get_total_member_count( array( 'member_type__in' => $member_type ) ) ) {
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

/** Members ****************************************************************/

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

/** Options ****************************************************************/

/**
 * Return whether custom activity posting is blocked
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_block_activity_posting'
 * @return bool Activity posting is blocked
 */
function vgsr_bp_block_activity_posting() {
	return apply_filters( 'vgsr_bp_block_activity_posting', true );
}
