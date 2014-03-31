<?php

/**
 * VGSR Core Functions
 *
 * @package VGSR
 * @subpackage Core
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Extend *****************************************************************/

/**
 * Return whether bbPress is active
 *
 * @since 1.0.0
 *
 * @uses function_exists()
 * @return bool bbPress is active
 */
function vgsr_is_bbpress_active() {
	return function_exists( 'bbpress' );
}

/**
 * Return whether Groupz is active
 *
 * @since 1.0.0
 *
 * @uses function_exists()
 * @return bool Groupz is active
 */
function vgsr_is_groupz_active() {
	return function_exists( 'groupz' );
}
