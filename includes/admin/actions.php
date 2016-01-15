<?php

/**
 * VGSR Admin Actions
 *
 * @package VGSR
 * @subpackage Admin
 *
 * This file contains the actions that are used through-out VGSR Admin. They
 * are consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional actions can currently be found
 *
 *  - VGSR: In {@link VGSR::setup_actions()} in vgsr.php
 *  - Admin: More in {@link VGSR_Admin::setup_actions()} in admin.php
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Attach VGSR to WordPress
 *
 * VGSR uses its own internal actions to help aid in third-party plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress core occur.
 *
 * These actions exist to create the concept of 'plugin dependencies'. They
 * provide a safe way for plugins to execute code *only* when VGSR is
 * installed and activated, without needing to do complicated guesswork.
 *
 * For more information on how this works, see the 'Plugin Dependency' section
 * near the bottom of this file.
 *
 *           v--WordPress Actions       v--VGSR Sub-actions
 */
add_action( 'admin_menu',              'vgsr_admin_menu'      );
add_action( 'admin_init',              'vgsr_admin_init'      );
add_action( 'admin_head',              'vgsr_admin_head'      );
add_action( 'admin_footer',            'vgsr_admin_footer'    );
add_action( 'admin_notices',           'vgsr_admin_notices'   );

// Hook on to admin_init
add_action( 'vgsr_admin_init', 'vgsr_register_admin_settings' );

// Initialize the admin area
add_action( 'vgsr_init', 'vgsr_admin' );

// Posts - Exclusivity
add_action( 'post_submitbox_misc_actions', 'vgsr_is_post_vgsr_meta'             );
add_action( 'save_post',                   'vgsr_is_post_vgsr_meta_save'        );
add_action( 'quick_edit_custom_box',       'vgsr_post_vgsr_quick_edit',   10, 2 );

/** Sub-Actions ***************************************************************/

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
