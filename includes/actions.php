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
 *  - Admin: More in admin/actions.php
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
 *           v--WordPress Actions     v--VGSR Sub-actions
 */
add_filter( 'request',               'vgsr_request',           10    );
add_action( 'plugins_loaded',        'vgsr_loaded',            20    );
add_action( 'init',                  'vgsr_init',               0    ); // Early for vgsr_register
add_action( 'add_admin_bar_menus',   'vgsr_admin_bar_menus'          );
add_action( 'wp_head',               'vgsr_head'                     );
add_filter( 'map_meta_cap',          'vgsr_map_meta_caps',     10, 4 );
add_action( 'wp_footer',             'vgsr_footer'                   );

/**
 * vgsr_init - Attached to 'init' above
 *
 * Attach various initialization actions to the init action.
 * The load order helps to execute code at the correct time.
 *                                                   v---Load order
 */
add_action( 'vgsr_init', 'vgsr_load_textdomain',     0   );
add_action( 'vgsr_init', 'vgsr_register',            0   );
add_action( 'vgsr_init', 'vgsr_manifest_json_route', 10  );
add_action( 'vgsr_init', 'vgsr_ready',               999 );

// Initialize the admin area
if ( is_admin() ) {
	add_action( 'vgsr_init', 'vgsr_admin' );
}

// Login
add_action( 'login_enqueue_scripts', 'vgsr_login_enqueue_scripts' );
add_filter( 'login_headerurl',       'get_home_url'               );
add_filter( 'login_headertitle',     'vgsr_login_header_title'    );

// Manifest
add_action( 'vgsr_head', 'vgsr_manifest_meta_tag' );

// Users
add_action( 'pre_user_query',         'vgsr_pre_user_query'             );
add_filter( 'wp_dropdown_users_args', 'vgsr_dropdown_users_args', 20, 2 ); // Since WP 4.4

// Categories
add_filter( 'the_category_list', 'vgsr_the_category_list', 10, 2 );
add_filter( 'the_category',      'vgsr_the_category'             );

// Comments
add_filter( 'pre_comment_approved', 'vgsr_pre_comment_approved', 20, 2 );

/**
 * Post exclusivity
 */
add_action( 'pre_get_posts',           '_vgsr_post_query'                    );
add_action( 'vgsr_register',           '_vgsr_post_update_hierarchy',   0    );
add_action( 'save_post',               '_vgsr_post_update_hierarchy'         );
// Query filters --v
add_filter( 'vgsr_request',            '_vgsr_post_query'                    );
add_filter( 'getarchives_where',       '_vgsr_post_get_archives',      10, 2 );
add_filter( 'get_next_post_where',     '_vgsr_post_get_adjacent_post'        );
add_filter( 'get_previous_post_where', '_vgsr_post_get_adjacent_post'        );
add_filter( 'list_pages',              '_vgsr_post_list_pages',        10, 2 );
add_filter( 'get_pages',               '_vgsr_post_get_pages',         10, 2 );
add_filter( 'wp_nav_menu_objects',     '_vgsr_post_nav_menu_objects',  10, 2 );
add_filter( 'comments_clauses',        '_vgsr_post_comment_query',     10, 2 );
add_filter( 'comment_feed_where',      '_vgsr_post_comment_query',     10, 2 );

/**
 * Extensions
 *
 * Attach actions to the ready action after VGSR has fully initialized
 * or at their plugin's loader hooks, so they fire at the right time.
 */
add_action( 'vgsr_ready',     'vgsr_setup_ancienniteit',    10 ); // Ancienniteit for users
add_action( 'bbp_loaded',     'vgsr_setup_bbpress',         0  ); // Forum integration
add_action( 'bp_core_loaded', 'vgsr_setup_buddypress',      10 ); // Social network integration
add_action( 'vgsr_ready',     'vgsr_setup_event_organiser', 10 ); // Events integration
add_action( 'vgsr_ready',     'vgsr_setup_gravityforms',    10 ); // Forms integration
add_action( 'vgsr_ready',     'vgsr_setup_wpseo',           10 ); // SEO integration
