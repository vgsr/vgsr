<?php

/**
 * VGSR BuddyPress Settings Functions
 *
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Settings ***************************************************************/

/**
 * Add settings sections for BuddyPress options
 *
 * @since 0.0.1
 * 
 * @param array $sections Settings sections
 * @return array Settings sections
 */
function vgsr_bp_settings_sections( $sections = array() ) {
	return $sections;
}

/**
 * Add settings fields for BuddyPress options
 *
 * @since 0.0.1
 * 
 * @param array $fields Settings fields
 * @return array Settings fields
 */
function vgsr_bp_settings_fields( $fields = array() ) {
	return $fields;
}
