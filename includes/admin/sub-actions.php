<?php

/**
 * VGSR Admin Sub-actions
 *
 * @package VGSR
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Piggy back admin_init action
 * 
 * @since 0.0.1
 *
 * @uses do_action() Calls 'vgsr_admin_init'
 */
function vgsr_admin_init() {
	do_action( 'vgsr_admin_init' );
}

/**
 * Piggy back admin_menu action
 * 
 * @since 0.0.1
 *
 * @uses do_action() Calls 'vgsr_admin_menu'
 */
function vgsr_admin_menu() {
	do_action( 'vgsr_admin_menu' );
}

/**
 * Piggy back admin_head action
 * 
 * @since 0.0.1
 *
 * @uses do_action() Calls 'vgsr_admin_head'
 */
function vgsr_admin_head() {
	do_action( 'vgsr_admin_head' );
}

/**
 * Piggy back admin_footer action
 * 
 * @since 0.0.1
 *
 * @uses do_action() Calls 'vgsr_admin_footer'
 */
function vgsr_admin_footer() {
	do_action( 'vgsr_admin_footer' );
}

/**
 * Piggy back admin_notices action
 * 
 * @since 0.0.1
 *
 * @uses do_action() Calls 'vgsr_admin_notices'
 */
function vgsr_admin_notices() {
	do_action( 'vgsr_admin_notices' );
}

/**
 * Dedicated action to register admin settings
 * 
 * @since 0.0.1
 *
 * @uses do_action() Calls 'vgsr_register_admin_settings'
 */
function vgsr_register_admin_settings() {
	do_action( 'vgsr_register_admin_settings' );
}

/**
 * Register a dedicated hook on admin page load
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_load_admin_page'
 */
function vgsr_load_admin_page() {
	do_action( 'vgsr_load_admin_page' );
}

/**
 * Register a dedicated hook in the admin page head
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_admin_page_head'
 */
function vgsr_admin_page_head() {
	do_action( 'vgsr_admin_page_head' );
}

/**
 * Register a dedicated hook in the admin page footer
 *
 * @since 0.1.0
 *
 * @uses do_action() Calls 'vgsr_admin_page_footer'
 */
function vgsr_admin_page_footer() {
	do_action( 'vgsr_admin_page_footer' );
}
