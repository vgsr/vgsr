<?php

/**
 * VGSR BuddyPress Filters
 * 
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Activity
add_filter( 'bp_after_has_activities_parse_args',               'vgsr_bp_activity_comments_in_stream'              );
add_filter( 'bp_activity_custom_post_type_post_action',         'vgsr_bp_activity_post_type_post_action',    10, 2 );
add_filter( 'bp_blogs_format_activity_action_new_blog_post',    'vgsr_bp_activity_post_type_post_action',    10, 2 );
add_filter( 'bp_activity_custom_post_type_comment_action',      'vgsr_bp_activity_post_type_comment_action', 10, 2 );
add_filter( 'bp_blogs_format_activity_action_new_blog_comment', 'vgsr_bp_activity_post_type_comment_action', 10, 2 );
