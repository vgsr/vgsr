<?php

/**
 * VGSR Extensions
 *
 * @package VGSR
 * @subpackage Extend
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Extend *****************************************************************/

/**
 * Loads the bbPress component
 * 
 * @since 1.0.0
 *
 * @return If bbPress is not active
 */
function vgsr_setup_bbpress() {

	// Bail if no bbPress
	if ( ! function_exists( 'bbpress' ) )
		return;

	// Include the bbPress component
	require( vgsr()->includes_dir . 'extend/bbpress/bbpress.php' );

	// Instantiate bbPress for VGSR
	vgsr()->extend->bbp = new VGSR_BBPress;
}

/**
 * Loads the BuddyPress component
 * 
 * @since 1.0.0
 *
 * @return If BuddyPress is not active
 */
function vgsr_setup_buddypress() {

	// Bail if no BuddyPress
	if ( ! function_exists( 'buddypress' ) )
		return;

	// Include the BuddyPress component
	require( vgsr()->includes_dir . 'extend/buddypress/buddypress.php' );

	// Instantiate BuddyPress for VGSR
	vgsr()->extend->bp = new VGSR_BudddyPress;
}

/**
 * Loads the Groupz component
 * 
 * @since 1.0.0
 *
 * @return If Groupz is not active
 */
function vgsr_setup_groupz() {

	// Bail if no Groupz
	if ( ! function_exists( 'groupz' ) )
		return;

	// Include the Groupz component
	require( vgsr()->includes_dir . 'extend/groupz/groupz.php' );

	// Instantiate Groupz for VGSR
	vgsr()->extend->groupz = new VGSR_Groupz;
}

