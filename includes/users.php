<?php

/**
 * VGSR User Functions
 *
 * @package VGSR
 * @subpackage User
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the user object for the given user
 *
 * @since 1.0.0
 *
 * @param  WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param  string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
 * @return WP_User|bool User object or False when not found.
 */
function vgsr_get_user( $user = 0, $by = 'slug' ) {

	// Get user from id
	if ( $user && is_numeric( $user ) ) {
		$user = get_user_by( 'id', $user );

	// Get user from slug
	} elseif ( is_string( $user ) ) {
		$user = get_user_by( $by, $user );

	// Default to current user
	} elseif ( 0 === $user ) {
		$user = get_user_by( 'id', vgsr_get_current_user_id() );
	}

	// Get id from user object
	if ( ! is_a( $user, 'WP_User' ) || ! $user->exists() ) {
		$user = false;
	}

	return $user;
}

/**
 * Return the exising user's ID for the given user
 *
 * @since 1.0.0
 *
 * @param  WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param  string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
 * @return int User ID
 */
function vgsr_get_user_id( $user = 0, $by = 'slug' ) {
	$user = vgsr_get_user( $user, $by );
	return $user ? $user->ID : 0;
}

/**
 * Return the current user ID even when it isn't set yet
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'determine_current_user'
 *
 * @return int User ID
 */
function vgsr_get_current_user_id() {
	return did_action( 'set_current_user' ) ? get_current_user_id() : apply_filters( 'determine_current_user', 0 );
}

/**
 * Return a list of vgsr users matching criteria
 * 
 * @since 0.1.0
 *
 * @param array $args Optional. Arguments for use in `WP_User_Query`.
 * @return array Users
 */
function vgsr_get_users( $args = array() ) {

	// Define query arguments. Default to all vgsr users.
	$args = wp_parse_args( $args, array( 'vgsr' => true ) );
	$args['vgsr'] = in_array( $args['vgsr'], array( 'lid', 'oud-lid' ) ) ? $args['vgsr'] : true;

	// Query the users
	return get_users( $args );
}

/**
 * Create dropdown HTML element of vgsr users
 *
 * @since 0.1.0
 *
 * @param array $args Optional. Arguments for `wp_dropdown_users()`.
 * @return string Dropdown when 'echo' argument is false.
 */
function vgsr_dropdown_users( $args = array() ) {

	// Define user query arguments. Default to all vgsr users.
	$args = wp_parse_args( $args, array( 'vgsr' => true ) );
	$args['vgsr'] = in_array( $args['vgsr'], array( 'lid', 'oud-lid' ) ) ? $args['vgsr'] : true;

	// Get the dropdown
	return wp_dropdown_users( $args );
}

/**
 * Modify the query arguments for the users dropdown
 *
 * @since 0.1.0
 *
 * @param array $query_args Query arguments
 * @param array $args Dropdown arguments
 * @return array Query arguments
 */
function vgsr_dropdown_users_args( $query_args = array(), $args = array() ) {

	// Add 'vgsr' argument to query args
	if ( isset( $args['vgsr'] ) && $args['vgsr'] ) {
		$query_args['vgsr'] = in_array( $args['vgsr'], array( 'lid', 'oud-lid' ) ) ? $args['vgsr'] : true;
	}

	return $query_args;
}

/**
 * Modify the user query when querying vgsr users
 *
 * @since 0.1.0
 *
 * @uses apply_filters() Calls 'vgsr_pre_user_query'
 * @param WP_User_Query $query
 */
function vgsr_pre_user_query( $query ) {

	// Bail when not querying vgsr users
	if ( ! $query->get( 'vgsr' ) )
		return;

	// Enable plugin filtering
	$sql_clauses = array( 'join' => '', 'where' => '' );
	$sql_clauses = apply_filters( 'vgsr_pre_user_query', $sql_clauses, $query );

	// Append JOIN statement
	if ( ! empty( $sql_clauses['join'] ) ) {
		$join = preg_replace( '/^\s*/', '', $sql_clauses['where'] );
		$query->query_join .= " $join";
	}

	// Append WHERE statement
	if ( ! empty( $sql_clauses['where'] ) ) {
		$where = preg_replace( '/^\s*AND\s*/', '', $sql_clauses['where'] );
		$query->query_where .= " AND $where";
	}
}

/** Is Functions **********************************************************/

/**
 * Return whether a given user is marked as VGSR
 *
 * Plugins hook in the provided filter to determine whether the
 * given user is indeed so. The function assumes not by default.
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'is_user_vgsr'
 * 
 * @param  WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param  string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
 * @return bool User is VGSR
 */
function is_user_vgsr( $user = 0, $by = 'slug' ) {
	return (bool) apply_filters( 'is_user_vgsr', false, vgsr_get_user_id( $user, $by ) );
}

/**
 * Return whether a given user is marked as Lid
 *
 * Plugins hook in the provided filter to determine whether the
 * given user is indeed so. The function assumes not by default.
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'is_user_lid'
 * 
 * @param  WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param  string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
 * @return bool User is Lid
 */
function is_user_lid( $user = 0, $by = 'slug' ) {
	return (bool) apply_filters( 'is_user_lid', false, vgsr_get_user_id( $user, $by ) );
}

/**
 * Return whether a given user is marked as Oud-lid
 *
 * Plugins hook in the provided filter to determine whether the
 * given user is indeed so. The function assumes not by default.
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'is_user_oudlid'
 * 
 * @param  WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param  string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
 * @return bool User is Oud-lid
 */
function is_user_oudlid( $user = 0, $by = 'slug' ) {
	return (bool) apply_filters( 'is_user_oudlid', false, vgsr_get_user_id( $user, $by ) );
}

/** Attributes ************************************************************/

/**
 * Return the user's lastname, defaults to user login name.
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_user_lastname'
 *
 * @param  WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return string User lastname.
 */
function vgsr_get_user_lastname( $user = 0 ) {
	$user     = vgsr_get_user( $user );
	$lastname = '';

	if ( $user ) {
		$lastname = $user->last_name;

		if ( ! $lastname ) {
			$lastname = $user->user_login;
		}
	}

	return apply_filters( 'vgsr_get_user_lastname', $lastname, $user );
}

/**
 * Return the user's fullname, defaults to user display name.
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_user_fullname'
 *
 * @param  WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return string User fullname.
 */
function vgsr_get_user_fullname( $user = 0 ) {
	$user     = vgsr_get_user( $user );
	$fullname = '';

	if ( $user ) {
		$fullname = sprintf( '%s %s', $user->first_name, $user->last_name );

		if ( ! $fullname ) {
			$fullname = $user->display_name;
		}
	}

	return apply_filters( 'vgsr_get_user_fullname', $fullname, $user );
}

/**
 * Return the user's gender
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_user_gender'
 *
 * @param  WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return bool|null True when male (1), False when female (0), Null when unknown.
 */
function vgsr_get_user_gender( $user = 0 ) {
	$user   = vgsr_get_user( $user );
	$gender = null;

	// Get gender from meta
	if ( $user ) {
		$meta = $user->get( 'gender' );

		if ( is_numeric( $meta ) ) {
			$gender = (bool) $meta;
		}
	}

	return apply_filters( 'vgsr_get_user_gender', $gender, $user );
}

/** Formalities ***********************************************************/

/**
 * Return the user's salutation text
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_salutation'
 *
 * @param  WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return string User's salutation.
 */
function vgsr_get_salutation( $user = 0 ) {
	$user   = vgsr_get_user( $user );
	$gender = vgsr_get_user_gender( $user );
	$salut  = '';

	// VGSR salutation
	if ( $user && is_user_vgsr( $user ) ) {
		if ( null !== $gender ) {
			$salut = $gender
				? 'Waarde amice %s,'
				: 'Waarde amica %s,';
		} else {
			$salut = 'Waarde amica aut amice %s,';
		}

	// Default salutation
	} else {
		if ( null !== $gender ) {
			$salut = $gender
				? __( 'Dear mr. %s,',  'vgsr' )
				: __( 'Dear mrs. %s,', 'vgsr' );
		} else {
			$salut = __( 'Dear mr. or mrs. %s,', 'vgsr' );
		}
	}

	// Parse with last name
	$salut = sprintf( $salut, ucfirst( vgsr_get_user_lastname( $user ) ) );

	return apply_filters( 'vgsr_get_salutation', $salut, $user );
}

/**
 * Return the user's salutation wrappend in paragraph tags
 *
 * @since 1.0.0
 *
 * @param  WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return string User's paragraphed salutation.
 */
function vgsr_get_salutation_p( $user = 0 ) {
	return wpautop( vgsr_get_salutation( $user ) ) . "\n";
}

/**
 * Return the user's closing text
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_closing'
 *
 * @param  WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return string User's closing.
 */
function vgsr_get_closing( $args = array() ) {
	$close = '';

	// Parse arguments
	$args = wp_parse_args( $args, array(
		'addressed' => false,
		'author'    => vgsr_get_user(),
	) );

	$args['addressed'] = vgsr_get_user( $args['addressed'] );
	$args['author']    = vgsr_get_user( $args['author'] );

	// VGSR closing
	if ( $args['addressed'] && is_user_vgsr( $args['addressed'] ) ) {
		$close = 'Met amicale groet,';

	// Default closing
	} else {
		$close = __( 'Sincerely,', 'vgsr' );
	}

	// Append author
	if ( $args['author'] ) {
		$close .= "\n\n" . vgsr_get_user_fullname( $args['author'] );
	}

	return apply_filters( 'vgsr_get_closing', $close, $args );
}

/**
 * Return the user's closing wrappend in paragraph tags
 *
 * @since 1.0.0
 *
 * @param  WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return string User's paragraphed closing.
 */
function vgsr_get_closing_p( $user = 0 ) {
	return wpautop( vgsr_get_closing( array( 'addressed' => $user ) ) ) . "\n";
}

/** Admin Bar *************************************************************/

/**
 * Modify the admin bar, after full setup
 *
 * @since 0.1.0
 */
function vgsr_admin_bar_menus() {

	// Modify WP Logo menu
	add_action( 'admin_bar_menu', 'vgsr_admin_bar_wp_menu', 10 );

	// Modify My Sites nodes
	add_action( 'admin_bar_menu', 'vgsr_admin_bar_my_sites_menu', 20 );
}

/**
 * Modify the WP Logo admin bar menu
 *
 * @see wp_admin_bar_wp_menu()
 *
 * @since 0.1.0
 * 
 * @param WP_Admin_Bar $wp_admin_bar
 */
function vgsr_admin_bar_wp_menu( $wp_admin_bar ) {

	// Hide WP menu for non-admins
	if ( ! current_user_can( 'manage_options' ) ) {
		$wp_admin_bar->remove_node( 'wp-logo' );
	}

	// @todo Add WP-like VGSR menu
}

/**
 * Modify the My Sites admin bar menu
 *
 * @see wp_admin_bar_my_sites_menu()
 *
 * @since 0.1.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function vgsr_admin_bar_my_sites_menu( $wp_admin_bar ) {

	// Bail when not in Multisite
	if ( ! is_multisite() )
		return;

	// Walk user blogs under My Sites
	foreach ( $wp_admin_bar->user->blogs as $blog ) {
		switch_to_blog( $blog->userblog_id );

		// Node exists
		if ( $node = $wp_admin_bar->get_node( 'blog-' . $blog->userblog_id ) ) {

			// Remove the logo icon, and link to site front page
			$node->title = str_replace( '<div class="blavatar"></div>', '', $node->title );
			$node->href  = home_url( '/' );

			// Overwrite node
			$wp_admin_bar->add_node( $node );
		}

		restore_current_blog();
	}
}
