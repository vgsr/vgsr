<?php

/**
 * VGSR WP SEO Functions
 *
 * @package VGSR
 * @subpackage WP SEO
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Breadcrumbs *********************************************************/

/**
 * Modify the breadcrumb links for BuddyPress pages
 *
 * @since 1.0.0
 *
 * @param array $crumbs Breadcrumbs data
 * @return array Breadcrumbs data
 */
function vgsr_wpseo_bp_breadcrumb_links( $crumbs = array() ) {

	// Bail when not a BP page
	if ( ! is_buddypress() ) {
		return $crumbs;
	}

	/**
	 * Add member type directories for members pages.
	 */

	// Get effective member type for displayed user or displayed member type directory
	$member_type = bp_is_user() ? bp_get_member_type( bp_displayed_user_id(), true ) : bp_get_current_member_type();
	$member_type = bp_get_member_type_object( $member_type );

	// Displaying user page or member type directory page
	if ( $member_type && $member_type->has_directory && vgsr_bp_is_vgsr_member_type( $member_type->name ) && is_user_vgsr() ) {

		// Member type directory
		if ( bp_get_current_member_type() ) {

			// Set the correct element text on the last element
			$crumbs[ count( $crumbs ) - 1 ] = array( 'text' => $member_type->labels['name'] );

		// Default to prefix the current page with the member type directory
		} else {
			$crumb = array(
				'text'       => $member_type->labels['name'],
				'url'        => bp_get_member_type_directory_permalink( $member_type->name ), // BP 2.5+
				'allow_html' => false,
			);

			// Insert member type crumb before last crumb
			array_splice( $crumbs, count( $crumbs ) - 1, 0, array( $crumb ) );
		}
	}

	// For current non-vgsr users
	if ( ! is_user_vgsr() ) {

		// Walk crumbs
		foreach ( $crumbs as $k => $crumb ) {

			// Do not link to default members directory
			if ( isset( $crumb['id'] ) && bp_core_get_directory_page_id( 'members' ) == $crumb['id'] ) {
				$crumbs[ $k ] = array(
					'text' => get_the_title( $crumb['id'] )
				);
			}
		}
	}

	return $crumbs;
}

/**
 * Modify the breadcrumb links for Event Organiser pages
 *
 * @since 1.0.0
 *
 * @param array $crumbs Breadcrumbs data
 * @return array Breadcrumbs data
 */
function vgsr_wpseo_eo_breadcrumb_links( $crumbs = array() ) {

	// Bail when not on a EO page
	if ( 'event' !== get_post_type() && ! vgsr_eo_is_event_home() ) {
		return $crumbs;
	}

	/**
	 * Add year/month/day event archives to the event breadcrumb trail.
	 */

	// Get date from single Event
	if ( is_singular( 'event' ) ) {

		// Get option
		$event_maintax = WPSEO_Options::get_option( 'post_types-event-maintax' );

		// Bail when taxonomy crumbs are used for this event
		if ( $event_maintax !== '0' && wp_get_object_terms( get_post()->ID, $event_maintax ) ) {
			return $crumbs;
		} 

		// Event reoccurrence
		if ( eo_reoccurs() ) {

			// Get the current, next or last occurrence date
			if ( ! $occurrence = eo_get_current_occurrence_of() ) {
				if ( ! $occurrence = eo_get_next_occurrence_of() ) {
					$date = eo_get_schedule_last( 'Y-m-d H:i:s' );
				}
			}

			if ( isset( $occurrence['start'] ) && is_a( $occurrence['start'], 'DateTime' ) ) {
				$date = $occurrence['start']->format( 'Y-m-d H:i:s' );
			}

		// Single occurrence, use start date
		} else {
			$date = eo_get_the_start( 'Y-m-d H:i:s' );
		}

	// Get date from Event archive
	} else {
		$date = eo_get_event_archive_date( 'Y-m-d H:i:s' );
	}

	// Make date a timestamp
	$date = strtotime( $date );

	// Define plugin crumb presets
	$_crumbs = array(

		// Root
		'root' => array(
			'text'       => esc_html_x( 'Events', 'Breadcrumb root title', 'vgsr' ),
			'url'        => get_post_type_archive_link( 'event' ),
			'allow_html' => false,
		),

		// Yearly archives
		'year' => array(
			'text'       => date_i18n( esc_html_x( 'Y', 'Event archives breadcrumb title: Year', 'vgsr' ), $date ),
			'url'        => call_user_func_array( 'eo_get_event_archive_link', explode( '-', date( 'Y', $date ) ) ),
			'allow_html' => false,
		),

		// Monthly archives
		'month' => array(
			'text'       => ucfirst( date_i18n( esc_html_x( 'F', 'Event archives breadcrumb title: Month', 'vgsr' ), $date ) ),
			'url'        => call_user_func_array( 'eo_get_event_archive_link', explode( '-', date( 'Y-m', $date ) ) ),
			'allow_html' => false,
		),

		// Daily archives
		'day' => array(
			'text'       => date_i18n( esc_html_x( 'l j', 'Event archives breadcrumb title: Day', 'vgsr' ), $date ),
			'url'        => call_user_func_array( 'eo_get_event_archive_link', explode( '-', date( 'Y-m-d', $date ) ) ),
			'allow_html' => false,
		),
	);

	// Define local variable(s)
	$last      = count( $crumbs ) - 1;
	$last_item = $crumbs[ $last ];

	// Overwrite Events root
	$crumbs[1] = $_crumbs['root'];

	// Yearly archives
	if ( eo_is_event_archive( 'year' ) ) {

		// Add Year
		$crumbs[] = array(
			'text'       => $_crumbs['year']['text'],
			'allow_html' => false
		);

	// Monthly archives
	} elseif ( eo_is_event_archive( 'month' ) ) {

		// Prepend Year, add Month
		$crumbs[] = $_crumbs['year'];
		$crumbs[] = array(
			'text'       => $_crumbs['month']['text'],
			'allow_html' => false
		);

	// Daily archives
	} elseif ( eo_is_event_archive( 'day' ) ) {

		// Prepend Year, Month, add Day
		$crumbs[] = $_crumbs['year'];
		$crumbs[] = $_crumbs['month'];
		$crumbs[] = array(
			'text'       => $_crumbs['day']['text'],
			'allow_html' => false
		);

	// Taxonomy archives
	} elseif ( is_tax() ) {

		// Append term. Item was overwritten by root
		$crumbs[] = $last_item;

	// Single event
	} elseif ( is_singular( 'event' ) ) {

		// Prepend Year, Month, Day
		array_splice( $crumbs, $last, 0, array(
			$_crumbs['year'],
			$_crumbs['month'],
			$_crumbs['day']
		) );
	}

	return $crumbs;
}
