<?php

/**
 * VGSR Extension for Event Organiser
 * 
 * @package VGSR
 * @subpackage Event Organiser
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
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Define default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Paths **********************************************************/

		// Includes
		$this->includes_dir = trailingslashit( vgsr()->extend_dir . 'event-organiser' );
		$this->includes_url = trailingslashit( vgsr()->extend_url . 'event-organiser' );

		// Templates
		$this->themes_dir   = trailingslashit( vgsr()->themes_dir . 'event-organiser' );
		$this->themes_url   = trailingslashit( vgsr()->themes_url . 'event-organiser' );
	}

	/**
	 * Include the required files
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		require( $this->includes_dir . 'actions.php'   );
		require( $this->includes_dir . 'functions.php' );
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
		add_filter( 'get_previous_post_join',  array( $this, 'adjacent_post_join'  ), 10, 5 );
		add_filter( 'get_next_post_join',      array( $this, 'adjacent_post_join'  ), 10, 5 );
		add_filter( 'get_previous_post_where', array( $this, 'adjacent_post_where' ), 10, 5 );
		add_filter( 'get_next_post_where',     array( $this, 'adjacent_post_where' ), 10, 5 );
		add_filter( 'get_previous_post_sort',  array( $this, 'adjacent_post_sort'  ), 10, 2 );
		add_filter( 'get_next_post_sort',      array( $this, 'adjacent_post_sort'  ), 10, 2 );

		// Templates
		add_filter( 'eventorganiser_template_stack', array( $this, 'template_stack'   )        );
		add_filter( 'vgsr_bypass_wp_query',          array( $this, 'bypass_wp_query'  ), 10, 2 );
		add_filter( 'vgsr_template_include',         array( $this, 'template_include' )        );
		add_filter( 'vgsr_activate_theme_compat',    array( $this, 'theme_compat'     )        );
		add_filter( 'vgsr_locate_template',          array( $this, 'locate_template'  ), 10, 4 );

		// Widgets
		add_filter( 'vgsr_register_widgets', array( $this, 'register_widgets' ) );
	}

	/**
	 * Manipulate the query arguments before WP_Query is run
	 *
	 * @since 1.0.0
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

			// Query ALL events when this is a month or day query (having at least yy-mm)
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
		if ( is_singular( 'event' ) ) {
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
		if ( is_singular( 'event' ) ) {
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

			// Previous event. Query events starting earlier
			if ( $previous ) {
				$date_query = array(
					// Either events that occur earlier or on the same moment, but order event IDs to prevent adjacency loops
					'notstrict' => " AND ({$wpdb->eo_events}.StartDate < %s OR ({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime = %s AND {$wpdb->eo_events}.event_id < %d))",
					// All events that occur earlier
					'strict'    => " AND ({$wpdb->eo_events}.StartDate < %s OR ({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime < %s))"
				);

			// Next event. Query events starting later
			} else {
				$date_query = array(
					// Either events that occur later or on the same moment, but order event IDs to prevent adjacency loops
					'notstrict' => " AND ({$wpdb->eo_events}.StartDate >= %s OR ({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime = %s AND {$wpdb->eo_events}.event_id > %d))",
					// All events that occur later
					'strict'    => " AND ({$wpdb->eo_events}.StartDate > %s OR ({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime > %s))"
				);
			}

			// The current event reoccurs
			if ( eo_reoccurs() ) {

				// Get the current, next or last occurrence date
				if ( ! $occurrence = eo_get_current_occurrence_of( $post->ID ) ) {
					if ( ! $occurrence = eo_get_next_occurrence_of( $post->ID ) ) {
						$date = eo_get_schedule_last( 'Y-m-d' );
						$time = eo_get_schedule_last( 'H:i:s' );
					}
				}

				if ( isset( $occurrence['start'] ) && is_a( $occurrence['start'], 'DateTime' ) ) {
					$date = $occurrence['start']->format( 'Y-m-d' );
					$time = $occurrence['start']->format( 'H:i:s' );
				}

			// Single event, get its date
			} else {
				$date = $post->StartDate;
				$time = $post->StartTime;
			}

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
		if ( is_singular( 'event' ) ) {
			global $wpdb;

			$previous = ( 'get_previous_post_sort' === current_filter() );
			$order = $previous ? 'DESC' : 'ASC';

			// Order by `eventstart`
			$order_by = "ORDER BY {$wpdb->eo_events}.StartDate $order, {$wpdb->eo_events}.StartTime $order LIMIT 1";
		}

		return $order_by;
	}

	/** Templates **********************************************************/

	/**
	 * Modify the location stack for Event Organiser templates
	 *
	 * @since 1.0.0
	 *
	 * @param array $stack Template locations
	 * @return array Template locations
	 */
	public function template_stack( $stack ) {

		// Prepend plugin template location
		$stack = array_merge( array( $this->themes_dir ), $stack );

		return $stack;
	}

	/**
	 * Modify whether to bypass the main query
	 *
	 * @since 1.0.0
	 *
	 * @param bool $retval Whether to bypass the main query
	 * @param WP_Query $query Query
	 * @return bool Bypass the main query?
	 */
	public function bypass_wp_query( $retval, $query ) {
		return $retval || vgsr_eo_is_event_home();
	}

	/**
	 * Modify the required template
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Template file
	 * @return string Template file
	 */
	public function template_include( $template ) {

		// Events home page
		if ( vgsr_eo_is_event_home() && ( $_template = vgsr_eo_get_event_home_template() ) ) {
			$template = $_template;
		}

		return $template;
	}

	/**
	 * Trigger post reset for theme compat
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Post reset arguments
	 * @return array Post reset arguments
	 */
	public function theme_compat( $args ) {

		// Events home page
		if ( vgsr_eo_is_event_home() ) {

			// Defaulting to a page?
			$post = get_option( 'vgsr_eo_events_home_page', false );
			$post = $post ? get_post( $post ) : false;

			// Reset post by page
			if ( $post ) {
				$args = $post->to_array();

			// Default post compat
			} else {
				$args = array(
					'post_title'   => esc_html__( 'Events', 'vgsr' ),
					'post_content' => array( 'content-event-home' ),
					'is_single'    => true,
				);


			}
		}

		return $args;
	}

	/**
	 * Retrieve the path of the highest priority template file that exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Located template file
	 * @param array $template_names Template hierarchy
	 * @param bool $load Optional. Whether to load the file when it is found. Default to false.
	 * @param bool $require_once Optional. Whether to require_once or require. Default to true.
	 * @return string Path of the template file when located.
	 */
	public function locate_template( $template, $template_names, $load, $require_once ) {
		if ( ! $template ) {
			$template = eo_locate_template( $template_names, $load, $require_once );
		}

		return $template;
	}

	/** Widgets ************************************************************/

	/**
	 * Modify the list of widgets to register
	 *
	 * @since 1.0.0
	 *
	 * @param array $widgets Widgets
	 * @return array Widgets
	 */
	public function register_widgets( $widgets ) {

		// Upcoming events
		$widgets['VGSR_EO_Upcoming_Events_Widget'] = vgsr()->includes_dir . 'classes/class-vgsr-eo-upcoming-events-widget.php';

		return $widgets;
	}
}

endif; // class_exists
