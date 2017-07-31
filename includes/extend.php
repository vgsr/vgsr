<?php

/**
 * VGSR Extensions
 *
 * @package VGSR
 * @subpackage Extend
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Extend *****************************************************************/

/**
 * Loads the bbPress component
 * 
 * @since 0.0.1
 *
 * @return When bbPress is not active
 */
function vgsr_setup_bbpress() {

	// Bail if no bbPress
	if ( ! function_exists( 'bbpress' ) )
		return;

	// Include the bbPress component
	require( vgsr()->extend_dir . 'bbpress/bbpress.php' );

	// Instantiate bbPress for VGSR
	vgsr()->extend->bbp = new VGSR_BBPress;
}

/**
 * Loads the BuddyPress component
 * 
 * @since 0.0.2
 *
 * @return When BuddyPress is not active
 */
function vgsr_setup_buddypress() {

	// Bail if no BuddyPress
	if ( ! function_exists( 'buddypress' ) )
		return;

	// Include the BuddyPress component
	require( vgsr()->extend_dir . 'buddypress/buddypress.php' );

	// Instantiate BuddyPress for VGSR
	vgsr()->extend->bp = new VGSR_BuddyPress;
}

/**
 * Loads the Event Organiser component
 * 
 * @since 1.0.0
 *
 * @return When Event Organiser is not active
 */
function vgsr_setup_event_organiser() {

	// Bail if no Event Organiser
	if ( ! defined( 'EVENT_ORGANISER_VER' ) )
		return;

	// Include the Event Organiser component
	require( vgsr()->extend_dir . 'event-organiser/event-organiser.php' );

	// Instantiate Event Organiser for VGSR
	vgsr()->extend->event_organiser = new VGSR_Event_Organiser;
}

/**
 * Loads the Gravity Forms component
 * 
 * @since 0.0.6
 *
 * @return When Gravity Forms is not active
 */
function vgsr_setup_gravityforms() {

	// Bail if no Gravity Forms
	if ( ! class_exists( 'GFForms' ) )
		return;

	// Include the Gravity Forms component
	require( vgsr()->extend_dir . 'gravityforms/gravityforms.php' );

	// Instantiate Gravity Forms for VGSR
	vgsr()->extend->gf = new VGSR_GravityForms;
}

/**
 * Loads the WordPress SEO component
 * 
 * @since 0.1.0
 *
 * @return When WordPress SEO is not active
 */
function vgsr_setup_wpseo() {

	// Bail if no WordPress SEO
	if ( ! defined( 'WPSEO_VERSION' ) )
		return;

	// Include the WordPress SEO component
	require( vgsr()->extend_dir . 'wordpress-seo/wordpress-seo.php' );

	// Instantiate WordPress SEO for VGSR
	vgsr()->extend->wpseo = new VGSR_WPSEO;
}
