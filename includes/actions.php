<?php

/**
 * VGSR Actions
 *
 * @package VGSR
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Sub-actions ***************************************************************/

add_filter( 'request',                 'vgsr_request',                 10    );
add_action( 'plugins_loaded',          'vgsr_loaded',                  20    );
add_action( 'init',                    'vgsr_init',                     0    ); // Early for vgsr_register
add_action( 'widgets_init',            'vgsr_widgets_init',            10    );
add_action( 'add_admin_bar_menus',     'vgsr_admin_bar_menus',         10    );
add_action( 'wp_head',                 'vgsr_head',                    10    );
add_filter( 'map_meta_cap',            'vgsr_map_meta_caps',           10, 4 );
add_action( 'template_include',        'vgsr_template_include',        10    );
add_action( 'wp_footer',               'vgsr_footer',                  10    );

/** Init **********************************************************************/

add_action( 'vgsr_init',               'vgsr_load_textdomain',          0    );
add_action( 'vgsr_init',               'vgsr_register',                 0    );
add_action( 'vgsr_init',               'vgsr_manifest_json_route',     10    );
add_action( 'vgsr_init',               'vgsr_ready',                  999    );

/** Query *********************************************************************/

add_filter( 'posts_pre_query',         'vgsr_bypass_wp_query',         10, 2 ); // Since WP 4.6

add_action( 'pre_get_posts',           '_vgsr_post_query',             10    );
add_action( 'vgsr_register',           '_vgsr_post_update_hierarchy',   0    );
add_action( 'save_post',               '_vgsr_post_update_hierarchy',  10    );

// Query filters
add_filter( 'vgsr_request',            '_vgsr_post_query',             10    );
add_filter( 'getarchives_where',       '_vgsr_post_get_archives',      10, 2 );
add_filter( 'get_next_post_where',     '_vgsr_post_get_adjacent_post', 10    );
add_filter( 'get_previous_post_where', '_vgsr_post_get_adjacent_post', 10    );
add_filter( 'list_pages',              '_vgsr_post_list_pages',        10, 2 );
add_filter( 'get_pages',               '_vgsr_post_get_pages',         10, 2 );
add_filter( 'wp_nav_menu_objects',     '_vgsr_post_nav_menu_objects',  10, 2 );
add_filter( 'comments_clauses',        '_vgsr_post_comment_query',     10, 2 );
add_filter( 'comment_feed_where',      '_vgsr_post_comment_query',     10, 2 );

add_filter( 'get_next_post_join',      'vgsr_get_adjacent_post_join',  10, 5 );
add_filter( 'get_previous_post_join',  'vgsr_get_adjacent_post_join',  10, 5 );
add_filter( 'get_next_post_where',     'vgsr_get_adjacent_post_where', 10, 5 );
add_filter( 'get_previous_post_where', 'vgsr_get_adjacent_post_where', 10, 5 );

/** Login *********************************************************************/

add_action( 'login_init',              'vgsr_login_init',              10    );
add_action( 'login_enqueue_scripts',   'vgsr_login_enqueue_scripts',   10    );
add_filter( 'login_headerurl',         'get_home_url',                 10    );

/** Template ******************************************************************/

add_action( 'vgsr_template_include',   'vgsr_activate_theme_compat',   12    );
add_action( 'vgsr_head',               'vgsr_manifest_meta_tag',       10    );
add_filter( 'get_the_archive_title',   'vgsr_get_the_archive_title',   10    );

/** Users *********************************************************************/

add_action( 'pre_get_users',           'vgsr_pre_get_users',            5    );
add_action( 'pre_user_query',          'vgsr_pre_user_query',           5    );
add_filter( 'wp_dropdown_users_args',  'vgsr_dropdown_users_args',     20, 2 ); // Since WP 4.4

/** Nav Menus *****************************************************************/

add_filter( 'wp_setup_nav_menu_item',  'vgsr_setup_nav_menu_item',     10    );
add_filter( 'wp_nav_menu_objects',     'vgsr_nav_menu_objects',        10, 2 );

/** Taxonomy ******************************************************************/

add_filter( 'the_category_list',       'vgsr_the_category_list',       10, 2 );
add_filter( 'the_category',            'vgsr_the_category',            10    );

// Query
add_filter( 'get_terms_orderby',       'vgsr_get_terms_orderby_name__in', 10, 3 );

/** Comments ******************************************************************/

add_action( 'parse_comment_query',     'vgsr_parse_comment_query',     10    );
add_filter( 'pre_comment_approved',    'vgsr_pre_comment_approved',    20, 2 );

/** Admin *********************************************************************/

if ( is_admin() ) {
	add_action( 'vgsr_init', 'vgsr_admin', 10 );
}

/** Extend ********************************************************************/

add_action( 'bbp_loaded',              'vgsr_setup_bbpress',                  0    ); // Forum integration
add_action( 'bp_core_loaded',          'vgsr_setup_buddypress',              10    ); // Social network integration
add_action( 'vgsr_ready',              'vgsr_setup_event_organiser',         10    ); // Events integration
add_action( 'gform_loaded',            'vgsr_setup_gravityforms',            10    ); // Forms integration
add_action( 'vgsr_ready',              'vgsr_setup_initials_default_avatar', 10    );
add_action( 'vgsr_ready',              'vgsr_setup_responsive_lightbox',     10    ); // Lightbox integration
add_action( 'vgsr_ready',              'vgsr_setup_woosidebars',             10    ); // Widget areas integration
add_action( 'vgsr_ready',              'vgsr_setup_user_switching',          10    ); // User switching
add_action( 'vgsr_ready',              'vgsr_setup_wpseo',                   10    ); // SEO integration
