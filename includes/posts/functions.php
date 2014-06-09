<?php

/**
 * VGSR Post Functions
 *
 * @package VGSR
 * @subpackage Post
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** VGSR Only *******************************************************************/

/**
 * Return whether the given post is marked vgsr-only
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_is_post_vgsr_only'
 * @param int $post_id Optional. Post ID. Defaults to global post
 * @return bool Post is marked vgsr-only
 */
function vgsr_is_post_vgsr_only( $post_id = 0 ) {

	// Default to global post
	if ( empty( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}

	// Get post meta
	$is = (bool) get_post_meta( $post_id, '_vgsr_post_vgsr_only', true );

	return (bool) apply_filters( 'vgsr_is_post_vgsr_only', $is, $post_id );
}

/**
 * Filter posts marked as VGSR-only for non-VGSR users
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_get_group_vgsr_id'
 * @return int VGSR group ID
 */
function vgsr_filter_vgsr_posts( $query_vars ) {

	// Bail if current user _is_ VGSR
	if ( user_is_vgsr() )
		return $query_vars;

	// Setup meta query
	$meta_query = isset( $query_vars['meta_query'] ) ? $query_vars['meta_query'] : array();

	// Handle post mark
	$meta_query[] = array(
		'key'     => '_vgsr_post_vgsr_only',
		'compare' => 'NOT EXISTS', // Empty values are deleted, so only selected ones exist
	);

	// Set meta query
	$query_vars['meta_query'] = $meta_query;

	return apply_filters( 'vgsr_filter_vgsr_posts', $query_vars );
}

/**
 * Filter nav menu items marked as VGSR-only for non-VGSR users
 *
 * @since 0.0.6
 *
 * @param array $nav_menu_items Nav menu items
 * @param array $args Query arguments
 * @return array Nav menu items
 */
function vgsr_filter_vgsr_nav_menu_objects( $nav_menu_items, $args ) {

	// Bail if current user _is_ VGSR
	if ( user_is_vgsr() )
		return $nav_menu_items;

	// Do stuff...
	foreach ( $nav_menu_items as $k => $item ) {

		// Remove vgsr-only object
		if ( isset( $item->object_id ) && vgsr_is_post_vgsr_only( $item->object_id ) )
			unset( $nav_menu_items[$k] );
	}

	return apply_filters( 'vgsr_filter_vgsr_nav_menu_objects', $nav_menu_items, $args );
}
