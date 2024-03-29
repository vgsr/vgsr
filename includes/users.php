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
 * @param WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
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
 * @param WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
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

/** Template **************************************************************/

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

	// Default to network users
	if ( ! isset( $args['blog_id'] ) ) {
		$args['blog_id'] = 0;
	}

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

	// Parse vgsr argument
	if ( isset( $args['vgsr'] ) && $args['vgsr'] ) {
		$query_args['vgsr'] = in_array( $args['vgsr'], array( 'lid', 'oud-lid' ) ) ? $args['vgsr'] : true;
	}

	return $query_args;
}

/** Query *****************************************************************/

/**
 * Modify the user query before parsing the query vars
 * 
 * @since 1.0.0
 *
 * @param WP_User_Query $users_query
 */
function vgsr_pre_get_users( $users_query ) {

	// Bail when not querying vgsr users
	if ( ! $users_query->get( 'vgsr' ) )
		return;

	/**
	 * Since we cannot filter query vars before the query defaults are parsed,
	 * assume 'login' as the default orderby value. Circumvent this assumption
	 * by providing the alternative 'user_login' orderby value.
	 */
	if ( 'login' === $users_query->get( 'orderby' ) ) {

		// Default to anciënniteit
		$users_query->set( 'orderby', 'ancienniteit' );
	}
}

/**
 * Modify the user query when querying vgsr users
 *
 * @since 0.1.0
 *
 * @global $wpdb WPDB
 *
 * @uses apply_filters() Calls 'vgsr_pre_user_query'
 *
 * @param WP_User_Query $users_query
 */
function vgsr_pre_user_query( $users_query ) {
	global $wpdb;

	/**
	 * Define what constitutes a vgsr user. Since this is left to extensions or
	 * other plugins, the filter allows for modifying the vgsr user definition.
	 */
	$sql_clauses = apply_filters( 'vgsr_pre_user_query', array( 'join' => '', 'where' => '', 'orderby' => '' ), $users_query );

	// Append JOIN statement
	if ( ! empty( $sql_clauses['join'] ) ) {
		$join = preg_replace( '/^\s*/', '', $sql_clauses['join'] );
		$users_query->query_from .= " $join";
	}

	// Append WHERE statement
	if ( ! empty( $sql_clauses['where'] ) ) {
		$where = preg_replace( '/^\s*AND\s*/', '', $sql_clauses['where'] );
		$users_query->query_where .= " AND $where";
	}

	// Filter by jaargroep
	if ( isset( $_GET['jaargroep'] ) && ! empty( $_GET['jaargroep'] ) ) {
		$users_query->query_from  .= $wpdb->prepare(" LEFT JOIN {$wpdb->usermeta} AS jaargroep ON {$wpdb->users}.ID = jaargroep.user_id AND jaargroep.meta_key = %s", 'jaargroep' );
		$users_query->query_where .= $wpdb->prepare( " AND jaargroep.meta_value = %d", (int) $_GET['jaargroep'] );
	}

	// Order by anciënniteit
	if ( in_array( $users_query->get( 'orderby' ), array( 'ancienniteit', 'ancienniteit-relevance' ), true ) ) {

		// Anciënniteit by user meta
		if ( apply_filters( 'vgsr_use_ancienniteit_meta', true ) ) {

			// Join with anciënniteit meta column to use only in ordering. Force
			// order null values to be sorted after meta values.
			$order                      = 'ASC' === strtoupper( $users_query->get( 'order' ) ) ? 'ASC' : 'DESC';
			$users_query->query_from   .= $wpdb->prepare( " LEFT JOIN {$wpdb->usermeta} AS ancienniteit ON {$wpdb->users}.ID = ancienniteit.user_id AND ancienniteit.meta_key = %s", 'ancienniteit' );
			$users_query->query_orderby = str_replace( 'ORDER BY', sprintf( 'ORDER BY CASE WHEN ancienniteit.meta_value IS NULL THEN 1 ELSE 0 END, CAST(ancienniteit.meta_value AS SIGNED) %s,', $order ), $users_query->query_orderby );
		}
	}

	// Order by relevant members first, then anciënniteit
	if ( 'ancienniteit-relevance' === $users_query->get( 'orderby' ) && ! empty( $sql_clauses['orderby'] ) ) {
		$orderby = preg_replace( '/^\s*/', '', $sql_clauses['orderby'] );
		$users_query->query_orderby = str_replace( 'ORDER BY', 'ORDER BY ' . $orderby . ',', $users_query->query_orderby );
	}
}

/** Is_* Functions ********************************************************/

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
 * @param WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
 * @return bool Is user VGSR?
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
 * @param WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
 * @return bool Is user lid?
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
 * @param WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
 * @return bool Is user oud-lid?
 */
function is_user_oudlid( $user = 0, $by = 'slug' ) {
	return (bool) apply_filters( 'is_user_oudlid', false, vgsr_get_user_id( $user, $by ) );
}

/**
 * Return whether a given user is marked as Ex-lid
 *
 * Plugins hook in the provided filter to determine whether the
 * given user is indeed so. The function assumes not by default.
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'is_user_exlid'
 *
 * @param WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
 * @return bool Is user ex-lid?
 */
function is_user_exlid( $user = 0, $by = 'slug' ) {
	return (bool) apply_filters( 'is_user_exlid', false, vgsr_get_user_id( $user, $by ) );
}

/**
 * Return the vgsr lid type the user is marked as
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_lid_type'
 *
 * @param WP_User|int|string $user Optional. User object or user ID|slug|name|email. Defaults to the current user.
 * @param string             $by   Optional. Type of input to get the user by for string values. Defaults to 'slug'.
 * @return string VGSR lid type or Empty text when not found.
 */
function vgsr_get_lid_type( $user = 0, $by = 'slug' ) {
	$type = apply_filters( 'vgsr_get_lid_type', '', vgsr_get_user_id( $user, $by ) );

	// Force lid type to be one of lid/oud-lid/ex-lid
	if ( ! in_array( $type, array( 'lid', 'oud-lid', 'ex-lid' ), true ) ) {
		$type = '';
	}

	return $type;
}

/** Attributes ************************************************************/

/**
 * Return the user's lastname, defaults to user login name.
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_lastname'
 *
 * @param WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return string User lastname.
 */
function vgsr_get_lastname( $user = 0 ) {
	$user     = vgsr_get_user( $user );
	$lastname = '';

	if ( $user ) {
		$lastname = $user->last_name;

		if ( ! $lastname ) {
			$lastname = $user->user_login;
		}
	}

	return apply_filters( 'vgsr_get_lastname', $lastname, $user );
}

/**
 * Return the user's fullname, defaults to user display name.
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_fullname'
 *
 * @param WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return string User fullname.
 */
function vgsr_get_fullname( $user = 0 ) {
	$user     = vgsr_get_user( $user );
	$fullname = '';

	if ( $user ) {
		$fullname = sprintf( '%s %s', $user->first_name, $user->last_name );

		if ( ! $fullname ) {
			$fullname = $user->display_name;
		}
	}

	return apply_filters( 'vgsr_get_fullname', $fullname, $user );
}

/**
 * Return the user's gender
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_gender'
 *
 * @param WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return bool|null True when male (1), False when female (0), Null when unknown.
 */
function vgsr_get_gender( $user = 0 ) {
	$user   = vgsr_get_user( $user );
	$gender = null;

	// Get gender from meta
	if ( $user ) {
		$meta = $user->get( 'gender' );

		if ( is_numeric( $meta ) ) {
			$gender = (bool) $meta;
		}
	}

	return apply_filters( 'vgsr_get_gender', $gender, $user );
}

/**
 * Return the user's anciënniteit
 *
 * Expect anciënniteit to be represented by 'YYYYXXX' where 'YYYY' represents
 * the user's jaargroep and 'XXX' represents the user's position within the
 * jaargroep.
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_ancienniteit'
 *
 * @param WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return int User anciënniteit
 */
function vgsr_get_ancienniteit( $user = 0 ) {
	$user         = vgsr_get_user( $user );
	$ancienniteit = 0;

	if ( $user ) {
		$ancienniteit = $user->get( 'ancienniteit' );
	}

	return (int) apply_filters( 'vgsr_get_ancienniteit', $ancienniteit, $user );
}

/**
 * Return the user's jaargroep
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_jaargroep'
 *
 * @param WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @param array $args Optional. Define additional parameters.
 * @return int User jaargroep
 */
function vgsr_get_jaargroep( $user = 0, $args = array() ) {
	$user      = vgsr_get_user( $user );
	$jaargroep = 0;
	$args      = wp_parse_args( $args, array(
		'default_to_ancienniteit' => true
	) );

	if ( $user ) {
		$jaargroep = $user->get( 'jaargroep' );

		// Default to the first 4 chars of ancienniteit
		if ( ! $jaargroep && $args['default_to_ancienniteit'] && $ancienniteit = vgsr_get_ancienniteit( $user ) ) {
			$jaargroep = (int) substr( $ancienniteit, 0, 4 );
		}
	}

	return (int) apply_filters( 'vgsr_get_jaargroep', $jaargroep, $user );
}

/**
 * Return all available user jaargroepen
 *
 * @since 0.2.0
 *
 * @global $wpdb WPDB
 *
 * @uses apply_filters() Calls 'vgsr_get_jaargroepen'
 *
 * @param array $args Optional. Define additional parameters.
 * @return array Jaargroepen
 */
function vgsr_get_jaargroepen( $args = array() ) {
	global $wpdb;

	// Parse args
	$args = wp_parse_args( $args, array(
		'default_to_ancienniteit' => true
	) );

	// Define query
	$query_where = $args['default_to_ancienniteit'] ? 'WHERE meta_key IN ( %s, %s )' : 'WHERE meta_key = %s';
	$query  = $wpdb->prepare( "SELECT LEFT(meta_value, 4) as meta_value FROM {$wpdb->usermeta} $query_where ORDER BY meta_value ASC", 'jaargroep', 'ancienniteit' );
	$retval = array_values( array_unique( array_map( 'intval', $wpdb->get_col( $query ) ) ) );

	return (array) apply_filters( 'vgsr_get_jaargroepen', $retval );
}

/** Formalities ***********************************************************/

/**
 * Return a letter's opening lines
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_salutation'
 *
 * @param WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return string User's salutation.
 */
function vgsr_get_salutation( $user = 0 ) {

	// Parse arguments
	$user   = vgsr_get_user( $user );
	$gender = vgsr_get_gender( $user );
	$retval = '';

	// VGSR salutation
	if ( $user && is_user_vgsr( $user ) ) {
		if ( null !== $gender ) {
			$retval = $gender
				? esc_html__( 'Honourable amice %s,', 'vgsr' )
				: esc_html__( 'Honourable amica %s,', 'vgsr' );
		} else {
			$retval = esc_html__( 'Honourable amica aut amice %s,', 'vgsr' );
		}

	// Default salutation
	} else {
		if ( null !== $gender ) {
			$retval = $gender
				? esc_html_x( 'Dear mr. %s,',  'General salutation', 'vgsr' )
				: esc_html_x( 'Dear mrs. %s,', 'General salutation', 'vgsr' );
		} else {
			$retval = esc_html_x( 'Dear mr. or mrs. %s,', 'General salutation', 'vgsr' );
		}
	}

	// Parse with last name
	$retval = sprintf( $retval, ucfirst( vgsr_get_lastname( $user ) ) );

	return apply_filters( 'vgsr_get_salutation', $retval, $user );
}

/**
 * Return the user's salutation wrappend in paragraph tags
 *
 * @since 1.0.0
 *
 * @param WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @return string User's paragraphed salutation.
 */
function vgsr_get_salutation_p( $user = 0 ) {
	return wpautop( vgsr_get_salutation( $user ) ) . "\n";
}

/**
 * Return a letter's closing lines
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_get_closing'
 *
 * @param array $args Closing arguments, supports these args:
 *  - addressee: User object or ID of the addressed person. Defaults to False.
 *  - addresser: User object or ID of the addressing person. Defaults to the current user.
 *  - description: Descriptor of the addressing person.
 * @return string Closing statement.
 */
function vgsr_get_closing( $args = array() ) {

	// Define return variable
	$retval = '';

	// Parse arguments
	$r = wp_parse_args( $args, array(
		'addressee'   => false,
		'addresser'   => vgsr_get_user(),
		'description' => ''
	) );

	$r['addressee'] = vgsr_get_user( $r['addressee'] );
	$r['addresser'] = vgsr_get_user( $r['addresser'] );

	// VGSR closing
	if ( $r['addressee'] && is_user_vgsr( $r['addressee'] ) ) {
		$retval = 'Met amicale groet,';

	// Default closing
	} else {
		$retval = esc_html_x( 'Sincerely,', 'General closing', 'vgsr' );
	}

	// Append addresser
	if ( $r['addresser'] ) {
		$retval .= "\n\n" . vgsr_get_fullname( $r['addresser'] );

		// Append description
		if ( $r['description'] ) {
			$retval .= "\n<em>" . $r['description'] . "</em>";
		}
	}

	return apply_filters( 'vgsr_get_closing', $retval, $r );
}

/**
 * Return the user's closing wrappend in paragraph tags
 *
 * @since 1.0.0
 *
 * @param array $args Optional. Closing arguments, see {@see vgsr_get_closing()}.
 * @return string Closing wrapped in paragraphs.
 */
function vgsr_get_closing_p( $args = array() ) {
	return wpautop( vgsr_get_closing( $args ) ) . "\n";
}

/** Anciënniteit **********************************************************/

/**
 * Compare function for users based on anciënniteit
 *
 * @since 1.0.0
 *
 * @param WP_User|int $a User object or ID.
 * @param WP_User|int $b User object or ID.
 * @return int Comparison result
 */
function vgsr_cmp_ancienniteit( $a, $b ) {
	$a = vgsr_get_ancienniteit( $a );
	$b = vgsr_get_ancienniteit( $b );

	if ( 0 !== $a && 0 !== $b ) {
		return $a > $b ? 1 : -1;
	} elseif ( $b > 0 ) {
		return 1;
	} else {
		return -1;
	}
}

/** Admin Bar *************************************************************/

/**
 * Modify the admin bar, after full setup
 *
 * @since 0.1.0
 */
function vgsr_admin_bar_menus() {
	add_action( 'admin_bar_menu', 'vgsr_admin_bar_wp_menu',       10 );
	add_action( 'admin_bar_menu', 'vgsr_admin_bar_my_sites_menu', 20 );
	add_action( 'admin_bar_menu', 'vgsr_admin_bar_site_menu',     30 );
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

	// Remove My Sites menu for non-vgsr users
	if ( is_user_logged_in() && ! is_user_vgsr() ) {
		$wp_admin_bar->remove_node( 'my-sites' );
		return;
	}

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

/**
 * Modify the site's admin bar menu
 *
 * @see wp_admin_bar_site_menu()
 *
 * @since 0.2.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function vgsr_admin_bar_site_menu( $wp_admin_bar ) {

	// Remove Site menu for non-vgsr users
	if ( is_user_logged_in() && ! is_user_vgsr() ) {
		$wp_admin_bar->remove_node( 'site-name' );
	}
}
