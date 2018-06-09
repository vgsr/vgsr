<?php

/**
 * VGSR Theme Compatability Functions
 *
 * @package VGSR
 * @subpackage Theme
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Stop WordPress performing a DB query for its main loop
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_bypass_wp_query'
 *
 * @param null $retval Current return value
 * @param WP_Query $query Query object
 * @return null|array
 */
function vgsr_bypass_wp_query( $retval, $query ) {

	// Bail when this is not the main query
	if ( ! $query->is_main_query() )
		return $retval;

	// When bypassing the main query...
	if ( apply_filters( 'vgsr_bypass_wp_query', false, $query ) ) {

		// ... return something other than a null value to bypass WP_Query
		$retval = array();
	}

	return $retval;
}

/**
 * Render a template part with a custom post query context
 *
 * @since 1.0.0
 *
 * @param WP_Query $query Post query
 * @param string $slug Template slug.
 * @param string $name Optional. Template name.
 * @param bool $echo Optional. Whether to echo the template part. Defaults to true.
 * @return string Template part content
 */
function vgsr_bake_template_part( $query, $slug, $name = '', $echo = true ) {

	// Replace main query
	$wp_query = $GLOBALS['wp_query'];
	$GLOBALS['wp_query'] = $query;

	// Output template part
	$output = vgsr_buffer_template_part( $slug, $name, $echo );

	// Reset main query
	$GLOBALS['wp_query'] = $wp_query;

	// Reset post data
	wp_reset_postdata();

	return $output;
}

/**
 * Get a template part in an output buffer and return it
 *
 * @since 1.0.0
 *
 * @param string $slug Template slug.
 * @param string $name Optional. Template name.
 * @param bool $echo Optional. Whether to echo the template part. Defaults to false.
 * @return string Template part content
 */
function vgsr_buffer_template_part( $slug, $name = '', $echo = false ) {

	// Start buffer
	ob_start();

	// Output template part
	vgsr_get_template_part( $slug, $name );

	// Close buffer and get its contents
	$output = ob_get_clean();

	// Echo or return the output buffer contents
	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Output a template part
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'vgsr_get_template_part_{$slug}'
 * @uses apply_filters() Calls 'vgsr_get_template_part'
 *
 * @param string $slug Template slug.
 * @param string $name Optional. Template name. Defaults to the current entity type.
 */
function vgsr_get_template_part( $slug, $name = '' ) {

	// Execute code for this part
	do_action( "vgsr_get_template_part_{$slug}", $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( $name )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template part to be filtered
	$templates = apply_filters( 'vgsr_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return vgsr_locate_template( $templates, true, false );
}

/**
 * Retrieve the path of the highest priority template file that exists.
 *
 * This function provides only a hook for other plugins to locate their
 * templates, without offering any fallback template location logic.
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_locate_template'
 *
 * @param array $template_names Template hierarchy
 * @param bool $load Optional. Whether to load the file when it is found. Default to false.
 * @param bool $require_once Optional. Whether to require_once or require. Default to true.
 * @return string Path of the template file when located.
 */
function vgsr_locate_template( $template_names, $load = false, $require_once = true ) {
	return apply_filters( 'vgsr_locate_template', '', $template_names, $load, $require_once );
}

/**
 * Filter the theme's template for theme compatability
 *
 * @since 1.0.0
 *
 * @param string $template Path to template file
 * @return string Path to template file
 */
function vgsr_activate_theme_compat( $template = '' ) {

	// Require plugins to activate theme compat
	$args = apply_filters( 'vgsr_activate_theme_compat', array() );

	// Reset post
	if ( ! empty( $args ) ) {
		vgsr_theme_compat_reset_post( wp_parse_args( $args, array(
			'ID'          => 0,
			'post_author' => 0,
			'post_date'   => 0,
			'post_type'   => '',
		) ) );
	}

	return $template;
}

/**
 * Reset WordPress globals with dummy data to prevent templates
 * reporting missing data.
 *
 * @see bbPress's bbp_theme_compat_reset_post()
 *
 * @since 1.0.0
 *
 * @global WP_Query $wp_query
 * @global WP_Post $post
 * @param array $args Reset post arguments
 */
function vgsr_theme_compat_reset_post( $args = array() ) {
	global $wp_query, $post;

	// Switch defaults if post is set
	if ( isset( $wp_query->post ) ) {
		$dummy = wp_parse_args( $args, array(
			'ID'                    => $wp_query->post->ID,
			'post_status'           => $wp_query->post->post_status,
			'post_author'           => $wp_query->post->post_author,
			'post_parent'           => $wp_query->post->post_parent,
			'post_type'             => $wp_query->post->post_type,
			'post_date'             => $wp_query->post->post_date,
			'post_date_gmt'         => $wp_query->post->post_date_gmt,
			'post_modified'         => $wp_query->post->post_modified,
			'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
			'post_content'          => $wp_query->post->post_content,
			'post_title'            => $wp_query->post->post_title,
			'post_excerpt'          => $wp_query->post->post_excerpt,
			'post_content_filtered' => $wp_query->post->post_content_filtered,
			'post_mime_type'        => $wp_query->post->post_mime_type,
			'post_password'         => $wp_query->post->post_password,
			'post_name'             => $wp_query->post->post_name,
			'guid'                  => $wp_query->post->guid,
			'menu_order'            => $wp_query->post->menu_order,
			'pinged'                => $wp_query->post->pinged,
			'to_ping'               => $wp_query->post->to_ping,
			'ping_status'           => $wp_query->post->ping_status,
			'comment_status'        => $wp_query->post->comment_status,
			'comment_count'         => $wp_query->post->comment_count,
			'filter'                => $wp_query->post->filter,

			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false,
		) );
	} else {
		$dummy = wp_parse_args( $args, array(
			'ID'                    => -9999,
			'post_status'           => 'publish',
			'post_author'           => 0,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => 0,
			'post_date_gmt'         => 0,
			'post_modified'         => 0,
			'post_modified_gmt'     => 0,
			'post_content'          => '',
			'post_title'            => '',
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => '',
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',

			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false,
		) );
	}

	// Bail if dummy post is empty
	if ( empty( $dummy ) ) {
		return;
	}

	// Parse content as template
	if ( is_array( $args['post_content'] ) ) {
		$dummy['post_content'] = call_user_func_array( 'vgsr_buffer_template_part', $args['post_content'] );
	}

	// Set the $post global
	$post = new WP_Post( (object) $dummy );

	// Copy the new post global into the main $wp_query
	$wp_query->post       = $post;
	$wp_query->posts      = array( $post );

	// Prevent comments form from appearing
	$wp_query->post_count = 1;
	$wp_query->is_404     = $dummy['is_404'];
	$wp_query->is_page    = $dummy['is_page'];
	$wp_query->is_single  = $dummy['is_single'];
	$wp_query->is_archive = $dummy['is_archive'];
	$wp_query->is_tax     = $dummy['is_tax'];

	// Clean up the dummy post
	unset( $dummy );

	/**
	 * Force the header back to 200 status if not a deliberate 404
	 *
	 * @see http://bbpress.trac.wordpress.org/ticket/1973
	 */
	if ( ! $wp_query->is_404() ) {
		status_header( 200 );
	}
}
