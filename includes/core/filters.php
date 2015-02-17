<?php

/**
 * VGSR Filters
 *
 * @package VGSR
 * @subpackage Core
 *
 * This file contains the filters that are used through-out VGSR. They are
 * consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional filters can currently be found
 *
 *  - VGSR: In {@link VGSR::setup_actions()} in fiscaat.php
 *  - Admin: More in {@link VGSR_Admin::setup_actions()} in admin.php
 *
 * @see /core/actions.php
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
add_filter( 'request',                 'vgsr_request',                 10    );
add_filter( 'map_meta_cap',            'vgsr_map_meta_caps',           10, 4 );

// Login Page
add_filter( 'login_headerurl',   'vgsr_login_header_url'   );
add_filter( 'login_headertitle', 'vgsr_login_header_title' );

/**
 * VGSR-only posts
 */

// wp-includes/class-wp.php
add_filter( 'vgsr_request',            '_vgsr_only_post_query'               );
// wp-includes/comment.php
add_filter( 'comments_clauses',        '_vgsr_only_comment_query',     10, 2 );
// wp-includes/general-template.php
add_filter( 'getarchives_where',       '_vgsr_only_get_archives',      10, 2 );
// wp-includes/link-template.php
add_filter( 'get_next_post_where',     '_vgsr_only_get_adjacent_post'        );
add_filter( 'get_previous_post_where', '_vgsr_only_get_adjacent_post'        );
// wp-includes/post-template.php
add_filter( 'list_pages',              '_vgsr_only_list_pages',        10, 2 );
// wp-includes/post.php
add_filter( 'get_pages',               '_vgsr_only_get_pages',         10, 2 );
// wp-includes/nav-menu-template.php
add_filter( 'wp_nav_menu_objects',     '_vgsr_only_nav_menu_objects',  10, 2 );
// wp-includes/query.php
add_filter( 'comment_feed_where',      '_vgsr_only_comment_query',     10, 2 );
