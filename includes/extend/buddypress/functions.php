<?php

/**
 * VGSR BuddyPress Functions
 * 
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Core *******************************************************************/

/**
 * Return whether we're on the root blog
 *
 * @since 0.2.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_is_root_blog'
 *
 * @return bool Is this the root blog?
 */
function vgsr_bp_is_root_blog() {
	$retval = bp_is_root_blog();

	// Consider BP Multiblog Mode plugin
	if ( function_exists( 'bp_multiblog_mode' ) ) {
		$retval = bp_multiblog_mode_is_root_blog();
	}

	return apply_filters( 'vgsr_bp_is_root_blog', $retval );
}

/**
 * Return the root blog ID
 *
 * @since 0.2.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_get_root_blog_id'
 *
 * @return int The root blog ID
 */
function vgsr_bp_get_root_blog_id() {
	$blog_id = bp_get_root_blog_id();

	// Consider BP Multiblog Mode plugin
	if ( function_exists( 'bp_multiblog_mode' ) ) {
		$blog_id = bp_multiblog_mode_get_root_blog_id();
	}

	return apply_filters( 'vgsr_bp_get_root_blog_id', $blog_id );
}

/** Components *************************************************************/

/**
 * Return exclusive BP components
 *
 * @since 0.1.0
 *
 * @return array Exclusive BP components
 */
function vgsr_bp_components() {
	return apply_filters( 'vgsr_bp_components', array(
		'activity',
		'blogs',
		'forums',
		'friends',
		'groups',
		'messages',
		'notifications'
	) );
}

/**
 * Return whether the given component is exclusive for vgsr
 *
 * @since 0.1.0
 *
 * @param string $component Optional. Defaults to the current component
 * @return bool Component is exclusive
 */
function vgsr_bp_is_vgsr_component( $component = '' ) {

	// Default to the current component
	if ( empty( $component ) ) {
		$component = bp_current_component();
	}

	$is = in_array( $component, vgsr_bp_components(), true );

	return $is;
}

/** Member Types ***********************************************************/

/**
 * Return the collection of custom member types
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_get_member_types'
 *
 * @param bool $strict Optional. Whether to return only true VGSR member types. Defaults to False.
 * @return array Member types
 */
function vgsr_bp_get_member_types( $strict = false ) {

	// Define types
	$member_types = array(

		// Lid
		vgsr_bp_lid_member_type() => array(
			'labels' => array(
				'name'          => esc_html__( 'Leden', 'vgsr' ),
				'singular_name' => esc_html__( 'Lid',   'vgsr' ),
				'plural_name'   => esc_html__( 'Leden', 'vgsr' ),
			)
		),

		// Oud-lid
		vgsr_bp_oudlid_member_type() => array(
			'labels' => array(
				'name'          => esc_html__( 'Oud-leden', 'vgsr' ),
				'singular_name' => esc_html__( 'Oud-lid',   'vgsr' ),
				'plural_name'   => esc_html__( 'Oud-leden', 'vgsr' ),
			)
		)
	);

	// When not in strict mode
	if ( ! $strict ) {

		// Ex-lid
		$member_types[ vgsr_bp_exlid_member_type() ] = array(
			'labels' => array(
				'name'          => esc_html__( 'Ex-leden', 'vgsr' ),
				'singular_name' => esc_html__( 'Ex-lid',   'vgsr' ),
				'plural_name'   => esc_html__( 'Ex-leden', 'vgsr' ),
			)
		);
	}

	return (array) apply_filters( 'vgsr_bp_get_member_types', $member_types, $strict );
}

/**
 * Return the member type for Lid
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_lid_member_type'
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
 * @return string Oud-lid member type name
 */
function vgsr_bp_exlid_member_type() {
	return apply_filters( 'vgsr_bp_exlid_member_type', 'exlid' );
}

/**
 * Return whether the member type is a vgsr one
 *
 * @since 0.2.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_is_vgsr_member_type'
 *
 * @param  string $member_type Optional. Member type to check. Defaults to the current directory member type
 * @return bool Is vgsr member type?
 */
function vgsr_bp_is_vgsr_member_type( $member_type = '' ) {

	// Default to current directory member type
	if ( ! $member_type ) {
		$member_type = bp_get_current_member_type();
	}

	$types = vgsr_bp_get_member_types( true );
	$is    = in_array( $member_type, array_keys( $types ) );

	return (bool) apply_filters( 'vgsr_bp_is_vgsr_member_type', $is, $member_type );
}

/**
 * Output the promote to member type url
 *
 * @since 0.1.0
 *
 * @todo Front-end member type promotion buttons are removed.
 *
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
	 * @todo Front-end member type promotion buttons are removed.
	 *
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
		$url  = '';
		$type = bp_get_member_type_object( $member_type );

		// When the member type does exist
		if ( ! empty( $type ) ) {

			// Get the args to add to the URL.
			$args = array(
				'action' => 'vgsr_promote',
				'type'   => $type->name,
				'append' => (int) (bool) $append,
			);

			// Construct action url
			$url = trailingslashit( bp_core_get_user_domain( $user_id ) . 'vgsr_promote' );
			$url = add_query_arg( $args, $url );
			$url = wp_nonce_url( $url, 'vgsr_promote_member_type_' . $type->name );
		}

		return apply_filters( 'vgsr_bp_get_member_type_promote_url', $url, $member_type, $user_id, $append );
	}

/**
 * Display a members query tab for the given member type
 *
 * @since 0.1.0
 *
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
	 * @uses apply_filters() Calls 'vgsr_bp_get_members_member_type_tab'
	 * @param string $member_type Member type
	 */
	function vgsr_bp_get_members_member_type_tab( $member_type ) {

		// Define local variable(s)
		$tab   = '';
		$count = 0;
		$type  = bp_get_member_type_object( $member_type );

		if ( ! empty( $type ) ) {

			// Only display tab when there are members
			if ( $count = vgsr_bp_get_total_member_count( array( 'member_type__in' => $type->name ) ) ) {
				$tab = sprintf( '<li id="members-%s"><a href="%s">%s <span>%s</span></a></li>',
					"vgsr_member_type_{$type->name}", // The scope part (cannot have any dashes in it!)
					esc_url( bp_get_member_type_directory_permalink( $type->name ) ),
					$type->labels['name'],
					$count
				);
			}
		}

		return apply_filters( 'vgsr_bp_get_members_member_type_tab', $tab, $member_type, $count );
	}

/** Members ****************************************************************/

/**
 * Return the actual total member count
 *
 * @since 0.1.0
 *
 * @uses BP_User_Query
 *
 * @param array $args Query args for `BP_User_Query`
 * @return int Total member count
 */
function vgsr_bp_get_total_member_count( $args = array() ) {

	// With args, do custom count query
	if ( ! empty( $args ) ) {
		if ( $query = new BP_User_Query( wp_parse_args( $args, array(
			'type' => '' // Default to just a user query (join with $wpdb->users, no last-active etc.)
		) ) ) ) {
			$count = $query->total_users;
		}

	// Return the *full* total member count
	} else {
		$count = bp_core_get_total_member_count();
	}

	return $count;
}

/**
 * Return the total vgsr member count
 *
 * @since 0.2.0
 *
 * @return int Total vgsr member count
 */
function vgsr_bp_get_total_vgsr_member_count() {
	return vgsr_bp_get_total_member_count( array(
		'member_type__in' => array_keys( vgsr_bp_get_member_types( true ) )
	) );
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
