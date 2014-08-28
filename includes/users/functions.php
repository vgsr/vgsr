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
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_get_group_vgsr_id'
 * @return int VGSR group ID
 */
function vgsr_get_group_vgsr_id() {
	return (int) apply_filters( 'vgsr_get_group_vgsr_id', 0 );
}

/**
 * Return the leden group ID
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_get_group_leden_id'
 * @return int Leden group ID
 */
function vgsr_get_group_leden_id() {
	return (int) apply_filters( 'vgsr_get_group_leden_id', 0 );
}

/**
 * Return the oud-leden group ID
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_get_group_oudleden_id'
 * @return int Oud-leden group ID
 */
function vgsr_get_group_oudleden_id() {
	return (int) apply_filters( 'vgsr_get_group_oudleden_id', 0 );
}

/**
 * Return IDs of all VGSR groups
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_get_vgsr_groups'
 * @return array VGSR groups IDs
 */
function vgsr_get_vgsr_groups() {
	return (array) array_map( 'intval', apply_filters( 'vgsr_get_vgsr_groups', array(
		vgsr_get_group_vgsr_id(),
		vgsr_get_group_leden_id(),
		vgsr_get_group_oudleden_id(),
	) ) );
}

/** Is Functions **********************************************************/

/**
 * Return whether a given user is in any VGSR group
 *
 * @since 0.0.1
 *
 * @uses vgsr_user_in_group()
 * @uses vgsr_get_group_vgsr_id()
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is VGSR
 */
function is_user_vgsr( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) )
		$user_id = get_current_user_id();

	$is = vgsr_user_in_group( vgsr_get_group_vgsr_id(), $user_id );

	return (bool) apply_filters( 'is_user_vgsr', $is, $user_id );
}

/**
 * Return whether a given user is in the leden group
 *
 * @since 0.0.1
 *
 * @uses vgsr_user_in_group()
 * @uses vgsr_get_group_leden_id()
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is lid
 */
function is_user_lid( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) )
		$user_id = get_current_user_id();

	$is = vgsr_user_in_group( vgsr_get_group_leden_id(), $user_id );

	return (bool) apply_filters( 'is_user_lid', $is, $user_id );
}

/**
 * Return whether a given user is in the oud-leden group
 *
 * @since 0.0.1
 *
 * @uses vgsr_user_in_group()
 * @uses vgsr_get_group_oudleden_id()
 * @param int $user_id User ID. Defaults to current user
 * @return boolean User is oud-lid
 */
function is_user_oudlid( $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) )
		$user_id = get_current_user_id();

	$is = vgsr_user_in_group( vgsr_get_group_oudleden_id(), $user_id );

	return (bool) apply_filters( 'is_user_oudlid', $is, $user_id );
}

/**
 * Abstraction function to check user group membership
 *
 * Group plugins hook in here to verify group membership
 * of the given user. When no external filters are added,
 * this function assumes no membership, returning False.
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_user_in_group' with the
 *                        membership, group ID and user ID
 *
 * @param int $group_id Group ID
 * @param int $user_id User ID. Defaults to current user
 * @return bool User is in group
 */
function vgsr_user_in_group( $group_id = 0, $user_id = 0 ) {

	// Default to current user
	if ( empty( $user_id ) )
		$user_id = get_current_user_id();

	// Assume no membership
	$is_member = false;

	return (bool) apply_filters( 'vgsr_user_in_group', $is_member, $group_id, $user_id );
}

/**
 * Return whether the given group is a VGSR group
 *
 * @since 0.0.3
 *
 * @uses vgsr_get_vgsr_groups()
 * @param int $group_id Group ID
 * @return bool Group is VGSR group
 */
function vgsr_is_vgsr_group( $group_id = 0 ) {

	// Does group id match any?
	$is = ! empty( $group_id ) && in_array( (int) $group_id, vgsr_get_vgsr_groups() );

	return apply_filters( 'vgsr_is_vgsr_group', $is, $group_id );
}
