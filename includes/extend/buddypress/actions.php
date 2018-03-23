<?php

/**
 * VGSR BuddyPress Actions
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

// Members
add_action( 'bp_members_directory_member_types',      'vgsr_bp_members_directory_tabs'            );
add_action( 'bp_before_directory_members_tabs',       'vgsr_bp_add_member_count_filter',    99    );
add_action( 'bp_members_directory_member_types',      'vgsr_bp_remove_member_count_filter',  0    );
add_filter( 'bp_legacy_theme_ajax_querystring',       'vgsr_bp_legacy_ajax_querystring',    10, 7 );
add_filter( 'bp_members_pagination_count',            'vgsr_bp_members_pagination_count'          );
add_filter( 'bp_get_current_member_type',             '__return_false'                            );
add_filter( 'bp_get_member_type_directory_permalink', '__return_false'                            );
add_action( 'bp_member_header_actions',               'vgsr_bp_add_member_header_actions'         );

// Plugin Settings
add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_bp_settings_sections' );
add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_bp_settings_fields'   );
