<?php

/**
 * VGSR User Functions
 *
 * @package VGSR
 * @subpackage User
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Groups ****************************************************************/

/**
 * Return the vgsr group ID
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_get_group_vgsr_id'
 * @return int VGSR group ID
 */
function vgsr_get_group_vgsr_id() {
	return (int) apply_filters( 'vgsr_get_group_vgsr_id', 0 );
}

/**
 * Return the leden group ID
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_get_group_leden_id'
 * @return int Leden group ID
 */
function vgsr_get_group_leden_id() {
	return (int) apply_filters( 'vgsr_get_group_leden_id', 0 );
}

/**
 * Return the oud-leden group ID
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_get_group_oudleden_id'
 * @return int Oud-leden group ID
 */
function vgsr_get_group_oudleden_id() {
	return (int) apply_filters( 'vgsr_get_group_oudleden_id', 0 );
}

/**
 * Return IDs of all VGSR groups
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_get_vgsr_groups'
 * @return array VGSR groups IDs
 */
function vgsr_get_vgsr_groups() {
	return array_map( 'intval', (array) apply_filters( 'vgsr_get_vgsr_groups', array(
		vgsr_get_group_vgsr_id(),
		vgsr_get_group_leden_id(),
		vgsr_get_group_oudleden_id(),
	) ) );
}

/** Is Functions **********************************************************/

/**
 * Return whether a given user is in any VGSR group
 *
 * @since 0.0.1
 *
 * @uses vgsr_user_in_group()
 * @uses vgsr_get_vgsr_groups()
 * @uses apply_filters() Calls 'is_user_vgsr'
 * 
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is VGSR
 */
function is_user_vgsr( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	return (bool) apply_filters( 'is_user_vgsr', vgsr_user_in_group( vgsr_get_vgsr_groups(), $user_id ), $user_id );
}

/**
 * Return whether a given user is in the leden group
 *
 * @since 0.0.1
 *
 * @uses vgsr_user_in_group()
 * @uses vgsr_get_group_leden_id()
 * @uses apply_filters() Calls 'is_user_lid'
 * 
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is lid
 */
function is_user_lid( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	return (bool) apply_filters( 'is_user_lid', vgsr_user_in_group( vgsr_get_group_leden_id(), $user_id ), $user_id );
}

/**
 * Return whether a given user is in the oud-leden group
 *
 * @since 0.0.1
 *
 * @uses vgsr_user_in_group()
 * @uses vgsr_get_group_oudleden_id()
 * @uses apply_filters() Calls 'is_user_oudlid'
 * 
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is oud-lid
 */
function is_user_oudlid( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	return (bool) apply_filters( 'is_user_oudlid', vgsr_user_in_group( vgsr_get_group_oudleden_id(), $user_id ), $user_id );
}

/**
 * Abstraction function to check user group membership
 *
 * Group plugins hook in here to verify group membership of the given user. 
 * When no external filters hook in, this function assumes no membership.
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_user_in_group'
 *
 * @param int|array $group_id Group ID or ids
 * @param int $user_id User ID. Defaults to current user
 * @return bool User is in group
 */
function vgsr_user_in_group( $group_id = 0, $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	return (bool) apply_filters( 'vgsr_user_in_group', false, $group_id, $user_id );
}

/**
 * Return whether the given group is a VGSR group
 *
 * @since 0.0.3
 *
 * @uses vgsr_get_vgsr_groups()
 * @uses apply_filters() Calls 'vgsr_is_vgsr_group'
 * 
 * @param int $group_id Group ID
 * @return bool Group is VGSR group
 */
function vgsr_is_vgsr_group( $group_id = 0 ) {

	// Bail when group was not provided
	if ( empty( $group_id ) )
		return false;

	return (bool) apply_filters( 'vgsr_is_vgsr_group', in_array( (int) $group_id, vgsr_get_vgsr_groups() ), $group_id );
}

/** Admin Bar *************************************************************/

/**
 * Hook various filters to modify the admin bar
 *
 * @since 0.1.0
 *
 * @uses add_action()
 * @uses is_multisite()
 */
function vgsr_admin_bar_menu() {

	// Modify WP Logo menu
	add_action( 'admin_bar_menu', 'vgsr_admin_bar_wp_menu', 10 );

	// Network context specifically
	if ( is_multisite() ) {

		// Modify My Sites nodes
		add_action( 'admin_bar_menu', 'vgsr_admin_bar_my_sites_menu', 20 );
	}
}

/**
 * Modify the WP Logo admin bar menu
 *
 * @since 0.1.0
 *
 * @see wp_admin_bar_wp_menu()
 * 
 * @uses current_user_can()
 * @uses WP_Admin_Bar::remove_node()
 * 
 * @param WP_Admin_Bar $wp_admin_bar
 */
function vgsr_admin_bar_wp_menu( $wp_admin_bar ) {

	// Hide WP menu for non-admins
	if ( ! current_user_can( 'manage_options' ) ) {
		$wp_admin_bar->remove_node( 'wp-logo' );
	}

	// @todo Add WP-like VGSR menu
}

/**
 * Modify the My Sites admin bar menu
 *
 * @since 0.1.0
 *
 * @see wp_admin_bar_my_sites_menu()
 *
 * @uses switch_to_blog()
 * @uses WP_Admin_Bar::get_node()
 * @uses home_url()
 * @uses WP_Admin_Bar::add_node()
 * @uses restore_current_blog()
 * 
 * @param WP_Admin_Bar $wp_admin_bar
 */
function vgsr_admin_bar_my_sites_menu( $wp_admin_bar ) {

	// Walk user blogs under My Sites
	foreach ( $wp_admin_bar->user->blogs as $blog ) {
		switch_to_blog( $blog->userblog_id );

		// Node exists
		if ( $node = $wp_admin_bar->get_node( 'blog-' . $blog->userblog_id ) ) {

			// Remove the site's wp-logo icon
			$node->title = str_replace( '<div class="blavatar"></div>', '', $node->title );

			// Change node link to the site's front page instead of its admin page
			$node->href = home_url( '/' );

			// Overwrite node
			$wp_admin_bar->add_node( $node );
		}

		restore_current_blog();
	}
}
