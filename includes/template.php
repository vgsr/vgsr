<?php

/**
 * VGSR Template Functions
 *
 * @package VGSR
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Modify the join clause in the post adjacency query
 *
 * @since 1.0.0
 *
 * @uses WPDB $wpdb
 *
 * @param string  $join           The JOIN clause in the SQL.
 * @param bool    $in_same_term   Whether post should be in a same taxonomy term.
 * @param array   $excluded_terms Array of excluded term IDs.
 * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
 * @param WP_Post $post           WP_Post object.
 * @return string Join clause
 */
function vgsr_get_adjacent_post_join( $join, $in_same_term, $excluded_terms, $taxonomy, $post ) {
	global $wpdb;

	// Gallery post. Check if term exists
	if ( ! $in_same_term && has_post_format( 'gallery' ) && get_term_by( 'slug', 'post-format-gallery', 'post_format' ) ) {
		$join .= " INNER JOIN {$wpdb->term_relationships} AS gallery_tr ON p.ID = gallery_tr.object_id INNER JOIN $wpdb->term_taxonomy gallery_tt ON gallery_tr.term_taxonomy_id = gallery_tt.term_taxonomy_id";
	}

	return $join;
}

/**
 * Modify the where clause in the post adjacency query
 *
 * @since 1.0.0
 *
 * @uses WPDB $wpdb
 *
 * @param string  $where          The `WHERE` clause in the SQL.
 * @param bool    $in_same_term   Whether post should be in a same taxonomy term.
 * @param array   $excluded_terms Array of excluded term IDs.
 * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
 * @param WP_Post $post           WP_Post object.
 * @return string Where clause
 */
function vgsr_get_adjacent_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
	global $wpdb;

	// Gallery post
	if ( ! $in_same_term && has_post_format( 'gallery' ) && $term = get_term_by( 'slug', 'post-format-gallery', 'post_format' ) ) {
		$where .= $wpdb->prepare( " AND gallery_tt.taxonomy = %s AND gallery_tt.term_id = %s", 'post_format', $term->term_id );
	}

	return $where;
}

/**
 * Modify the archive title
 *
 * @since 1.0.0
 *
 * @param string $title Archive title
 * @return string Archive title
 */
function vgsr_get_the_archive_title( $title = '' ) {

	// Galleries
	if ( is_tax( 'post_format', 'post-format-gallery' ) || has_post_format( 'gallery' ) ) {
		$title = _x( 'Galleries', 'post format archive title' );
	}

	return $title;
}
