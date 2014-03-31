<?php

/**
 * VGSR User Functions
 *
 * @package VGSR
 * @subpackage User
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Groups ****************************************************************/

/**
 * Return whether a given user is in the VGSR group
 *
 * @since 1.0.0
 *
 * @uses vgsr_is_groupz_active()
 * @uses groupz_user_in_group()
 * @uses vgsr_get_group_vgsr()
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is VGSR
 */
function user_is_vgsr( $user_id = 0 ) {
	$user_id = (int) $user_id;
	$is      = false;

	// Check only if Groupz is active
	if ( vgsr_is_groupz_active() ) {

		// Default to current user
		if ( empty( $user_id ) )
			$user_id = get_current_user_id();

		// Find user in group
		$is = groupz_user_in_group( vgsr_get_group_vgsr(), $user_id );
	}

	return (bool) apply_filters( 'vgsr_user_is_vgsr', $is, $user_id );
}

/**
 * Return whether a given user is in the leden group
 *
 * @since 1.0.0
 *
 * @uses vgsr_is_groupz_active()
 * @uses groupz_user_in_group()
 * @uses vgsr_get_group_leden()
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is lid
 */
function user_is_lid( $user_id = 0 ) {
	$user_id = (int) $user_id;
	$is      = false;

	// Check only if Groupz is active
	if ( vgsr_is_groupz_active() ) {

		// Default to current user
		if ( empty( $user_id ) )
			$user_id = get_current_user_id();

		// Find user in group
		$is = groupz_user_in_group( vgsr_get_group_leden(), $user_id );
	}

	return (bool) apply_filters( 'vgsr_user_is_lid', $is, $user_id );
}

/**
 * Return whether a given user is in the oud-leden group
 *
 * @since 1.0.0
 *
 * @uses vgsr_is_groupz_active()
 * @uses groupz_user_in_group()
 * @uses vgsr_get_group_oudleden()
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is oud-lid
 */
function user_is_oudlid( $user_id = 0 ) {
	$user_id = (int) $user_id;
	$is      = false;

	// Check only if Groupz is active
	if ( vgsr_is_groupz_active() ) {

		// Default to current user
		if ( empty( $user_id ) )
			$user_id = get_current_user_id();

		// Find user in group
		$is = groupz_user_in_group( vgsr_get_group_oudleden(), $user_id );
	}

	return (bool) apply_filters( 'vgsr_user_is_oudlid', $is, $user_id );
}

