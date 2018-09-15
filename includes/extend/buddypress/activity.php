<?php

/**
 * VGSR BuddyPress Activity Functions
 * 
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Modify the arguments of 'has_activities' after parsing defaults to
 * bring activity comments into the stream.
 *
 * @since 0.1.0
 *
 * @param array $args Parsed args
 * @return array Parsed args
 */
function vgsr_bp_activity_comments_in_stream( $args ) {

	// Force activity comment items to be displayed in the stream. The
	// default is 'threaded', which stacks comments to their parent item.
	$args['display_comments'] = 'stream';

	return $args;
}

/**
 * Modify the activity action displayed for custom post type posts
 *
 * @since 0.1.0
 *
 * @see bp_activity_format_activity_action_custom_post_type_post()
 * @see bp_blogs_format_activity_action_new_blog_post()
 *
 * @param string $action Action string value
 * @param BP_Activity_Activity $activity Activity data object
 * @return string Action
 */
function vgsr_bp_activity_post_type_post_action( $action, $activity ) {

	// Remove the 'on the site {$site}' part when the post is displayed on the
	// site it was actually written on.
	if ( is_multisite() && get_current_blog_id() === (int) $activity->item_id ) {
		$bp = buddypress();

		// Get post object
		$post_id = (int) $activity->secondary_item_id;
		$post    = get_post( $post_id );

		// Get post link and title
		$post_url   = get_permalink( $post->ID );
		$post_title = get_the_title( $post->ID );

		// Default to the registered primary link
		if ( empty( $post_url ) ) {
			$post_url = $activity->primary_link;
		}

		// Default to registered post title
		if ( empty( $post_title ) ) {

			// Should be the case when the comment has just been published
			if ( isset( $activity->post_title ) ) {
				$post_title = $activity->post_title;

			// If activity already exists try to get the post title from activity meta
			} elseif ( ! empty( $activity->id ) ) {
				$post_title = bp_activity_get_meta( $activity->id, 'post_title' );
			}
		}

		// Define action parameters
		$post_link = '<a href="' . esc_url( $post_url ) . '">' . $post_title . '</a>';
		$user_link = bp_core_get_userlink( $activity->user_id );

		// Get post types tracking args
		if ( empty( $bp->activity->track ) ) {
			$bp->activity->track = bp_activity_get_post_types_tracking_args();
		}

		// Get action post type tracking details
		$track = $bp->activity->track[ $activity->type ];

		// Use the single site equivalent when there is an explicit post type action set
		if ( ! empty( $track->new_post_type_action_ms ) && ! empty( $track->new_post_type_action ) ) {
			$action = sprintf( $track->new_post_type_action, $user_link, esc_url( $post_url ) );

		/**
		 * Object is a 'post'.
		 * See {@link bp_blogs_format_activity_action_new_blog_post()}.
		 */
		} elseif ( 'post' == $post->post_type ) {
			$action = sprintf( __( '%1$s wrote a new post, %2$s', 'buddypress' ), $user_link, $post_link );

		/**
		 * Default to 'item'.
		 * See {@link bp_activity_format_activity_action_custom_post_type_post()}.
		 */
		} else {
			$action = sprintf( _x( '%1$s wrote a new <a href="%2$s">item</a>', 'Activity Custom Post Type post action', 'buddypress' ), $user_link, esc_url( $post_url ) );
		}
	}

	return $action;
}

/**
 * Modify the activity action displayed for custom post type comments
 *
 * @since 0.1.0
 *
 * @see bp_activity_format_activity_action_custom_post_type_comment()
 * @see bp_blogs_format_activity_action_new_blog_comment()
 *
 * @param string $action Action string value
 * @param BP_Activity_Activity $activity Activity data object
 * @return string Action
 */
function vgsr_bp_activity_post_type_comment_action( $action, $activity ) {

	// Remove the 'on the site {$site}' part when the comment is displayed on the
	// site it was actually written on.
	if ( is_multisite() && get_current_blog_id() === (int) $activity->item_id ) {
		$bp = buddypress();

		// Get comment and post objects
		$comment_id = (int) $activity->secondary_item_id;
		$comment    = get_comment( $comment_id );
		$post       = get_post( $comment->comment_post_ID );

		// Get post link and title
		$post_url   = get_permalink( $post->ID );
		$post_title = get_the_title( $post->ID );

		// Default to the registered primary link
		if ( empty( $post_url ) ) {
			$post_url = $activity->primary_link;
		}

		// Default to registered post title
		if ( empty( $post_title ) ) {

			// Should be the case when the comment has just been published
			if ( isset( $activity->post_title ) ) {
				$post_title = $activity->post_title;

			// If activity already exists try to get the post title from activity meta
			} elseif ( ! empty( $activity->id ) ) {
				$post_title = bp_activity_get_meta( $activity->id, 'post_title' );
			}
		}

		// Define action parameters
		$post_link = '<a href="' . esc_url( $post_url ) . '">' . $post_title . '</a>';
		$user_link = bp_core_get_userlink( $activity->user_id );

		// Get post types tracking args
		if ( empty( $bp->activity->track ) ) {
			$bp->activity->track = bp_activity_get_post_types_tracking_args();
		}

		// Get action post type tracking details
		$track = $bp->activity->track[ $activity->type ];

		// Use the single site equivalent when there is an explicit post type action set
		if ( ! empty( $track->new_post_type_comment_action_ms ) && ! empty( $track->new_post_type_comment_action ) ) {
			$action = sprintf( $track->new_post_type_comment_action, $user_link, esc_url( $post_url ) );

		/**
		 * Object is a 'post'.
		 * See {@link bp_blogs_format_activity_action_new_blog_comment()}.
		 */
		} elseif ( 'post' == $post->post_type ) {
			$action = sprintf( __( '%1$s commented on the post, %2$s', 'buddypress' ), $user_link, $post_link );

		/**
		 * Default to 'item'.
		 * See {@link bp_activity_format_activity_action_custom_post_type_comment()}.
		 */
		} else {
			$action = sprintf( _x( '%1$s commented on the <a href="%2$s">item</a>', 'Activity Custom Post Type post comment action', 'buddypress' ), $user_link, esc_url( $post_url ) );
		}
	}

	return $action;
}

/**
 * Modify whether the activity item can be commented on
 *
 * @since 1.0.0
 *
 * @param bool $can_comment Whether the item can be commented on
 * @param string $activity_type Activity type name
 * @return bool Whether the item can be commented on
 */
function vgsr_bp_activity_can_comment( $can_comment, $activity_type ) {

	// Define disabled actions
	$disabled_actions = array(
		'new_avatar',           // Updated profile photos
		'updated_profile',      // Profile updates
		'updated_profile_field' // Plugin BP_XProfile_Field_Activity
	);

	// Check if this activity stream action is disabled
	if ( in_array( $activity_type, $disabled_actions ) ) {
		$can_comment = false;
	}

	return $can_comment;
}

/**
 * Modify whether the activity item can be favorited
 *
 * @since 1.0.0
 *
 * @param bool $can_favorite Whether the item can be favorited
 * @return bool Whether the item can be favorited
 */
function vgsr_bp_activity_can_favorite( $can_favorite ) {

	// Bail when we're not in the activities template loop
	if ( ! isset( $GLOBALS['activities_template'] ) || ! is_a( $GLOBALS['activities_template'], 'BP_Activity_Template' ) ) {
		return $can_favorite;
	}

	// Define disabled actions
	$disabled_actions = array(
		'new_avatar',           // Updated profile photos
		'updated_profile',      // Profile updates
		'updated_profile_field' // Plugin BP_XProfile_Field_Activity
	);

	// Check if this activity stream action is disabled
	if ( in_array( bp_get_activity_type(), $disabled_actions ) ) {
		$can_favorite = false;
	}

	return $can_favorite;
}
