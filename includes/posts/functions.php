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
 * VGSR-only: Post access
 *
 * This is a simple in-or-out structure, which is based on
 * user group membership within the VGSR groups. Posts are
 * marked 'vgsr-only' which makes the post only accessible
 * for the mentioned users. Ultimately this system divides
 * site users in two camps, so nothing fancy besides that.
 */

/** VGSR-only: Checks ***********************************************************/

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

/**
 * Return whether the given post type allows for vgsr-only marking
 *
 * @since 0.0.7
 *
 * @param string|int $post_type Post type name or post ID
 * @return bool Post type allows for vgsr-only marking
 */
function is_vgsr_only_post_type( $post_type = '' ) {

	// Post ID was provided
	if ( is_numeric( $post_type ) ) {
		$post_type = get_post_type( $post_type );

	// Default to global post type
	} elseif ( empty( $post_type ) ) {
		global $post;
		if ( ! isset( $post ) ) {
			return false;
		} else {
			$post_type = $post->post_type;
		}
	}

	// Post type can be marked vgsr-only
	$retval = in_array( $post_type, vgsr_only_post_types() );

	return $retval;
}

/**
 * Return the post types that allow for vgsr-only marking
 *
 * Defaults to all public registered post types.
 *
 * @since 0.0.7
 *
 * @uses apply_filters() Calls 'vgsr_only_post_types'
 * @return array Post types
 */
function vgsr_only_post_types() {
	return apply_filters( 'vgsr_only_post_types', get_post_types( array( 'public' => true ) ) );
}

/** VGSR-only: Query Filters ****************************************************/

/**
 * Manipulate query clauses for WP_Query to exclude posts
 * that are marked as VGSR-only for non-VGSR users
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_only_posts'
 * @param WP_Query|array $query Query (vars)
 * @return int VGSR group ID
 */
function _vgsr_only_post_query( $query ) {

	// Bail if current user _is_ VGSR
	if ( is_user_vgsr() )
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

	if ( ( $post__not_in = _vgsr_only_get_post_hierarchy() ) && ! empty( $post__not_in ) ) {
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
	if ( is_user_vgsr() )
		return $nav_menu_items;

	// Do stuff...
	foreach ( $nav_menu_items as $k => $item ) {

		// Remove vgsr-only nav menu post items
		if ( 'post_type' == $item->type && vgsr_is_post_vgsr_only( $item->object_id, true ) )
			unset( $nav_menu_items[$k] );
	}

	return apply_filters( 'vgsr_only_nav_menu_objects', $nav_menu_items, $args );
}

/**
 * Filter pages marked as VGSR-only for non-VGSR users
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_only_get_pages'
 * @param array $pages Pages
 * @param array $args Query arguments
 * @return array Pages
 */
function _vgsr_only_get_pages( $pages, $args ) {

	// Bail if current user _is_ VGSR
	if ( is_user_vgsr() )
		return $pages;

	// Do stuff...
	foreach ( $pages as $k => $page ) {

		// Remove vgsr-only pages
		if ( vgsr_is_post_vgsr_only( $page->ID, true ) )
			unset( $pages[$k] );
	}

	return apply_filters( 'vgsr_only_get_pages', $pages, $args );
}

/**
 * Mark a single page as VGSR-only for VGSR users in page list
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_only_list_pages'
 * @param string $title Page title
 * @param WP_Post $args Page object
 * @return string Page title
 */
function _vgsr_only_list_pages( $title, $page ) {

	// Bail if current user _is_ not VGSR
	if ( ! is_user_vgsr() )
		return $title;

	// Mark vgsr-only pages
	if ( vgsr_is_post_vgsr_only( $page->ID, true ) )
		$title .= '*'; // Make marking optional?

	return apply_filters( 'vgsr_only_list_pages', $title, $page );
}

/**
 * Manipulate WHERE clause for {@link get_adjacent_post()}
 * to exclude VGSR-only posts for non-VGSR users
 *
 * @since 0.0.6
 *
 * @uses _vgsr_only_get_post_hierarchy()
 * @param string $where Where clause
 * @return string Where clause
 */
function _vgsr_only_get_adjacent_post( $where ) {

	// Bail if current user _is_ VGSR
	if ( is_user_vgsr() )
		return $where;

	// Exclude posts
	if ( ( $post__not_in = _vgsr_only_get_post_hierarchy() ) && ! empty( $post__not_in ) ) {
		$where .= sprintf( ' AND p.ID NOT IN (%s)', implode( ',', $post__not_in ) );
	}

	return $where;
}

/**
 * Manipulate WHERE clause for {@link wp_get_archives()}
 * to exclude VGSR-only posts for non-VGSR users
 *
 * @since 0.0.6
 *
 * @uses _vgsr_only_get_post_hierarchy()
 * @param string $where Where clause
 * @param array $args Query args
 * @return string Where clause
 */
function _vgsr_only_get_archives( $where, $args = array() ) {
	global $wpdb;

	// Bail if current user _is_ VGSR
	if ( is_user_vgsr() )
		return $where;

	// Exclude posts
	if ( ( $post__not_in = _vgsr_only_get_post_hierarchy() ) && ! empty( $post__not_in ) ) {
		$where .= sprintf( " AND {$wpdb->posts}.ID NOT IN (%s)", implode( ',', $post__not_in ) );
	}

	return $where;
}

/**
 * Manipulate query clauses for WP_Query (comment feed) or
 * WP_Comment_Query to exclude comments of VGSR-only posts
 * for non-VGSR users
 *
 * @since 0.0.6
 *
 * @param string|array $clause Comment WHERE query clause or all query clauses
 * @param WP_QueryWP_Comment_Query $query The query object
 * @return string|array Clauses
 */
function _vgsr_only_comment_query( $clause, $query ) {

	// Bail if current user _is_ VGSR
	if ( is_user_vgsr() || ( is_a( $query, 'WP_Query' ) && $query->is_singular ) )
		return $clause;

	// Exclude posts
	if ( ( $post__not_in = _vgsr_only_get_post_hierarchy() ) && ! empty( $post__not_in ) ) {

		// Logic to work with collection of clauses
		if ( is_array( $clause ) ) {
			$clauses = $clause;
			$clause  = $clauses['where'];
		}

		$clause .= sprintf( ' AND comment_post_ID NOT IN (%s)', implode( ',', $post__not_in ) );

		// Reset logic
		if ( isset( $clauses ) ) {
			$clauses['where'] = $clause;
			$clause = $clauses;
		}
	}

	return $clause;
}

//
// wp-includes/comment.php
// - get_comment_count() - unfilterable
// - wp_count_comments() - unfilterable
//
// wp-includes/general-template.php
// - get_calendar()      - unfilterable
//
// wp-includes/post.php
// - wp_count_posts()       - no query filter
// - wp_count_attachments() - no query filter
//

/** VGSR-only: Hierarchy ********************************************************/

/**
 * Update the post hierarchy option
 *
 * A single option holds the full vgsr-only post hierarchy
 * which is used for excluding posts in WP_Query and other
 * situations. This function creates that array of posts.
 *
 * This handles both marked posts as well as posts that are
 * added or removed as child of marked parent posts.
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
	$post_types = vgsr_only_post_types();
	$only_posts = get_posts( array(
		'numberposts' => -1,
		'post_type'   => $post_types,
		'post_status' => 'any',
		'fields'      => 'ids',
		'meta_key'    => '_vgsr_post_vgsr_only',
		'meta_value'  => 1,
	) );

	// Process a single post
	if ( ! empty( $post_id ) ) {

		// According to current vgsr-only ancestry
		// @todo Sort this out: how to explicitly mark children of marked posts?
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
			'post_type'    => $post_types,
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

	// Manipulate global
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
