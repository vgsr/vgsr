<?php

/**
 * VGSR Post Functions
 *
 * @package VGSR
 * @subpackage Post
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Groups **********************************************************************/

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
