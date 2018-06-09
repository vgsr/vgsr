<?php

/**
 * VGSR Event Organiser Functions
 *
 * @package VGSR
 * @subpackage Event Organiser
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Settings ******************************************************************/

/**
 * Register settings fields for Event Organiser options
 *
 * @since 1.0.0
 */
function vgsr_eo_register_settings_fields() {

	// Events home page
	register_setting( 'eventorganiser_general', 'vgsr_eo_events_home_page', 'intval' );
	add_settings_field(
		'vgsr_eo_events_home_page',
		esc_html__( 'Events home page', 'vgsr' ),
		'vgsr_admin_setting_callback_page',
		'eventorganiser_general',
		'general',
		array(
			'name'        => 'vgsr_eo_events_home_page',
			'selected'    => get_option( 'vgsr_eo_events_home_page', false ),
			'description' => esc_html__( 'The selected page will act as the home page for Events. When no page is selected, a list of generated events widgets will be shown.', 'vgsr' )
		)
	);
}

/** Template ******************************************************************/

/**
 * Return whether we're on the Events home page
 *
 * @since 1.0.0
 *
 * @return bool Is this the event home page?
 */
function vgsr_eo_is_event_home() {
	return is_post_type_archive( 'event' ) && ! eo_get_event_archive_date();
}

/**
 * Return the template for the Events home page
 *
 * @since 1.0.0
 *
 * @return string Template file
 */
function vgsr_eo_get_event_home_template() {
	$templates = array(
		'archive-event-home.php',
		'page.php'
	);

	return vgsr_locate_template( $templates, false );
}

/**
 * Filter the document page title for event pages
 *
 * Available since WP 4.4.0 in `wp_get_document_title()`.
 *
 * @since 1.0.0
 *
 * @param string $title Page title
 * @return string Page title
 */
function vgsr_eo_page_title( $title ) {

	// Run page title through our archive title filter
	$title['title'] = vgsr_eo_get_the_archive_title( $title['title'] );

	return $title;
}

/**
 * Return the archive title for Event pages
 *
 * @since 1.0.0
 *
 * @param string $title Archive title
 * @return string Archive title
 */
function vgsr_eo_get_the_archive_title( $title = '' ) {

	// When displaying an event category
	if ( is_tax( 'event-category' ) ) {
		$title = sprintf( esc_html_x( 'Events: %s', 'Event category archives page title', 'vgsr' ), single_term_title( '', false ) );
	}

	// When displaying an event tag
	if ( is_tax( 'event-tag' ) ) {
		$title = sprintf( esc_html_x( 'Events by tag: %s', 'Event tag archives page title', 'vgsr' ), single_term_title( '', false ) );
	}

	// When displaying an event venue
	if ( is_tax( 'event-venue' ) ) {
		$title = sprintf( esc_html_x( 'Events at %s', 'Event venue archives page title', 'vgsr' ), single_term_title( '', false ) );
	}

	// When displaying event archives of a certain period
	if ( is_post_type_archive( 'event' ) ) {

		/* translators: period archive (year or month or day) */
		$title = esc_html_x( 'Events: %s', 'Event period archives page title', 'vgsr' );

		// Yearly archives
		if ( eo_is_event_archive( 'year' ) ) {
			$title = sprintf( $title, eo_get_event_archive_date( esc_html_x( 'Y', 'Event archives page title: Year', 'vgsr' ) ) );

		// Monthly archives
		} elseif ( eo_is_event_archive( 'month' ) ) {
			$title = sprintf( $title, ucfirst( eo_get_event_archive_date( esc_html_x( 'F Y', 'Event archives page title: Month', 'vgsr' ) ) ) );

		// Daily archives
		} elseif ( eo_is_event_archive( 'day' ) ) {
			$title = sprintf( $title, eo_get_event_archive_date( esc_html_x( 'jS F Y', 'Event archives page title: Day', 'vgsr' ) ) );

		// Fallback
		} else {
			$title = esc_html_x( 'Events', 'Event archives page title', 'vgsr' );
		}
	}

	// Events home page
	if ( vgsr_eo_is_event_home() ) {
		$title = esc_html_x( 'Events', 'Event home page title', 'vgsr' );
	}

	return $title;
}

/**
 * Return the archive description for Event pages
 *
 * @since 1.0.0
 *
 * @param string $description Archive description
 * @return string Archive description
 */
function vgsr_eo_get_the_archive_description( $description = '' ) {

	// When displaying an event category
	if ( is_tax( 'event-category' ) ) {
		$description = sprintf( __( 'This page lists all events published within the selected category. You can visit the <a href="%1$s">main events page</a> to view the upcoming events or browse the <a href="%2$s">date archives</a> to find all registered events on this site by date.', 'vgsr' ), get_post_type_archive_link( 'event' ), eo_get_event_archive_link( date( 'Y' ) ) );
	}

	// When displaying an event tag
	if ( is_tax( 'event-tag' ) ) {
		$description = sprintf( __( 'This page lists all events published within the selected tag. You can visit the <a href="%1$s">main events page</a> to view the upcoming events or browse the <a href="%2$s">date archives</a> to find all registered events on this site by date.', 'vgsr' ), get_post_type_archive_link( 'event' ), eo_get_event_archive_link( date( 'Y' ) ) );
	}

	// When displaying an event venue
	if ( is_tax( 'event-venue' ) ) {
		$description = sprintf( __( 'This page lists all events related to the selected venue. You can visit the <a href="%1$s">main events page</a> to view the upcoming events or browse the <a href="%2$s">date archives</a> to find all registered events on this site by date.', 'vgsr' ), get_post_type_archive_link( 'event' ), eo_get_event_archive_link( date( 'Y' ) ) );
	}

	// When displaying event archives of a certain period
	if ( is_post_type_archive( 'event' ) ) {

		// Yearly archives
		if ( eo_is_event_archive( 'year' ) ) {
			$description = sprintf( __( 'This page lists all events published for the selected year. You can browse here to find all registered events on this site or visit the <a href="%s">main events page</a> to view the upcoming events.', 'vgsr' ), get_post_type_archive_link( 'event' ) );

		// Monthly archives
		} elseif ( eo_is_event_archive( 'month' ) ) {
			$description = sprintf( __( 'This page lists all events published for the selected month. You can visit the <a href="%1$s">main events page</a> to view the upcoming events or browse the <a href="%2$s">date archives</a> to find all registered events on this site by date.', 'vgsr' ), get_post_type_archive_link( 'event' ), eo_get_event_archive_link( date( 'Y' ) ) );

		// Daily archives
		} elseif ( eo_is_event_archive( 'day' ) ) {
			$description = sprintf( __( 'This page lists all events published for the selected date. You can visit the <a href="%1$s">main events page</a> to view the upcoming events or browse the <a href="%2$s">date archives</a> to find all registered events on this site by date.', 'vgsr' ), get_post_type_archive_link( 'event' ), eo_get_event_archive_link( date( 'Y' ) ) );

		// Fallback
		} else {
			$description = sprintf( __( 'This page lists all events on this site. You can browse the <a href="%s">date archives</a> to find all registered events on this site by date.', 'vgsr' ), eo_get_event_archive_link( date( 'Y' ) ) );
		}
	}

	if ( vgsr_eo_is_event_home() ) {
		$description = sprintf( __( 'This page lists the upcoming events. You can browse the <a href="%s">date archives</a> to find all registered events on this site by date.', 'vgsr' ), eo_get_event_archive_link( date( 'Y' ) ) );
	}

	return $description;
}

/**
 * Return whether the current event has a new date in the loop
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_eo_is_new_date'
 * @return bool Has event new date?
 */
function vgsr_eo_is_new_date() {
	global $wp_query;

	// Bail when not in the event loop
	if ( ! in_the_loop() || 'event' !== get_post_type() ) {
		return false;
	}

	// Get the previous post from the loop, compare dates
	$prev_post = $wp_query->current_post > 0 ? $wp_query->posts[ $wp_query->current_post - 1 ] : false;
	$is_new    = ! $prev_post || $prev_post->StartDate !== $wp_query->post->StartDate;

	return (bool) apply_filters( 'vgsr_eo_is_new_date', $is_new );
}
