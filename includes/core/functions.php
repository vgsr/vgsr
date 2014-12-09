<?php

/**
 * VGSR Core Functions
 *
 * @package VGSR
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Stuff ******************************************************************/

/**
 * Return whether VGSR has a network context
 *
 * @since 0.0.7
 *
 * @uses vgsr()
 * @uses is_plugin_active_for_network()
 * @return bool This is a network context
 */
function vgsr_is_network_context() {
	$vgsr = vgsr();

	// Define variable first
	if ( null === $vgsr->network ) {

		// Load file to use its functions
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// Bail when plugin is not network activated
		$vgsr->network = is_plugin_active_for_network( $vgsr->basename );
	}

	return $vgsr->network;
}
