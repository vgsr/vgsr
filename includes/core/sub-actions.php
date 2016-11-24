<?php

/**
 * Plugin Dependency
 *
 * The purpose of the following hooks is to mimic the behavior of something
 * called 'plugin dependency' which enables a plugin to have plugins of their
 * own in a safe and reliable way.
 *
 * We do this in VGSR by mirroring existing WordPress hooks in many places
 * allowing dependant plugins to hook into the VGSR specific ones, thus
 * guaranteeing proper code execution only when VGSR is active.
 *
 * The following functions are wrappers for hooks, allowing them to be
 * manually called and/or piggy-backed on top of other hooks if needed.
 */

/** Activation Actions ********************************************************/

/**
 * Runs on VGSR activation
 *
 * @since 0.1.0
 *
 * @uses register_uninstall_hook() To register our own uninstall hook
 * @uses do_action() Calls 'vgsr_activation' hook
 */
function vgsr_activation() {
	do_action( 'vgsr_activation' );
}

/**
 * Runs on VGSR deactivation
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_deactivation' hook
 */
function vgsr_deactivation() {
	do_action( 'vgsr_deactivation' );
}

/**
 * Runs when uninstalling VGSR
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_uninstall' hook
 */
function vgsr_uninstall() {
	do_action( 'vgsr_uninstall' );
}

/** Main Actions **************************************************************/

/**
 * Main action responsible for constants, globals, and includes
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_loaded'
 */
function vgsr_loaded() {
	do_action( 'vgsr_loaded' );
}

/**
 * Register any objects before anything is initialized
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_register'
 */
function vgsr_register() {
	do_action( 'vgsr_register' );
}

/**
 * Initialize any code after everything has been loaded
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_init'
 */
function vgsr_init() {
	do_action( 'vgsr_init' );
}

/**
 * Main action in the head of the site's front-end
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_head'
 */
function vgsr_head() {
	do_action( 'vgsr_head' );
}

/**
 * Main action in the footer of the site's front-end
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_footer'
 */
function vgsr_footer() {
	do_action( 'vgsr_footer' );
}

/** Supplemental Actions ******************************************************/

/**
 * Load translations for current language
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_load_textdomain'
 */
function vgsr_load_textdomain() {
	do_action( 'vgsr_load_textdomain' );
}

/** User Actions **************************************************************/

/**
 * The main action for hooking into when a user account is updated
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_profile_update'
 * @param int $user_id ID of user being edited
 * @param array $old_user_data The old, unmodified user data
 */
function vgsr_profile_update( $user_id = 0, $old_user_data = array() ) {
	do_action( 'vgsr_profile_update', $user_id, $old_user_data );
}

/** Final Action **************************************************************/

/**
 * VGSR has loaded and initialized everything, and is okay to go
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_ready'
 */
function vgsr_ready() {
	do_action( 'vgsr_ready' );
}

/** Filters *******************************************************************/

/**
 * Piggy back filter for WordPress's 'request' filter
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_request'
 *
 * @param array $query_vars Query vars
 * @return array Query vars
 */
function vgsr_request( $query_vars = array() ) {
	return apply_filters( 'vgsr_request', $query_vars );
}

/**
 * Maps caps to build in WordPress caps
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_map_meta_caps'
 *
 * @param array $caps Capabilities for meta capability
 * @param string $cap Capability name
 * @param int $user_id User id
 * @param mixed $args Arguments
 * @return array Caps for meta capability
 */
function vgsr_map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
	return apply_filters( 'vgsr_map_meta_caps', $caps, $cap, $user_id, $args );
}
