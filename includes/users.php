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
 * @uses apply_filters() Calls 'determine_current_user'
 * @return int User ID
 */
function vgsr_get_current_user_id() {
	return did_action( 'set_current_user' ) ? get_current_user_id() : apply_filters( 'determine_current_user', 0 );
}

/**
 * Return a list of vgsr users matching criteria
 * 
 * @since 0.1.0
 *
 * @param array $args Optional. Arguments for use in `WP_User_Query`.
 * @return array Users
 */
function vgsr_get_users( $args = array() ) {

	// Define query arguments. Default to all vgsr users.
	$args = wp_parse_args( $args, array( 'vgsr' => true ) );
	$args['vgsr'] = in_array( $args['vgsr'], array( 'lid', 'oud-lid' ) ) ? $args['vgsr'] : true;

	// Query the users
	return get_users( $args );
}

/**
 * Create dropdown HTML element of vgsr users
 *
 * @since 0.1.0
 *
 * @param array $args Optional. Arguments for `wp_dropdown_users()`.
 * @return string Dropdown when 'echo' argument is false.
 */
function vgsr_dropdown_users( $args = array() ) {

	// Define user query arguments. Default to all vgsr users.
	$args = wp_parse_args( $args, array( 'vgsr' => true ) );
	$args['vgsr'] = in_array( $args['vgsr'], array( 'lid', 'oud-lid' ) ) ? $args['vgsr'] : true;

	// Get the dropdown
	return wp_dropdown_users( $args );
}

/**
 * Modify the query arguments for the users dropdown
 *
 * @since 0.1.0
 *
 * @param array $query_args Query arguments
 * @param array $args Dropdown arguments
 * @return array Query arguments
 */
function vgsr_dropdown_users_args( $query_args = array(), $args = array() ) {

	// Add 'vgsr' argument to query args
	if ( isset( $args['vgsr'] ) && $args['vgsr'] ) {
		$query_args['vgsr'] = in_array( $args['vgsr'], array( 'lid', 'oud-lid' ) ) ? $args['vgsr'] : true;
	}

	return $query_args;
}

/**
 * Modify the user query when querying vgsr users
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_pre_user_query'
 * @param WP_User_Query $query
 */
function vgsr_pre_user_query( $query ) {

	// Bail when not querying vgsr users
	if ( ! $query->get( 'vgsr' ) )
		return;

	// Enable plugin filtering
	$sql_clauses = array( 'join' => '', 'where' => '' );
	$sql_clauses = apply_filters( 'vgsr_pre_user_query', $sql_clauses, $query );

	// Append JOIN statement
	if ( ! empty( $sql_clauses['join'] ) ) {
		$join = preg_replace( '/^\s*/', '', $sql_clauses['where'] );
		$query->query_join .= " $join";
	}

	// Append WHERE statement
	if ( ! empty( $sql_clauses['where'] ) ) {
		$where = preg_replace( '/^\s*AND\s*/', '', $sql_clauses['where'] );
		$query->query_where .= " AND $where";
	}
}

/** Is Functions **********************************************************/

/**
 * Return whether a given user is marked as VGSR
 *
 * Plugins hook in the provided filter to determine whether the
 * given user is indeed so. The function assumes not by default.
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
 * Return whether a given user is marked as Lid
 *
 * Plugins hook in the provided filter to determine whether the
 * given user is indeed so. The function assumes not by default.
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'is_user_lid'
 * 
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is Lid
 */
function is_user_lid( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) ) {
		$user_id = vgsr_get_current_user_id();
	}

	return (bool) apply_filters( 'is_user_lid', false, $user_id );
}

/**
 * Return whether a given user is marked as Oud-lid
 *
 * Plugins hook in the provided filter to determine whether the
 * given user is indeed so. The function assumes not by default.
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'is_user_oudlid'
 * 
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is Oud-lid
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
 * Modify the admin bar, after full setup
 *
 * @since 0.1.0
 */
function vgsr_admin_bar_menus() {

	// Modify WP Logo menu
	add_action( 'admin_bar_menu', 'vgsr_admin_bar_wp_menu', 10 );

	// Modify My Sites nodes
	add_action( 'admin_bar_menu', 'vgsr_admin_bar_my_sites_menu', 20 );
}

/**
 * Modify the WP Logo admin bar menu
 *
 * @see wp_admin_bar_wp_menu()
 *
 * @since 0.1.0
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
 * @see wp_admin_bar_my_sites_menu()
 *
 * @since 0.1.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function vgsr_admin_bar_my_sites_menu( $wp_admin_bar ) {

	// Bail when not in Multisite
	if ( ! is_multisite() )
		return;

	// Walk user blogs under My Sites
	foreach ( $wp_admin_bar->user->blogs as $blog ) {
		switch_to_blog( $blog->userblog_id );

		// Node exists
		if ( $node = $wp_admin_bar->get_node( 'blog-' . $blog->userblog_id ) ) {

			// Remove the logo icon, and link to site front page
			$node->title = str_replace( '<div class="blavatar"></div>', '', $node->title );
			$node->href  = home_url( '/' );

			// Overwrite node
			$wp_admin_bar->add_node( $node );
		}

		restore_current_blog();
	}
}
