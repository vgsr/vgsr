<?php

/**
 * VGSR BuddyPress Members Functions
 *
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Template ***************************************************************/

/**
 * Add additional query tabs to the Members directory
 *
 * @since 0.1.0
 */
function vgsr_bp_members_directory_tabs() {

	// When the user is vgsr
	if ( is_user_vgsr() ) {

		// Add tabs for Lid and Oud-lid member type
		vgsr_bp_members_member_type_tab( vgsr_bp_lid_member_type()    );
		vgsr_bp_members_member_type_tab( vgsr_bp_oudlid_member_type() );
	}

	// For admins
	if ( current_user_can( 'bp_moderate' ) ) {
		echo '<li id="members-all_profiles"><a href="'. bp_get_members_directory_permalink() . '">' . sprintf( __( 'All Profiles %s', 'vgsr' ), '<span>' . bp_get_total_member_count() . '</span>' ) . '</a></li>';
	}
}

/**
 * Add filter to modify the total member count
 *
 * @since 0.2.0
 */
function vgsr_bp_add_member_count_filter() {
	add_filter( 'bp_get_total_member_count', 'vgsr_bp_get_total_vgsr_member_count', 5 );
}

/**
 * Remove filter to modify the total member count
 *
 * @since 0.2.0
 */
function vgsr_bp_remove_member_count_filter() {
	remove_filter( 'bp_get_total_member_count', 'vgsr_bp_get_total_vgsr_member_count', 5 );
}

/**
 * Modify the ajax query string from the legacy template pack
 *
 * @since 0.1.0
 *
 * @param string $query_string        The query string we are working with.
 * @param string $object              The type of page we are on.
 * @param string $object_filter       The current object filter.
 * @param string $object_scope        The current object scope.
 * @param string $object_page         The current object page.
 * @param string $object_search_terms The current object search terms.
 * @param string $object_extras       The current object extras.
 * @return string The query string
 */
function vgsr_bp_legacy_ajax_querystring( $query_string, $object, $object_filter, $object_scope, $object_page, $object_search_terms, $object_extras ) {

	// Handle the members page queries
	if ( 'members' === $object ) {

		// Default scope All Members to all vgsr member types
		if ( 'all' === $object_scope ) {
			foreach ( array_keys( vgsr_bp_member_types() ) as $member_type ) {
				$query_string .= "&member_type__in[]={$member_type}";
			}

		// Single member type
		} elseif ( 0 === strpos( $object_scope, 'vgsr_member_type_' ) ) {
			$member_type   = str_replace( 'vgsr_member_type_', '', $object_scope );
			$query_string .= "&member_type__in={$member_type}";
		}
	}

	return $query_string;
}

/**
 * Return the query argument value from the current Members query
 *
 * @since 0.2.0
 *
 * @param string $arg Query arg key
 * @return mixed The current members query scope
 */
function vgsr_bp_members_get_query_arg( $arg = '' ) {

	// Get the current member query's args
	$query_vars = wp_parse_args( bp_ajax_querystring( 'members' ) );
	$scope = null;

	// Get the availabel argument value
	if ( isset( $query_vars[ $arg ] ) ) {
		$scope = $query_vars[ $arg ];
	}

	return $scope;
}

/**
 * Modify the pagination count of the members query
 *
 * @see bp_get_members_pagination_count()
 *
 * @since 0.2.0
 *
 * @param string $pag Members pagination count
 * @return string Members pagination count
 */
function vgsr_bp_members_pagination_count( $pag ) {

	// In the All Profiles scope
	if ( 'all_profiles' === vgsr_bp_members_get_query_arg( 'scope' ) ) {
		global $members_template;

		$start_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $start_num + ( $members_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $members_template->total_member_count );

		if ( 'active' == $members_template->type ) {
			if ( 1 == $members_template->total_member_count ) {
				$pag = __( 'Viewing 1 active profile', 'vgsr' );
			} else {
				$pag = sprintf( _n( 'Viewing %1$s - %2$s of %3$s active profile', 'Viewing %1$s - %2$s of %3$s active profiles', $members_template->total_member_count, 'vgsr' ), $from_num, $to_num, $total );
			}
		} elseif ( 'popular' == $members_template->type ) {
			if ( 1 == $members_template->total_member_count ) {
				$pag = __( 'Viewing 1 profile with friends', 'vgsr' );
			} else {
				$pag = sprintf( _n( 'Viewing %1$s - %2$s of %3$s profile with friends', 'Viewing %1$s - %2$s of %3$s profiles with friends', $members_template->total_member_count, 'vgsr' ), $from_num, $to_num, $total );
			}
		} elseif ( 'online' == $members_template->type ) {
			if ( 1 == $members_template->total_member_count ) {
				$pag = __( 'Viewing 1 online profile', 'vgsr' );
			} else {
				$pag = sprintf( _n( 'Viewing %1$s - %2$s of %3$s online profile', 'Viewing %1$s - %2$s of %3$s online profiles', $members_template->total_member_count, 'vgsr' ), $from_num, $to_num, $total );
			}
		} else {
			if ( 1 == $members_template->total_member_count ) {
				$pag = __( 'Viewing 1 profile', 'vgsr' );
			} else {
				$pag = sprintf( _n( 'Viewing %1$s - %2$s of %3$s profile', 'Viewing %1$s - %2$s of %3$s profiles', $members_template->total_member_count, 'vgsr' ), $from_num, $to_num, $total );
			}
		}
	}

	return $pag;
}

/**
 * Display additional member profile action links
 *
 * @since 0.1.0
 */
function vgsr_bp_add_member_header_actions() {

	// Bail when the user cannot moderate
	if ( ! bp_current_user_can( 'bp_moderate' ) )
		return;

	// Edit user in wp-admin link
	bp_button( array(
		'id'                => 'dashboard_profile',
		'component'         => 'members',
		'must_be_logged_in' => true,
		'block_self'        => false,
		'link_href'         => add_query_arg( array( 'user_id' => bp_displayed_user_id() ), admin_url( 'user-edit.php' ) ),
		'link_title'        => __( 'Edit this user in the admin.', 'vgsr' ),
		'link_text'         => __( 'Dashboard Profile', 'vgsr' ),
		'link_class'        => 'dashboard-profile'
	) );
}
