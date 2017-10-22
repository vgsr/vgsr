<?php

/**
 * VGSR WP SEO Extension
 * 
 * @package VGSR
 * @subpackage WP SEO
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_WPSEO' ) ) :
/**
 * The VGSR WPSEO Class
 *
 * @since 1.0.0
 */
class VGSR_WPSEO {

	/**
	 * Dummy for WPSEO's options from {@see WPSEO_Options::get_all()}
	 * to prevent multiple calls to that function.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $options = array();

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Setup class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {
		$this->options = WPSEO_Options::get_all();
	}

	/**
	 * Setup actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {
		add_filter( 'wpseo_breadcrumb_links', array( $this, 'breadcrumb_links' ) );
	}

	/**
	 * Modify the crumbs collection
	 *
	 * @since 1.0.0
	 *
	 * @param array $crumbs Crumbs
	 * @return array Crumbs
	 */
	public function breadcrumb_links( $crumbs ) {

		// Categories taxonomy crumbs
		if ( isset( $this->options['post_types-post-maintax'] ) && 'category' == $this->options['post_types-post-maintax'] ) {
			foreach ( $crumbs as $k => $crumb ) {

				// Remove 'Uncategorized' default category crumb
				if ( isset( $crumb['term'] ) && (int) get_option( 'default_category' ) == $crumb['term']->term_id ) {
					unset( $crumbs[ $k ] );
				}
			}
		}

		// Support BuddyPress pages
		if ( function_exists( 'buddypress' ) && is_buddypress() ) {

			/**
			 * Add member type directories to the members breadcrumb trail.
			 */

			// Get effective member type
			$member_type = bp_is_user() ? bp_get_member_type( bp_displayed_user_id() ) : bp_get_current_member_type();
			$member_type = bp_get_member_type_object( $member_type );
			if ( $member_type && $member_type->has_directory ) {

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
		}

		// Support Event Organiser pages
		if ( defined( 'EVENT_ORGANISER_VER' ) && ( is_post_type_archive( 'event' ) || is_singular( 'event' ) ) ) {

			/**
			 * Add year/month/day event archives to the event breadcrumb trail.
			 */

			// Define local variable(s)
			$add_year = $add_month = false;
			$ancestors = array();

			// Single event
			if ( is_singular( 'event' ) ) {
				$add_year = $add_month = true;

				// Bail when taxonomy crumbs are used for this event
				if ( isset( $this->options['post_types-event-maintax'] ) && $this->options['post_types-event-maintax'] != '0'
					&& wp_get_object_terms( get_post()->ID, $this->options['post_types-event-maintax'] )
				) {
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

				// Add Day parent
				$ancestors[] = array(
					'text'       => date( _x( 'l j', 'Event archives breadcrumb title: Day', 'zeta' ), strtotime( $date ) ),
					'url'        => call_user_func_array( 'eo_get_event_archive_link', explode( '-', date( 'Y-m-d', strtotime( $date ) ) ) ),
					'allow_html' => false,
				);

			// Event archive
			} else {
				$date = eo_get_event_archive_date( 'Y-m-d H:i:s' );
			}

			// Make $date a timestamp
			$date = strtotime( $date );

			// Add Month parent
			if ( $add_month || eo_is_event_archive( 'day' ) ) {
				$add_year = true;
				$ancestors[] = array(
					'text'       => date( _x( 'F', 'Event archives breadcrumb title: Month', 'zeta' ), $date ),
					'url'        => call_user_func_array( 'eo_get_event_archive_link', explode( '-', date( 'Y-m', $date ) ) ),
					'allow_html' => false,
				);

				// Set the proper current element title
				if ( ! $add_month ) {
					$crumbs[ count( $crumbs ) - 1 ] = array( 'text' => date( _x( 'l j', 'Event archives breadcrumb title: Day', 'zeta' ), $date ) );
				}
			}

			// Add Year parent
			if ( $add_year || eo_is_event_archive( 'month' ) ) {
				$ancestors[] = array(
					'text'       => date( _x( 'Y', 'Event archives breadcrumb title: Year', 'zeta' ), $date ),
					'url'        => call_user_func_array( 'eo_get_event_archive_link', explode( '-', date( 'Y', $date ) ) ),
					'allow_html' => false,
				);

				// Set the proper current element title
				if ( ! $add_year ) {
					$crumbs[ count( $crumbs ) - 1 ] = array( 'text' => date( _x( 'F', 'Event archives breadcrumb title: Month', 'zeta' ), $date ) );
				}
			}

			// Set the proper current element title
			if ( eo_is_event_archive( 'year' ) ) {
				$crumbs[ count( $crumbs ) - 1 ] = array( 'text' => date( _x( 'Y', 'Event archives breadcrumb title: Year', 'zeta' ), $date ) );
			}

			// Insert event ancestor crumbs before the last one
			array_splice( $crumbs, count( $crumbs ) - 1, 0, array_reverse( $ancestors ) );
		}

		return $crumbs;
	}
}

endif; // class_exists
