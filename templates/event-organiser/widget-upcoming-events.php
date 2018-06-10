<?php

/**
 * VGSR Event Organiser Upcoming Events Widget Template
 *
 * @package Plugin
 * @subpackage Main
 */

?>

<?php if ( have_posts() ) : ?>

	<?php while ( have_posts() ) : the_post(); ?>

		<?php if ( vgsr_eo_is_new_date() ) : ?>

		<h4 class="vgsr-eo-start-date"><?php echo ucfirst( eo_get_the_start( 'D j F' ) ); ?></h4>

		<?php endif; ?>

		<p>
			<span class="vgsr-eo-start-time"><?php eo_the_start( 'H:i' ); ?></span> &ndash; <span class="vgsr-eo-end-time"><?php eo_the_end( 'H:i' ); ?></span> <a class="vgsr-eo-permalink" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</p>

	<?php endwhile; ?>

<?php else : ?>

	<p><?php esc_html_e( 'No upcoming events were found.', 'vgsr' ); ?></p>

<?php endif; ?>
