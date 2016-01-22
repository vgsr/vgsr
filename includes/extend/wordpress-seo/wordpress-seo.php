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
	 * Class constructor
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_WPSEO::setup_actions()
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
		add_filter( 'wpseo_breadcrumb_links',  array( $this, 'breadcrumb_links' ) );
	}

	/**
	 * Modify the crumbs collection
	 *
	 * @since 1.0.0
	 *
	 * @uses is_buddypress()
	 * @uses bp_is_user()
	 * @uses bp_get_member_type()
	 * @uses bp_displayed_user_id()
	 * @uses bp_get_current_member_type()
	 * @uses bp_get_member_type_object()
	 * @uses bp_get_directory_title()
	 * @uses bp_get_members_directory_permalink()
	 * @uses apply_filters() Calls 'bp_members_member_type_base'
	 *
	 * @param array $crumbs Crumbs
	 * @return array Crumbs
	 */
	public function breadcrumb_links( $crumbs ) {

		// Support BuddyPress
		if ( function_exists( 'buddypress' ) && is_buddypress() ) {

			// Get effective member type
			$member_type = bp_is_user() ? bp_get_member_type( bp_displayed_user_id() ) : bp_get_current_member_type();
			$member_type = bp_get_member_type_object( $member_type );
			if ( $member_type && $member_type->has_directory ) {

				// Member type directory
				if ( bp_get_current_member_type() ) {

					// Append directory crumb and current page
					array_splice( $crumbs, count( $crumbs ) - 1, 0, array(
						array(
							'text'       => bp_get_directory_title( 'members' ),
							'url'        => bp_get_members_directory_permalink(),
							'allow_html' => true,

						// Replace last element with correct member type name
						), array(
							'text'       => $member_type->labels['name'],
						)
					) );

					// Remove last 'empty' element
					array_pop( $crumbs );

				// Default to insert member type directory next-to-last
				} else {
					$crumb = array(
						'text'       => $member_type->labels['name'],
						// Quite a cumbersome way to construct the member type directory url :S
						'url'        => trailingslashit( bp_get_members_directory_permalink() . apply_filters( 'bp_members_member_type_base', _x( 'type', 'member type URL base', 'buddypress' ) ) . '/' . $member_type->directory_slug ),
						'allow_html' => false,
					);

					// Insert member type crumb before last crumb
					array_splice( $crumbs, count( $crumbs ) - 1, 0, array( $crumb ) );
				}
			}
		}

		return $crumbs;
	}
}

endif; // class_exists
