<?php

/**
 * VGSR User Functions
 *
 * @package VGSR
 * @subpackage User
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the current user ID even when it isn't set yet
 *
 * @since 0.1.0
 *
 * @uses did_action()
 * @uses get_current_user_id()
 * @uses apply_filters() Calls 'determine_current_user'
 * 
 * @return int User ID
 */
function vgsr_get_current_user_id() {
	return did_action( 'set_current_user' ) ? get_current_user_id() : apply_filters( 'determine_current_user', 0 );
}

/** Is Functions **********************************************************/

/**
 * Return whether a given user is in any VGSR group
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'is_user_vgsr'
 * 
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is VGSR
 */
function is_user_vgsr( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) ) {
		$user_id = vgsr_get_current_user_id();
	}

	return (bool) apply_filters( 'is_user_vgsr', false, $user_id );
}

/**
 * Return whether a given user is in the leden group
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'is_user_lid'
 * 
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is lid
 */
function is_user_lid( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) ) {
		$user_id = vgsr_get_current_user_id();
	}

	return (bool) apply_filters( 'is_user_lid', false, $user_id );
}

/**
 * Return whether a given user is in the oud-leden group
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'is_user_oudlid'
 * 
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is oud-lid
 */
function is_user_oudlid( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) ) {
		$user_id = vgsr_get_current_user_id();
	}

	return (bool) apply_filters( 'is_user_oudlid', false, $user_id );
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
