<?php

/**
 * VGSR Extension for WP SEO
 * 
 * @package VGSR
 * @subpackage WP SEO
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_WPSEO' ) ) :
/**
 * The VGSR WPSEO class
 *
 * @since 1.0.0
 */
class VGSR_WPSEO {

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Bail if no WordPress SEO
		if ( ! defined( 'WPSEO_VERSION' ) )
			return;

		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Setup class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Paths **********************************************************/

		$this->includes_dir = trailingslashit( vgsr()->extend_dir . 'wordpress-seo' );
		$this->includes_url = trailingslashit( vgsr()->extend_url . 'wordpress-seo' );
	}

	/**
	 * Include the required files
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		require( $this->includes_dir . 'functions.php' );
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
	 * @param array $crumbs Breadcrumbs data
	 * @return array Breadcrumbs data
	 */
	public function breadcrumb_links( $crumbs ) {

		// Bail early when this is a search page
		if ( is_search() )
			return $crumbs;

		// Get option
		$post_maintax = WPSEO_Options::get_option( 'post_types-post-maintax' );

		// Categories for posts
		if ( 'category' === $post_maintax ) {
			foreach ( $crumbs as $k => $crumb ) {

				// Remove 'Uncategorized' default category crumb
				if ( isset( $crumb['term'] ) && (int) get_option( 'default_category' ) == $crumb['term']->term_id ) {
					unset( $crumbs[ $k ] );
				}
			}
		}

		// Galleries
		if ( has_post_format( 'gallery' ) ) {

			// Remove 'Posts' crumb
			unset( $crumbs[1] );
			$crumbs = array_values( $crumbs );

			// Single gallery
			if ( is_singular() ) {
				array_splice( $crumbs, count( $crumbs ) - 1, 0, array(
					array(
						'text' => get_the_archive_title(),
						'url'  => get_post_format_link( 'gallery' )
					)
				) );

			// Gallery archives
			} else {
				$crumbs[1] = array(
					'text' => get_the_archive_title(),
				);
			}
		}

		// BuddyPress
		if ( function_exists( 'buddypress' ) ) {
			$crumbs = vgsr_wpseo_bp_breadcrumb_links( $crumbs );
		}

		// Event Organiser
		if ( defined( 'EVENT_ORGANISER_VER' ) ) {
			$crumbs = vgsr_wpseo_eo_breadcrumb_links( $crumbs );
		}

		$crumbs = array_values( $crumbs );

		return $crumbs;
	}
}

/**
 * Setup the extension logic for WordPress SEO
 *
 * @since 0.1.0
 *
 * @uses VGSR_WPSEO
 */
function vgsr_setup_wpseo() {
	vgsr()->extend->wpseo = new VGSR_WPSEO;
}

endif; // class_exists
