<?php

/**
 * VGSR Admin Actions
 *
 * @package VGSR
 * @subpackage Administration
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
add_action( vgsr_admin_menu_hook(),    'vgsr_admin_menu'      );
add_action( 'admin_init',              'vgsr_admin_init'      );
add_action( 'admin_head',              'vgsr_admin_head'      );
add_action( 'admin_footer',            'vgsr_admin_footer'    );
add_action( 'admin_notices',           'vgsr_admin_notices'   );

// Hook on to admin_init
add_action( 'vgsr_admin_init', 'vgsr_register_admin_settings'      );
add_action( 'vgsr_admin_init', 'vgsr_admin_settings_save',     100 );

// Posts - Exclusivity
add_action( 'post_submitbox_misc_actions', 'vgsr_is_post_vgsr_meta'             );
add_action( 'save_post',                   'vgsr_is_post_vgsr_meta_save'        );
add_action( 'quick_edit_custom_box',       'vgsr_post_vgsr_quick_edit',   10, 2 );
