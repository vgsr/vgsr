<?php

/**
 * VGSR Event Organiser Functions
 *
 * @package VGSR
 * @subpackage Event Organiser
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Template ******************************************************************/

/**
 * Filter the archive title for Event pages
 *
 * @since 1.0.0
 *
 * @param string $title Archive title
 * @return string Archive title
 */
function vgsr_eo_archive_title( $title ) {

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
			$title = sprintf( $title, eo_get_event_archive_date( esc_html_x( 'F Y', 'Event archives page title: Month', 'vgsr' ) ) );

		// Daily archives
		} elseif ( eo_is_event_archive( 'day' ) ) {
			$title = sprintf( $title, eo_get_event_archive_date( esc_html_x( 'jS F Y', 'Event archives page title: Day', 'vgsr' ) ) );

		// Fallback
		} else {
			$title = esc_html_x( 'Events', 'Event archives page title', 'vgsr' );
		}
	}

	return $title;
}
