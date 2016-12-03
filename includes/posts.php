<?php

/**
 * VGSR Post Functions
 *
 * @package VGSR
 * @subpackage Posts
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Exclusivity: Post access
 *
 * This is a simple in-or-out structure, which is based on VGSR membership,
 * determined trough {@see is_user_vgsr()}. Posts are marked 'vgsr' which
 * makes the post only accessible for the mentioned users.
 */

/** Exclusivity: Checks *********************************************************/

/**
 * Return whether the given post is exclusive
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_is_post_vgsr'
 * @param int $post_id Optional. Post ID. Defaults to global post
 * @param bool $check_ancestors Optional. Whether to walk post hierarchy
 * @return bool Post is exclusive
 */
function vgsr_is_post_vgsr( $post_id = 0, $check_ancestors = false ) {

	// Bail when post is invalid
	if ( ! $post = get_post( $post_id ) )
		return false;

	// Bail when this post cannot be exclusive
	if ( ! is_vgsr_post_type( $post->post_type ) )
		return false;

	// Get post meta
	$is = (bool) get_post_meta( $post->ID, '_vgsr_post_vgsr_only', true );

	// Check ancestors
	if ( ! $is && $check_ancestors ) {
		$is = vgsr_post_check_ancestors( $post->ID );
	}

	return (bool) apply_filters( 'vgsr_is_post_vgsr', $is, $post->ID, $check_ancestors );
}

/**
 * Walk the post hierarchy upwards to find an exclusive parent
 *
 * @since 0.0.6
 *
 * @see get_post_ancestors()
 *
 * @uses vgsr_is_post_vgsr()
 *
 * @param int $post_id Post ID
 * @return bool A parent post is exclusive
 */
function vgsr_post_check_ancestors( $post_id = 0 ) {

	// Bail when post is invalid
	if ( ! $post = get_post( $post_id ) )
		return false;

	// Define local variable
	$excl = false;

	// Only when post has hierarchy
	if ( ! empty( $post->post_parent ) && $post->post_parent != $post->ID ) {
		$ancestors   = array();
		$ancestors[] = $id = $post->post_parent;

		while ( $ancestor = get_post( $id ) ) {

			// Break when the parent is exclusive
			if ( $excl = vgsr_is_post_vgsr( $ancestor->ID ) )
				break;

			// Loop detection: If the ancestor has been seen before, break.
			if ( empty( $ancestor->post_parent ) || ( $ancestor->post_parent == $post->ID ) || in_array( $ancestor->post_parent, $ancestors ) )
				break;

			$id = $ancestors[] = $ancestor->post_parent;
		}
	}

	return $excl;
}

/**
 * Return whether posts of the given post type can be made exclusive
 *
 * @since 0.0.7
 *
 * @uses vgsr_post_types()
 *
 * @param string|WP_Post|int $post_type Post type name or Post object or ID
 * @return bool Post type can be made exclusive
 */
function is_vgsr_post_type( $post_type = '' ) {

	// Default to the post's post type
	if ( ! post_type_exists( $post_type ) ) {
		if ( $post = get_post( $post_type ) ) {
			$post_type = $post->post_type;
		} else {
			return false;
		}
	}

	// Posts can be exclusive
	$retval = in_array( $post_type, vgsr_post_types() );

	return $retval;
}

/**
 * Return the post types that can be made exclusive
 *
 * Defaults to all public registered post types. Post types that handle
 * exclusivity themselves should register their with 'vgsr' => true:
 *
 * When the current user is vgsr, the post type might be public, but the
 * post type is still exclusive in its own right. These post types do not
 * need any additional vgsr treatment, so leave them out of this collection.
 *
 * @since 0.0.7
 * @since 0.1.0 Applied custom WP_List_Util implementation, and added
 *              the 'vgsr' post type parameter check.
 *
 * @uses apply_filters() Calls 'vgsr_post_types'
 * @return array Post types
 */
function vgsr_post_types() {
	global $wp_post_types;

	// Since WP 4.7
	if ( class_exists( 'WP_List_Util' ) ) {
		$util = new WP_List_Util( $wp_post_types );

		// Only public post types, but leave out vgsr post types
		$util->filter( array( 'public' => true )        );
		$util->filter( array( 'vgsr'   => true ), 'NOT' );

		$util->pluck( 'name' );

		$post_types = $util->get_output();

	// Pre-WP 4.7
	} else {
		$public_types = get_post_types( array( 'public' => true ) );
		$vgsr_types   = get_post_types( array( 'vgsr'   => true ) );

		$post_types = array_diff( $public_types, $vgsr_types );
	}

	return apply_filters( 'vgsr_post_types', $post_types );
}

/** Exclusivity: Query Filters **************************************************/

/**
 * Manipulate query clauses for WP_Query to exclude posts
 * that are exclusive, for non-VGSR users
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_post_query'
 * @param WP_Query|array $query Query (vars)
 */
function _vgsr_post_query( $query ) {

	// Bail when current user is VGSR
	if ( is_user_vgsr() )
		return $query;

	// Logic to work with 'pre_get_posts' filter
	if ( doing_filter( 'pre_get_posts' ) ) {

		// Bail when editing main query, since that was done through
		// the 'request' filter
		if ( $query->is_main_query() )
			return;

		// Bail when suppressing filters
		if ( true === $query->get( 'suppress_filters' ) )
			return $query;

		// Setup query vars
		$query = &$query->query_vars;

	// Logic to work with 'request' filter
	} elseif ( doing_filter( 'request' ) ) {

		// Bail when no specific page was queried, being the front page. Continue for blog posts on front
		if ( empty( $query ) && 'page' == get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) {
			return $query;
		}
	}

	// Setup meta query
	$meta_query   = isset( $query['meta_query'] ) ? $query['meta_query'] : array();
	$meta_query[] = array(
		'key'     => '_vgsr_post_vgsr_only',
		'compare' => 'NOT EXISTS', // Empty values are deleted, so only selected ones exist
	);
	$query['meta_query'] = $meta_query;

	//
	// Post Hierarchy
	//

	if ( ( $post__not_in = _vgsr_post_get_hierarchy() ) && ! empty( array_filter( $post__not_in ) ) ) {
		if ( isset( $query['post__not_in'] ) ) {
			$post__not_in = array_merge( (array) $query['post__not_in'], $post__not_in );
		}

		$query['post__not_in'] = array_unique( array_filter( $post__not_in ) );
	}

	return apply_filters( 'vgsr_post_query', $query );
}

/**
 * Filter nav menu items exclusive, for non-VGSR users
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_post_nav_menu_objects'
 *
 * @param array $nav_menu_items Nav menu items
 * @param array $args Query arguments
 * @return array Nav menu items
 */
function _vgsr_post_nav_menu_objects( $nav_menu_items, $args ) {

	// Bail when current user _is_ VGSR
	if ( is_user_vgsr() )
		return $nav_menu_items;

	// Walk this menu's items
	foreach ( $nav_menu_items as $k => $item ) {

		// Remove exclusive nav menu post type items
		if ( 'post_type' === $item->type && vgsr_is_post_vgsr( $item->object_id, true ) ) {
			unset( $nav_menu_items[$k] );
		}
	}

	return apply_filters( 'vgsr_post_nav_menu_objects', $nav_menu_items, $args );
}

/**
 * Filter pages exclusive, for non-VGSR users
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_post_get_pages'
 *
 * @param array $pages Pages
 * @param array $args Query arguments
 * @return array Pages
 */
function _vgsr_post_get_pages( $pages, $args ) {

	// Bail when current user _is_ VGSR
	if ( is_user_vgsr() )
		return $pages;

	// Walk pages
	foreach ( $pages as $k => $page ) {

		// Remove exclusive pages
		if ( vgsr_is_post_vgsr( $page->ID, true ) ) {
			unset( $pages[$k] );
		}
	}

	return apply_filters( 'vgsr_post_get_pages', $pages, $args );
}

/**
 * Mark a single page as exclusive in the page list
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_post_list_pages'
 *
 * @param string $title Page title
 * @param WP_Post $args Page object
 * @return string Page title
 */
function _vgsr_post_list_pages( $title, $page ) {

	// Bail when current user _is_ not VGSR
	if ( ! is_user_vgsr() )
		return $title;

	// Mark exclusive pages
	if ( vgsr_is_post_vgsr( $page->ID, true ) ) {
		$title .= '*'; // Make marking optional
	}

	return apply_filters( 'vgsr_post_list_pages', $title, $page );
}

/**
 * Manipulate WHERE clause for {@link get_adjacent_post()} to
 * exclude exclusive posts for non-VGSR users
 *
 * @since 0.0.6
 *
 * @param string $where Adjacent where clause
 * @return string Adjacent where clause
 */
function _vgsr_post_get_adjacent_post( $where ) {

	// Bail when current user _is_ VGSR
	if ( is_user_vgsr() )
		return $where;

	// Exclude exclusive posts
	if ( ( $post__not_in = _vgsr_post_get_hierarchy() ) && ! empty( $post__not_in ) ) {
		$where .= sprintf( ' AND p.ID NOT IN (%s)', implode( ',', $post__not_in ) );
	}

	return $where;
}

/**
 * Manipulate WHERE clause for {@link wp_get_archives()} to
 * exclude exclusive posts for non-VGSR users
 *
 * @since 0.0.6
 *
 * @param string $where Where clause
 * @param array $args Query args
 * @return string Where clause
 */
function _vgsr_post_get_archives( $where, $args = array() ) {
	global $wpdb;

	// Bail when current user _is_ VGSR
	if ( is_user_vgsr() )
		return $where;

	// Exclude posts
	if ( ( $post__not_in = _vgsr_post_get_hierarchy() ) && ! empty( $post__not_in ) ) {
		$where .= sprintf( " AND {$wpdb->posts}.ID NOT IN (%s)", implode( ',', $post__not_in ) );
	}

	return $where;
}

/**
 * Manipulate query clauses for WP_Query (comment feed) or
 * WP_Comment_Query to exclude comments of exclusive posts
 * for non-VGSR users
 *
 * @since 0.0.6
 *
 * @param string|array $clause Comment WHERE query clause or all query clauses
 * @param WP_QueryWP_Comment_Query $query The query object
 * @return string|array Clauses
 */
function _vgsr_post_comment_query( $clause, $query ) {

	// Bail when current user _is_ VGSR
	if ( is_user_vgsr() || ( is_a( $query, 'WP_Query' ) && $query->is_singular ) )
		return $clause;

	// Get exclusive post ids
	$post__not_in = _vgsr_post_get_hierarchy();
	if ( ! empty( $post__not_in ) ) {

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

/** Exclusivity: Hierarchy ******************************************************/

/**
 * Update the post hierarchy option
 *
 * A single option holds the full exclusive posts hierarchy
 * which is used for excluding posts in WP_Query and other
 * situations. This function creates that array of posts.
 *
 * This handles both marked posts as well as posts that are
 * added or removed as child of marked parent posts.
 *
 * @since 0.0.6
 *
 * @param int $post_id Post ID
 * @param bool $rebuild Optional. Whether to fully rebuild the post hierarchy
 */
function _vgsr_post_update_hierarchy( $post_id = 0, $rebuild = false ) {

	// Force rebuild the hierarchy once a day
	if ( ! $rebuild ) {
		$rebuild = ! (bool) get_transient( '_vgsr_post_rebuild_hierarchy' );
	}

	// Bail when no post is provided and not rebuilding
	if ( empty( $post_id ) && ! $rebuild )
		return;

	// Unhook query filter
	remove_filter( 'pre_get_posts', '_vgsr_post_query' );

	// Query all exclusive posts
	$post_types = vgsr_post_types();
	$excl_posts = get_posts( array(
		'numberposts' => -1,
		'post_type'   => $post_types,
		'post_status' => 'any',
		'fields'      => 'ids',
		'meta_key'    => '_vgsr_post_vgsr_only',
		'meta_value'  => 1,
	) );

	// Process a single post
	if ( ! empty( $post_id ) ) {

		// According to current exclusive ancestry
		// @todo Sort this out: how to explicitly mark children of marked posts?
		$append = vgsr_is_post_vgsr( $post_id, true );
		$posts  = array( $post_id );

	// Do full rebuild when explicitly requested. NOTE: this may take a while.
	} elseif ( $rebuild ) {
		$append = null;
		$posts  = $excl_posts;

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
			'post__not_in' => $excl_posts, // Exclude exclusive posts and their children
		) ) ) {
			foreach ( $children as $child_id ) {
				$_posts->append( (int) $child_id );
			}
		}
	}

	// Get the post hierarchy
	$hierarchy = _vgsr_post_get_hierarchy();
	$collected = $_posts->getArrayCopy();

	// Modify global
	if ( null !== $append ) {
		// Append to or remove from the hierarchy
		$hierarchy = $append ? array_unique( array_merge( $hierarchy, $collected ) ) : array_diff( $hierarchy, $collected );
	} else {
		$hierarchy = $collected;
	}

	// Update option
	update_option( '_vgsr_only_post_hierarchy', array_filter( $hierarchy ) );

	// Don't force rebuild for another day
	set_transient( '_vgsr_post_rebuild_hierarchy', true, DAY_IN_SECONDS );

	// Rehook query filter
	add_filter( 'pre_get_posts', '_vgsr_post_query' );
}

/**
 * Return the array of the entire exclusive posts hierarchy
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_post_get_hierarchy'
 * @return array Post ids in exclusive posts hierarchy
 */
function _vgsr_post_get_hierarchy() {
	return (array) apply_filters( 'vgsr_post_get_hierarchy', get_option( '_vgsr_only_post_hierarchy' ) );
}
