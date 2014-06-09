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
if ( ! defined( 'ABSPATH' ) ) exit;

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
add_filter( 'request',                 'vgsr_request',            10    );
add_filter( 'map_meta_cap',            'vgsr_map_meta_caps',      10, 4 );

// Posts
add_filter( 'vgsr_request', 'vgsr_filter_vgsr_posts' );