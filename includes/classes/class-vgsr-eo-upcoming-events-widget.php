<?php

/**
 * VGSR Event Organiser Upcoming Events Widget
 *
 * @package VGSR
 * @subpackage Event Organiser
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_EO_Upcoming_Events_Widget' ) ) :
/**
 * The VGSR Event Organiser Upcoming Events widget class
 *
 * @since 1.0.0
 */
class VGSR_EO_Upcoming_Events_Widget extends WP_Widget {

	/**
	 * Setup this class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( 
			'vgsr-eo-upcoming-events',
			esc_html__( 'Upcoming Events', 'vgsr' ),
			array(
				'classname' => 'vgsr-eo-upcoming-events',
				'description' => esc_html__( 'Displays the upcoming events.', 'vgsr' ),
			)
		);
	}

	/**
	 * Display the widget contents
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'widget_title'
	 *
	 * @param array $args Sidebar markup arguments
	 * @param array $instance Widget settings
	 */
	public function widget( $args, $instance ) {
		$instance = $this->defaults( $instance );

		// Get number of days to display
		$num_days = absint( $instance['num_days'] );

		// Query for events
		$query = new WP_Query( array(
			'post_type'         => 'event',
			'posts_per_page'    => $num_days * 3, // Query some more, just in case
			'event_start_after' => date( 'Y-m-d H:i:s' ), // Now, relative to session
			'orderby'           => 'eventstart',
			'order'             => 'ASC',
			'showrepeats'       => true
		) );

		// Get last date to compare
		$last_date = date( 'Y-m-d', time( '-1 day' ) );

		// Only list events for number of days
		foreach ( $query->posts as $k => $post ) {
			if ( 0 !== $num_days ) {

				// Step into next day
				if ( $last_date !== $post->StartDate ) {
					$last_date = $post->StartDate;
					$num_days--;
				}

			// Remove post
			} else {
				unset( $query->posts[ $k ] );
			}
		}

		// Update query properties
		$query->posts = array_values( $query->posts );
		$query->post_count = count( $query->posts );

		// Wrap widget
		echo $args['before_widget'];

		$widget_title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		if ( $widget_title ) {
			$widget_title = sprintf(
				$instance['link_to_archive'] ? '<a href="%s">%s</a>' : '%2$s',
				esc_url( get_post_type_archive_link( 'event' ) ),
				esc_html( $widget_title )
			);

			echo $args['before_title'] . $widget_title . $args['after_title'];
		}

		?>

		<div class="widget-content">
			<?php vgsr_bake_template_part( $query, 'widget-upcoming-events' ); ?>
		</div>

		<?php

		// Close widget
		echo $args['after_widget'];
	}

	/**
	 * Display the widget settings form
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance Widget settings
	 */
	public function form( $instance ) {
		$instance = $this->defaults( $instance ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'vgsr' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'link_to_archive' ); ?>">
				<input id="<?php echo $this->get_field_id( 'link_to_archive' ); ?>" name="<?php echo $this->get_field_name( 'link_to_archive' ); ?>" type="checkbox" value="1" <?php checked( $instance['link_to_archive'] ); ?>/>
				<?php esc_html_e( 'Link widget title to the events home page', 'vgsr' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'num_days' ); ?>"><?php esc_html_e( 'Number of days to display:', 'vgsr' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'num_days' ); ?>" name="<?php echo $this->get_field_name( 'num_days' ); ?>" type="number" value="<?php echo esc_attr( $instance['num_days'] ); ?>" />
		</p>

		<?php
	}

	/**
	 * Parse the updated widget settings
	 *
	 * @since 1.0.0
	 *
	 * @param array $new_instance New widget settings
	 * @param array $old_instance Previous widget settings
	 * @return array New widget settings
	 */
	public function update( $new_instance, $old_instance ) {

		// Sanitize input
		$new_instance['title']    = sanitize_text_field( $new_instance['title'] );
		$new_instance['num_days'] = absint( $new_instance['num_days'] );

		return $new_instance;
	}

	/**
	 * Parse widget arguments applying default values
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments
	 * @return array Parsed arguments
	 */
	public function defaults( $args = array() ) {
		return wp_parse_args( $args, array(
			'title'           => '',
			'link_to_archive' => false,
			'num_days'        => 3
		) );
	}
}

endif; // class_exists
