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
add_filter( 'bp_activity_can_comment',                          'vgsr_bp_activity_can_comment',              10, 2 );
add_filter( 'bp_activity_can_favorite',                         'vgsr_bp_activity_can_favorite',             10, 1 );

// Members
add_action( 'bp_members_directory_member_types',      'vgsr_bp_members_directory_tabs'            );
add_action( 'bp_members_directory_member_sub_types',  'vgsr_bp_members_jaargroep_filter'          );
add_action( 'bp_members_directory_order_options',     'vgsr_bp_members_directory_order_options'   );
add_action( 'bp_before_directory_members_tabs',       'vgsr_bp_add_member_count_filter',    99    );
add_action( 'bp_members_directory_member_types',      'vgsr_bp_remove_member_count_filter',  0    );
add_filter( 'bp_legacy_theme_ajax_querystring',       'vgsr_bp_legacy_ajax_querystring',    10, 7 );
add_filter( 'bp_after_has_members_parse_args',        'vgsr_bp_parse_has_members_args',     99    );
add_filter( 'bp_before_core_get_users_parse_args',    'vgsr_bp_parse_core_get_users_args',   1    );
add_filter( 'bp_members_pagination_count',            'vgsr_bp_members_pagination_count'          );
add_filter( 'bp_get_current_member_type',             '__return_false'                            );
add_filter( 'bp_get_member_type_directory_permalink', '__return_false'                            );
add_action( 'bp_directory_members_actions',           'vgsr_bp_add_directory_members_actions'     );
add_action( 'bp_member_header_actions',               'vgsr_bp_add_member_header_actions'         );
add_filter( 'bp_user_query_uid_clauses',              'vgsr_bp_user_query_uid_clauses',     10, 2 );

// XProfile
add_filter( 'bp_xprofile_get_field_types',    'vgsr_bp_xprofile_register_field_types'      );
add_filter( 'bp_xprofile_field_get_children', 'vgsr_bp_xprofile_field_get_children', 10, 3 );
add_action( 'xprofile_field_after_save',      'vgsr_bp_xprofile_save_field'                );
add_action( 'xprofile_data_after_save',       'vgsr_bp_xprofile_sync_field_to_meta'        );
add_action( 'updated_user_meta',              'vgsr_bp_xprofile_sync_meta_to_field', 10, 4 );

// Plugin Settings
add_filter( 'vgsr_admin_get_settings_sections', 'vgsr_bp_admin_settings_sections' );
add_filter( 'vgsr_admin_get_settings_fields',   'vgsr_bp_admin_settings_fields'   );
