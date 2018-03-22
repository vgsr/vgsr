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
