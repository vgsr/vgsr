<?php

/**
 * VGSR Extensions
 *
 * @package VGSR
 * @subpackage Extend
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Loads the bbPress component
 * 
 * @since 1.0.0
 *
 * @return If bbPress is not active
 */
function vgsr_setup_bbpress() {

	// Bail if no bbPress
	if ( ! vgsr_is_bbpress_active() )
		return;

	// Include the bbPress component
	require( vgsr()->includes_dir . 'extend/bbpress/bbpress.php' );

	// Instantiate bbPress for VGSR
	vgsr()->extend->bbpress = new VGSR_BBPress;
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
	if ( ! vgsr_is_groupz_active() )
		return;

	// Include the Groupz component
	require( vgsr()->includes_dir . 'extend/groupz/groupz.php' );

	// Instantiate Groupz for VGSR
	vgsr()->extend->groupz = new VGSR_Groupz;
}

