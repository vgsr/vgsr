<?php

/**
 * VGSR Posts Template Functions
 * 
 * @package VGSR
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Modify the categories in the category list
 *
 * @since 1.0.0
 *
 * @param array $cats Post categories
 * @param int $post_id Post ID
 * @return array Post categories
 */
function vgsr_the_category_list( $cats, $post_id ) {

	// Remove the default category ('uncategorized') from the list
	$cats = array_values( wp_list_filter( $cats, array( 'term_id' => (int) get_option( 'default_category' ) ), 'NOT' ) );

	return $cats;
}
add_filter( 'the_category_list', 'vgsr_the_category_list', 10, 2 );

/**
 * Modify the category display name
 *
 * When the category list is empty, `the_category_list()` will default
 * to the unlinked text 'Uncategorized'. As we don't want that, this
 * filter is in place to undo that.
 *
 * @since 1.0.0
 *
 * @param string $cat Category name
 * @return string Category name
 */
function vgsr_the_category( $cat ) {

	// In the loop, hide the 'uncategorized' (default) category
	if ( __( 'Uncategorized' ) === $cat && ! is_admin() && in_the_loop() ) {
		$cat = '';
	}

	return $cat;
}
add_filter( 'the_category', 'vgsr_the_category' );

