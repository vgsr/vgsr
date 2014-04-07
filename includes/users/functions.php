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
 * Return the vgsr group ID
 *
 * @since 1.0.0
 *
 * @return int VGSR group ID
 */
function vgsr_get_group_vgsr_id() {
	return (int) apply_filters( 'vgsr_get_group_vgsr_id', 0 ) );
}

/**
 * Return the leden group ID
 *
 * @since 1.0.0
 *
 * @return int Leden group ID
 */
function vgsr_get_group_leden_id() {
	return (int) apply_filters( 'vgsr_get_group_leden_id', 0 ) );
}

/**
 * Return the oud-leden group ID
 *
 * @since 1.0.0
 *
 * @return int Oud-leden group ID
 */
function vgsr_get_group_oudleden_id() {
	return (int) apply_filters( 'vgsr_get_group_oudleden_id', 0 ) );
}

/**
 * Abstraction function to check user group membership
 *
 * @since 1.0.0
 * 
 * @uses apply_filters() Calls 'vgsr_user_in_group' with the
 *                        group ID and user ID
 *
 * @param int $group_id Group ID
 * @param int $user_id User ID. Defaults to current user
 * @return bool User is in group
 */
function vgsr_user_in_group( $group_id = 0, $user_id = 0 ) {
	$user_id = (int) $user_id;

	// Default to current user
	if ( empty( $user_id ) )
		$user_id = get_current_user_id();

	$in_group = apply_filters( 'vgsr_user_in_group', $group_id, $user_id );

	// No callback filter was run
	if ( $in_group === $group_id )
		$in_group = false;

	return (bool) $in_group;
}

/**
 * Return whether a given user is in the VGSR group
 *
 * @since 1.0.0
 *
 * @uses vgsr_user_in_group()
 * @uses vgsr_get_group_vgsr_id()
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is VGSR
 */
function user_is_vgsr( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) )
		$user_id = get_current_user_id();

	$is = vgsr_user_in_group( vgsr_get_group_vgsr_id(), $user_id );

	return (bool) apply_filters( 'user_is_vgsr', $is, $user_id );
}

/**
 * Return whether a given user is in the leden group
 *
 * @since 1.0.0
 *
 * @uses vgsr_user_in_group()
 * @uses vgsr_get_group_leden_id()
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is lid
 */
function user_is_lid( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) )
		$user_id = get_current_user_id();

	$is = vgsr_user_in_group( vgsr_get_group_leden_id(), $user_id );

	return (bool) apply_filters( 'user_is_lid', $is, $user_id );
}

/**
 * Return whether a given user is in the oud-leden group
 *
 * @since 1.0.0
 *
 * @uses vgsr_user_in_group()
 * @uses vgsr_get_group_oudleden_id()
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is oud-lid
 */
function user_is_oudlid( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) )
		$user_id = get_current_user_id();

	$is = vgsr_user_in_group( vgsr_get_group_oudleden_id(), $user_id );

	return (bool) apply_filters( 'user_is_oudlid', $is, $user_id );
}

