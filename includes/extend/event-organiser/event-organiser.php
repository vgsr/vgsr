<?php

/**
 * VGSR Event Organiser Functions
 * 
 * @package VGSR
 * @subpackage Extend
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Event_Organiser' ) ) :
/**
 * The VGSR Event Organiser Class
 *
 * @since 1.0.0
 */
class VGSR_Event_Organiser {

	/**
	 * Setup the Search class
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Event_Organiser::setup_actions()
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Setup actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Event queries. EO hooks at priority 11
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 15 );

		// Adjacent events
		add_filter( 'get_next_post_join',      'adjacent_post_join',  10, 5 );
		add_filter( 'get_previous_post_join',  'adjacent_post_join',  10, 5 );
		add_filter( 'get_next_post_where',     'adjacent_post_where', 10, 5 );
		add_filter( 'get_previous_post_where', 'adjacent_post_where', 10, 5 );
		add_filter( 'get_next_post_sort',      'adjacent_post_sort',  10, 2 );
		add_filter( 'get_previous_post_sort',  'adjacent_post_sort',  10, 2 );
	}

	/**
	 * Manipulate the query arguments before WP_Query is run
	 *
	 * @since 1.0.0
	 *
	 * @uses eventorganiser_is_event_query()
	 *
	 * @param WP_Query $query
	 */
	public function pre_get_posts( $query ) {

		// Bail when this is not an event query
		if ( ! eventorganiser_is_event_query( $query, true ) )
			return;

		// Querying events after a start date
		if ( ! empty( $query->query_vars['ondate'] ) ) {

			// Get the date parts
			$parts = explode( '-', str_replace( '/', '-', $query->query_vars['ondate'] ) );

			// Query ALL events if this is a month or day query
			if ( count( $parts ) > 1 ) {
				$query->query_vars['posts_per_page'] = -1;
			}
		}
	}

	/**
	 * Manipulate the adjacent post JOIN clause
	 *
	 * @see eventorganiser_join_tables()
	 *
	 * @since 1.0.0
	 *
	 * @param string  $join           The JOIN clause in the SQL.
	 * @param bool    $in_same_term   Whether post should be in a same taxonomy term.
	 * @param array   $excluded_terms Array of excluded term IDs.
	 * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
	 * @param WP_Post $post           WP_Post object.
	 * @return string JOIN clause
	 */
	public function adjacent_post_join( $join, $in_same_term, $excluded_terms, $taxonomy, $post ) {

		// When this is an event
		if ( 'event' == $post->post_type ) {
			global $wpdb;

			// Imagine get_query_var( 'group_events_by' ) = 'occurrence'
			$join .= " LEFT JOIN {$wpdb->eo_events} ON p.ID = {$wpdb->eo_events}.post_id";
		}

		return $join;
	}

	/**
	 * Manipulate the adjacent post WHERE clause
	 *
	 * @see eventorganiser_events_where()
	 *
	 * @since 1.0.0
	 *
	 * @param string $where          The `WHERE` clause in the SQL.
	 * @param bool   $in_same_term   Whether post should be in a same taxonomy term.
	 * @param array  $excluded_terms Array of excluded term IDs.
	 * @param string $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
	 * @param WP_Post $post           WP_Post object.
	 * @return string WHERE clause
	 */
	public function adjacent_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {

		// When this is an event
		if ( 'event' == $post->post_type ) {
			global $wpdb;

			$previous = ( 'get_previous_post_where' === current_filter() );
			$op = $previous ? '<' : '>';

			/**
			 * Strip the `p.post_date` WHERE clause, since the post date is not
			 * relevant to the matter of event date adjacency.
			 */
			$original = $wpdb->prepare( "WHERE p.post_date $op %s AND p.post_type = %s", $post->post_date, $post->post_type );
			$improved = $wpdb->prepare( "WHERE 1=1 AND p.post_type = %s", $post->post_type );
			$where    = str_replace( $original, $improved, $where );

			// Exclude the current post
			$where .= $wpdb->prepare( " AND {$wpdb->eo_events}.event_id <> %d", $post->event_id );

			// Previous event. We are interested in events starting earlier
			if ( $previous ) {
				$date_query = array(
					// Either events that occur earlier or on the same moment, but order event IDs to prevent adjacency loops
					'notstrict' => " AND ({$wpdb->eo_events}.StartDate < %s OR ({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime = %s AND {$wpdb->eo_events}.event_id < %d))",
					// All events that occur earlier
					'strict'    => " AND ({$wpdb->eo_events}.StartDate < %s OR ({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime < %s))"
				);

			// Next event. We are interested in events starting later
			} else {
				$date_query = array(
					// Either events that occur later or on the same moment, but order event IDs to prevent adjacency loops
					'notstrict' => " AND ({$wpdb->eo_events}.StartDate >= %s OR ({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime = %s AND {$wpdb->eo_events}.event_id > %d))",
					// All events that occur later
					'strict'    => " AND ({$wpdb->eo_events}.StartDate > %s OR ({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime > %s))"
				);
			}

			// Get the current event's date details
			$date = $post->StartDate;
			$time = $post->StartTime;

			// Append constructed queries
			if ( $time == '00:00:00' ) {
				$where .= $wpdb->prepare( $date_query['notstrict'], $date, $date, $time, $post->event_id );
			} else {
				$where .= $wpdb->prepare( $date_query['strict'], $date, $date, $time );
			}
		}

		return $where;
	}

	/**
	 * Manipulate the adjacent post ORDER BY clause
	 *
	 * @see eventorganiser_sort_events()
	 *
	 * @since 1.0.0
	 *
	 * @param string $order_by The `ORDER BY` clause in the SQL.
	 * @param WP_Post $post    WP_Post object.
	 * @return string ORDER BY clause
	 */
	public function adjacent_post_sort( $order_by, $post ) {

		// When this is an event
		if ( 'event' == $post->post_type ) {
			global $wpdb;

			$previous = ( 'get_previous_post_sort' === current_filter() );
			$order = $previous ? 'DESC' : 'ASC';

			// Order by `eventstart`
			$order_by = "ORDER BY {$wpdb->eo_events}.StartDate $order, {$wpdb->eo_events}.StartTime $order LIMIT 1";
		}

		return $order_by;
	}
}

endif; // class_exists
