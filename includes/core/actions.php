<?php

/**
 * VGSR Actions
 *
 * @package VGSR
 * @subpackage Core
 *
 * This file contains the actions that are used through-out VGSR. They are
 * consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional actions can currently be found
 *
 *  - VGSR: In {@link VGSR::setup_actions()} in vgsr.php
 *  - Admin: More in {@link VGSR_Admin::setup_actions()} in admin.php
 *
 * @see /core/filters.php
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
 *           v--WordPress Actions    v--VGSR Sub-actions
 */
add_action( 'plugins_loaded',        'vgsr_loaded',           20 );
add_action( 'init',                  'vgsr_init',              0 ); // Early for vgsr_register
add_action( 'add_admin_bar_menus',   'vgsr_admin_bar_menu'       );
add_action( 'wp_head',               'vgsr_manifest_meta_tag'    );

/**
 * vgsr_loaded - Attached to 'plugins_loaded' above
 *
 * Attach various loader actions to the vgsr_loaded action.
 * The load order helps to execute code at the correct time.
 *                                                    v---Load order
 */
add_action( 'vgsr_loaded', 'vgsr_constants',          2  );
add_action( 'vgsr_loaded', 'vgsr_boot_strap_globals', 4  );
add_action( 'vgsr_loaded', 'vgsr_includes',           6  );
add_action( 'vgsr_loaded', 'vgsr_setup_globals',      8  );

/**
 * vgsr_init - Attached to 'init' above
 *
 * Attach various initialization actions to the init action.
 * The load order helps to execute code at the correct time.
 *                                                 v---Load order
 */
add_action( 'vgsr_init', 'vgsr_load_textdomain',     0   );
add_action( 'vgsr_init', 'vgsr_register',            0   );
add_action( 'vgsr_init', 'vgsr_manifest_json_route', 10  );
add_action( 'vgsr_init', 'vgsr_ready',               999 );

// Initialize the admin area
if ( is_admin() ) {
	add_action( 'vgsr_init', 'vgsr_admin' );
}

// Users
add_action( 'pre_user_query', 'vgsr_pre_user_query' );

/**
 * vgsr_ready - attached to end 'vgsr_init' above
 *
 * Attach actions to the ready action after VGSR has fully initialized.
 * The load order helps to execute code at the correct time.
 *                                                     v---Load order
 */
add_action( 'vgsr_ready',     'vgsr_setup_ancienniteit',    10 ); // Ancienniteit for users
add_action( 'bbp_loaded',     'vgsr_setup_bbpress',         0  ); // Forum integration
add_action( 'bp_core_loaded', 'vgsr_setup_buddypress',      10 ); // Social network integration
add_action( 'vgsr_ready',     'vgsr_setup_event_organiser', 10 ); // Events integration
add_action( 'vgsr_ready',     'vgsr_setup_gravityforms',    10 ); // Forms integration
add_action( 'vgsr_ready',     'vgsr_setup_wpseo',           10 ); // SEO integration

// Login page
add_action( 'login_enqueue_scripts', 'vgsr_login_enqueue_scripts' );

/**
 * Posts Exclusivity
 */
add_action( 'pre_get_posts', '_vgsr_post_query'               );
add_action( 'vgsr_register', '_vgsr_post_update_hierarchy', 0 );
add_action( 'save_post',     '_vgsr_post_update_hierarchy'    );
