<?php

/**
 * VGSR Event Organiser Events Home content
 *
 * @package VGSR
 * @subpackage Events Organiser
 */

// Events home description
the_archive_description( '<div class="archive-description">', '</div>' );

// Define widget args
$widget_args = array(
	'before_widget' => '<div class="%s">',
	'after_widget'  => "</div>",
	'before_title'  => '<h3 class="widgettitle">',
	'after_title'   => '</h3>',
);

// Upcoming Events
the_widget( 'VGSR_EO_Upcoming_Events_Widget', array(
	'title'    => esc_html__( 'Upcoming Events', 'vgsr' ),
	'num_days' => 5
), $widget_args );
