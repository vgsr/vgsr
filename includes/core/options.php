<?php

/**
 * VGSR Options
 *
 * @package VGSR
 * @subpackage Options
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get the default site options and their values.
 * 
 * These option
 *
 * @since 0.0.1
 * 
 * @return array Filtered option names and values
 */
function vgsr_get_default_options() {

	// Default options
	return apply_filters( 'vgsr_get_default_options', array(

		/** DB Version ********************************************************/

		'_vgsr_db_version'             => vgsr()->db_version,

		/** Settings **********************************************************/

	) );
}

/**
 * Add default options
 *
 * Hooked to vgsr_activate, it is only called once when VGSR is activated.
 * This is non-destructive, so existing settings will not be overridden.
 *
 * @since 0.0.1
 * 
 * @uses vgsr_get_default_options() To get default options
 * @uses add_option() Adds default options
 * @uses do_action() Calls 'vgsr_add_options'
 */
function vgsr_add_options() {

	// Add default options
	foreach ( vgsr_get_default_options() as $key => $value )
		add_option( $key, $value );

	// Allow previously activated plugins to append their own options.
	do_action( 'vgsr_add_options' );
}

/**
 * Delete default options
 *
 * Hooked to vgsr_uninstall, it is only called once when VGSR is uninstalled.
 * This is destructive, so existing settings will be destroyed.
 *
 * @since 0.0.1
 * 
 * @uses vgsr_get_default_options() To get default options
 * @uses delete_option() Removes default options
 * @uses do_action() Calls 'vgsr_delete_options'
 */
function vgsr_delete_options() {

	// Add default options
	foreach ( array_keys( vgsr_get_default_options() ) as $key )
		delete_option( $key );

	// Allow previously activated plugins to append their own options.
	do_action( 'vgsr_delete_options' );
}

/**
 * Add filters to each VGSR option and allow them to be overloaded from
 * inside the $vgsr->options array.
 *
 * @since 0.0.1
 * 
 * @uses vgsr_get_default_options() To get default options
 * @uses add_filter() To add filters to 'pre_option_{$key}'
 * @uses do_action() Calls 'vgsr_add_option_filters'
 */
function vgsr_setup_option_filters() {

	// Add filters to each VGSR option
	foreach ( array_keys( vgsr_get_default_options() ) as $key )
		add_filter( 'pre_option_' . $key, 'vgsr_pre_get_option' );

	// Allow previously activated plugins to append their own options.
	do_action( 'vgsr_setup_option_filters' );
}

/**
 * Filter default options and allow them to be overloaded from inside the
 * $vgsr->options array.
 *
 * @since 0.0.1
 * 
 * @param bool $value Optional. Default value false
 * @return mixed false if not overloaded, mixed if set
 */
function vgsr_pre_get_option( $value = '' ) {

	// Remove the filter prefix
	$option = str_replace( 'pre_option_', '', current_filter() );

	// Check the options global for preset value
	if ( isset( vgsr()->options[$option] ) )
		$value = vgsr()->options[$option];

	// Always return a value, even if false
	return $value;
}

/** Active? *******************************************************************/

/**
 * Return the private reading post types option
 *
 * @since 0.0.6
 * 
 * @return array Post type names
 */
function vgsr_get_private_reading_post_types() {
	return (array) get_option( '_vgsr_private_reading_post_types', array() );
}
