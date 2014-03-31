<?php

/**
 * VGSR Actions
 *
 * @package VGSR
 * @subpackage Core
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Main Actions **********************************************************/

// Extensions
add_action( 'init', 'vgsr_setup_bbpress' );
add_action( 'init', 'vgsr_setup_groupz'  );

if ( is_admin() ) {
	add_action ( 'init', 'vgsr_admin' );
}

/** Activation Actions ****************************************************/

/**
 * Runs on VGSR activation
 *
 * @since 1.0.0
 * 
 * @uses do_action() Calls 'vgsr_activation' hook
 */
function vgsr_activation() {
	do_action( 'vgsr_activation' );
}

/**
 * Runs on VGSR deactivation
 *
 * @since 1.0.0
 * 
 * @uses do_action() Calls 'vgsr_deactivation' hook
 */
function vgsr_deactivation() {
	do_action( 'vgsr_deactivation' );
}

/**
 * Runs when uninstalling VGSR
 *
 * @since 1.0.0
 * 
 * @uses do_action() Calls 'vgsr_uninstall' hook
 */
function vgsr_uninstall() {
	do_action( 'vgsr_uninstall' );
}

