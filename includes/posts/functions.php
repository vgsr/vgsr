<?php

/**
 * VGSR Post Functions
 *
 * @package VGSR
 * @subpackage Post
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Vgsr-only: Post access
 *
 * This is a simple in-or-out structure, which is based on
 * user group membership within the VGSR groups. Posts are
 * marked 'vgsr-only' which makes the post only accessible
 * for the mentioned users. Ultimately this system divides
 * site users in two camps, so nothing fancy besides that.
 */

/** Vgsr-only: Checks ***********************************************************/

/**
 * Return whether the given post is marked vgsr-only
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_is_post_vgsr_only'
 * @param int $post_id Optional. Post ID. Defaults to global post
 * @param bool $check_ancestors Optional. Whether to walk post hierarchy
 * @return bool Post is marked vgsr-only
 */
function vgsr_is_post_vgsr_only( $post_id = 0, $check_ancestors = false ) {

	// Default to global post
	if ( empty( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}

	// Get post meta
	$is = (bool) get_post_meta( $post_id, '_vgsr_post_vgsr_only', true );

	// Check ancestors
	if ( ! $is && $check_ancestors ) {
		$is = vgsr_only_check_post_ancestors( $post_id );
	}

	return (bool) apply_filters( 'vgsr_is_post_vgsr_only', $is, $post_id, $check_ancestors );
}

/**
 * Walk the post hierarchy for a vgsr-only marked parent
 *
 * @since 0.0.6
 * @see get_post_ancestors()
 *
 * @uses vgsr_is_post_vgsr_only()
 * @param bool $only Post is marked vgsr-only
 * @param int $post_id Post ID
 * @return bool Post is marekd vgsr-only
 */
function vgsr_only_check_post_ancestors( $post_id = 0 ) {

	// Get post object. Default to global post
	if ( ! empty( $post_id ) ) {
		$post = get_post( $post_id );
	} else {
		global $post;
	}

	// Assume no marking
	$only = false;

	// Only when post has hierarchy
	if ( ! empty( $post->post_parent ) && $post->post_parent != $post->ID ) {
		$ancestors   = array();
		$ancestors[] = $id = $post->post_parent;

		while ( $ancestor = get_post( $id ) ) {
			// Find marking: If the ancestor is marked, break.
			if ( $only = vgsr_is_post_vgsr_only( $ancestor->ID ) )
				break;

			// Loop detection: If the ancestor has been seen before, break.
			if ( empty( $ancestor->post_parent ) || ( $ancestor->post_parent == $post->ID ) || in_array( $ancestor->post_parent, $ancestors ) )
				break;

			$id = $ancestors[] = $ancestor->post_parent;
		}
	}

	return $only;
}

/** Vgsr-only: Query Filters ****************************************************/

/**
 * Filter posts in the query that are marked as VGSR-only for non-VGSR users
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_only_posts'
 * @param WP_Query|array $query Query (vars)
 * @return int VGSR group ID
 */
function _vgsr_only_post_query( $query ) {

	// Bail if current user _is_ VGSR
	if ( user_is_vgsr() )
		return $query;

	// Logic to work with 'pre_get_posts' filter
	if ( 'pre_get_posts' === current_filter() ) {

		// Bail if editing main query, since that was done through
		// the 'vgsr_request' filter
		if ( $query->is_main_query() )
			return;

		// Setup query vars
		$query = &$query->query_vars;
	}

	// Setup meta query
	$meta_query = isset( $query['meta_query'] ) ? $query['meta_query'] : array();

	// Handle post mark
	$meta_query[] = array(
		'key'     => '_vgsr_post_vgsr_only',
		'compare' => 'NOT EXISTS', // Empty values are deleted, so only selected ones exist
	);

	// Set meta query
	$query['meta_query'] = $meta_query;

	//
	// Post Hierarchy
	// 
	
	if ( $post__not_in = _vgsr_only_get_post_hierarchy() ) {
		if ( isset( $query['post__not_in'] ) ) 
			$post__not_in = array_merge( (array) $query['post__not_in'], $post__not_in );
		
		$query['post__not_in'] = $post__not_in;
	}

	return apply_filters( 'vgsr_only_posts', $query );
}

/**
 * Filter nav menu items marked as VGSR-only for non-VGSR users
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_only_nav_menu_objects'
 * @param array $nav_menu_items Nav menu items
 * @param array $args Query arguments
 * @return array Nav menu items
 */
function _vgsr_only_nav_menu_objects( $nav_menu_items, $args ) {

	// Bail if current user _is_ VGSR
	if ( user_is_vgsr() )
		return $nav_menu_items;

	// Do stuff...
	foreach ( $nav_menu_items as $k => $item ) {

		// Remove vgsr-only nav menu post items
		if ( 'post_type' == $item->type && vgsr_is_post_vgsr_only( $item->object_id, true ) )
			unset( $nav_menu_items[$k] );
	}

	return apply_filters( 'vgsr_only_nav_menu_objects', $nav_menu_items, $args );
}

// filter wp_count_posts()
// filter get_pages()
// filter wp_get_adjacent_post() - get_next_post_where, get_previous_post_where
// filter get_comments() - comments_clauses, comment_feed_where
// 

/** Vgsr-only: Hierarchy ********************************************************/

/**
 * Update the post hierarchy option
 *
 * A single option holds the full vgsr-only post hierarchy
 * which is used for excluding posts in WP_Query and other
 * situations. This function creates that array of posts.
 *
 * @since 0.0.6
 *
 * @uses remove_filter()
 * @uses add_filter()
 * @uses apply_filters() Calls 'vgsr_only_post_types'
 * @uses get_post_types()
 * @uses get_posts()
 * @uses vgsr_is_post_vgsr_only()
 * @uses get_children()
 * @uses get_option()
 * @uses update_option()
 * 
 * @param int $post_id Post ID
 * @param bool $rebuild Optional. Whether to fully rebuild the post hierarchy
 */
function _vgsr_only_update_post_hierarchy( $post_id = 0, $rebuild = false ) {

	// Bail if no post is provided, while not resetting and already collected
	if ( empty( $post_id ) && ( ! $rebuild || get_option( '_vgsr_only_post_hierarchy' ) ) )
		return;

	// Un-hook query filter
	remove_filter( 'pre_get_posts', '_vgsr_only_post_query' );

	// Define vgsr-only post types
	$only_post_types = apply_filters( 'vgsr_only_post_types', get_post_types( array( 'public' => true ) ) );
	$only_posts      = get_posts( array( 
		'numberposts' => -1,
		'post_type'   => $only_post_types,
		'post_status' => 'any',
		'fields'      => 'ids',
		'meta_key'    => '_vgsr_post_vgsr_only', 
		'meta_value'  => 1,
	) );

	// Process a single post
	if ( ! empty( $post_id ) ) {

		// According to current vgsr-only ancestry
		$add   = vgsr_is_post_vgsr_only( $post_id, true );
		$posts = array( $post_id );

	// Do full rebuild if explicitly requested. NOTE: this may take a while.
	} elseif ( $rebuild ) {
		$add   = null;
		$posts = $only_posts;

	// Nothing to do here
	} else {
		return;
	}

	$_posts = new ArrayIterator( $posts );
	foreach ( $_posts as $post_id ) {
		if ( $children = get_children( array(
			'post_type'    => $only_post_types,
			'post_parent'  => $post_id,
			'fields'       => 'ids',
			'post__not_in' => $only_posts, // Exclude marked posts and their children
		) ) ) {
			foreach ( $children as $child_id ) {
				$_posts->append( (int) $child_id );
			}
		}
	}

	// Get the post hierarchy
	$hierarchy = _vgsr_only_get_post_hierarchy();
	$collected = $_posts->getArrayCopy();

	// Update global
	if ( null !== $add ) {

		// Add to hierarchy
		if ( $add ) {
			$hierarchy = array_unique( array_merge( $hierarchy, $collected ) );

		// Remove from hierarchy
		} else {
			$hierarchy = array_diff( $hierarchy, $collected );
		}
	}

	// Update option
	update_option( '_vgsr_only_post_hierarchy', $hierarchy );

	// Re-hook query filter
	add_filter( 'pre_get_posts', '_vgsr_only_post_query' );
}

/**
 * Return the array of the entire vgsr-only post hierarchy
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls '_vgsr_only_get_post_hierarchy'
 * @return array Post ids in vgsr-only post hierarchy
 */
function _vgsr_only_get_post_hierarchy() {
	return (array) apply_filters( 'vgsr_only_get_post_hierarchy', get_option( '_vgsr_only_post_hierarchy' ) );
}
