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

	// When the user is vgsr. Only for the root blog
	if ( is_user_vgsr() && vgsr_bp_is_root_blog() ) {

		// Add tabs for Lid and Oud-lid member type
		vgsr_bp_members_member_type_tab( vgsr_bp_lid_member_type()    );
		vgsr_bp_members_member_type_tab( vgsr_bp_oudlid_member_type() );
	}

	// For admins
	if ( current_user_can( 'bp_moderate' ) ) {
		printf( '<li id="members-all_profiles"><a href="%s">%s</a></li>',
			bp_get_members_directory_permalink(),
			sprintf( __( 'All Profiles %s', 'vgsr' ), '<span>' . bp_get_total_member_count() . '</span>' )
		);
	}
}

/**
 * Add additional order options to the Members directory
 *
 * @since 0.2.0
 */
function vgsr_bp_members_directory_order_options() {

	// When the user is vgsr. Only for the root blog
	if ( is_user_vgsr() && vgsr_bp_is_root_blog() ) {

		// Add filter option for ancienniteit
		echo '<option value="ancienniteit" selected="selected">' . esc_html__( 'Anciënniteit', 'vgsr' ) . '</option>';
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
 * Return the leden members directory scope name
 *
 * @since 0.2.0
 *
 * @return string Scope for leden members directory
 */
function vgsr_bp_members_get_leden_scope() {
	return 'vgsr_member_type_' . vgsr_bp_lid_member_type();
}

/**
 * Return the oud-leden members directory scope name
 *
 * @since 0.2.0
 *
 * @return string Scope for oud-leden members directory
 */
function vgsr_bp_members_get_oudleden_scope() {
	return 'vgsr_member_type_' . vgsr_bp_oudlid_member_type();
}

/**
 * Return whether this is the leden members directory scope
 *
 * @since 0.2.0
 *
 * @return bool Is this the leden scope?
 */
function vgsr_bp_members_is_leden_scope() {
	return vgsr_bp_members_get_leden_scope() === vgsr_bp_members_get_query_arg( 'scope' );
}

/**
 * Return whether this is the oud-leden members directory scope
 *
 * @since 0.2.0
 *
 * @return bool Is this the oud-leden scope?
 */
function vgsr_bp_members_is_oudleden_scope() {
	return vgsr_bp_members_get_oudleden_scope() === vgsr_bp_members_get_query_arg( 'scope' );
}

/**
 * Display the jaargroepen member query filter
 *
 * @since 0.2.0
 */
function vgsr_bp_members_jaargroep_filter() {
	$jaargroepen = vgsr_get_jaargroepen();

	// Bail when no jaargroepen exist. Only when user is vgsr and on root blog
	if ( ! $jaargroepen || ! is_user_vgsr() || ! vgsr_bp_is_root_blog() )
		return;

	// Sort descending
	rsort( $jaargroepen );

	?>

	<li id="members-jaargroep-select" class="last vgsr-filter">
		<label for="members-by-jaargroep"><?php esc_html_e( 'Jaargroep:', 'vgsr' ); ?></label>
		<select id="members-by-jaargroep">
			<option value=""><?php echo esc_html( _x( 'All', 'Jaargroep member filter', 'vgsr' ) ); ?></option>
			<?php foreach ( $jaargroepen as $jaargroep ) : ?>

			<option value="<?php echo esc_attr( $jaargroep ); ?>"><?php echo $jaargroep; ?></option>

			<?php endforeach; ?>
		</select>
	</li>

	<?php
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
	$lines = array();

	// In the All Profiles scope
	if ( 'all_profiles' === vgsr_bp_members_get_query_arg( 'scope' ) ) {

		// Active
		$lines['active'] = array(
			1 => __( 'Viewing 1 active profile', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s active profile', 'Viewing %1$s - %2$s of %3$s active profiles', 'vgsr' ),
		);

		// Popular
		$lines['popular'] = array(
			1 => __( 'Viewing 1 profile with friends', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s profile with friends', 'Viewing %1$s - %2$s of %3$s profiles with friends', 'vgsr' ),
		);

		// Online
		$lines['online'] = array(
			1 => __( 'Viewing 1 online profile', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s online profile', 'Viewing %1$s - %2$s of %3$s online profiles', 'vgsr' ),
		);

		// Default
		$lines['default'] = array(
			1 => __( 'Viewing 1 profile', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s profile', 'Viewing %1$s - %2$s of %3$s profiles', 'vgsr' ),
		);

	// In Leden scope
	} elseif ( vgsr_bp_members_is_leden_scope() ) {

		// Active
		$lines['active'] = array(
			1 => __( 'Viewing 1 active lid', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s active lid', 'Viewing %1$s - %2$s of %3$s active leden', 'vgsr' ),
		);

		// Popular
		$lines['popular'] = array(
			1 => __( 'Viewing 1 lid with friends', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s lid with friends', 'Viewing %1$s - %2$s of %3$s leden with friends', 'vgsr' ),
		);

		// Online
		$lines['online'] = array(
			1 => __( 'Viewing 1 online lid', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s online lid', 'Viewing %1$s - %2$s of %3$s online leden', 'vgsr' ),
		);

		// Default
		$lines['default'] = array(
			1 => __( 'Viewing 1 lid', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s lid', 'Viewing %1$s - %2$s of %3$s leden', 'vgsr' ),
		);

	// In Oud-leden scope
	} elseif ( vgsr_bp_members_is_oudleden_scope() ) {

		// Active
		$lines['active'] = array(
			1 => __( 'Viewing 1 active oud-lid', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s active oud-lid', 'Viewing %1$s - %2$s of %3$s active oud-leden', 'vgsr' ),
		);

		// Popular
		$lines['popular'] = array(
			1 => __( 'Viewing 1 oud-lid with friends', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s oud-lid with friends', 'Viewing %1$s - %2$s of %3$s oud-leden with friends', 'vgsr' ),
		);

		// Online
		$lines['online'] = array(
			1 => __( 'Viewing 1 online oud-lid', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s online oud-lid', 'Viewing %1$s - %2$s of %3$s online oud-leden', 'vgsr' ),
		);

		// Default
		$lines['default'] = array(
			1 => __( 'Viewing 1 oud-lid', 'vgsr' ),
			2 => _n_noop( 'Viewing %1$s - %2$s of %3$s oud-lid', 'Viewing %1$s - %2$s of %3$s oud-leden', 'vgsr' ),
		);
	}

	if ( $lines ) {
		global $members_template;

		$start_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $start_num + ( $members_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $members_template->total_member_count );

		$type = in_array( $members_template->type, array( 'active', 'popular', 'online' ) ) ? $members_template->type : 'default';

		if ( 1 == $members_template->total_member_count ) {
			$pag = $lines[ $type ][1];
		} else {
			$pag = sprintf( translate_nooped_plural( $lines[ $type ][2], $members_template->total_member_count, 'vgsr' ), $from_num, $to_num, $total );
		}
	}

	return $pag;
}

/**
 * Display additional member profile action links
 *
 * @since 0.1.0
 */
function vgsr_bp_add_directory_members_actions() {

	// When the user can moderate
	if ( bp_current_user_can( 'bp_moderate' ) ) {

		// Edit user in wp-admin link
		vgsr_bp_member_dashboard_profile_button();
	}
}

/**
 * Display additional member profile action links
 *
 * @since 0.1.0
 */
function vgsr_bp_add_member_header_actions() {

	// When the user can moderate
	if ( bp_current_user_can( 'bp_moderate' ) ) {

		// Edit user in wp-admin link
		vgsr_bp_member_dashboard_profile_button();
	}
}

/**
 * Output a BP button linking to the member's dashboard profile
 *
 * @since 0.2.0
 *
 * @param int $user_id Optional. User ID. Defaults to displayed or directory user.
 */
function vgsr_bp_member_dashboard_profile_button( $user_id = 0 ) {

	// Default to displayed user
	if ( ! $user_id && bp_is_user() ) {
		$user_id = bp_displayed_user_id();

	// Default to members template user
	} elseif ( ! $user_id && bp_get_member_user_id() ) {
		$user_id = bp_get_member_user_id();

	// Bail when no user is provided
	} elseif ( ! $user_id ) {
		return;
	}

	bp_button( array(
		'id'                => 'dashboard_profile',
		'component'         => 'members',
		'must_be_logged_in' => true,
		'block_self'        => false,
		'link_href'         => add_query_arg( array( 'user_id' => $user_id ), admin_url( 'user-edit.php' ) ),
		'link_title'        => esc_html__( 'Edit this user in the admin.', 'vgsr' ),
		'link_text'         => esc_html__( 'Dashboard Profile', 'vgsr' ),
		'link_class'        => 'dashboard-profile'
	) );
}

/** Query ******************************************************************/

/**
 * Return the SQL WHERE statement for the matched 'vgsr' parameter
 *
 * @since 1.0.0
 *
 * @param bool|string $arg The 'vgsr' query parameter.
 * @return string SQL WHERE statement
 */
function vgsr_bp_query_for_vgsr_arg( $arg = '' ) {

	// Define return variable
	$retval = '';

	// Query Leden
	if ( 'lid' === $arg ) {
		$retval = vgsr_bp_query_is_user_lid();

	// Query Oud-leden
	} elseif ( 'oud-lid' === $arg ) {
		$retval = vgsr_bp_query_is_user_oudlid();

	// Query all vgsr
	} elseif ( true === $arg ) {
		$retval = vgsr_bp_query_is_user_vgsr();
	}

	return $retval;
}

/**
 * Return the SQL WHERE statement to query by all vgsr member types
 *
 * @since 0.1.0
 */
function vgsr_bp_query_is_user_vgsr() {
	return vgsr_bp_query_where_user_by_member_type( array(
		vgsr_bp_lid_member_type(),
		vgsr_bp_oudlid_member_type()
	) );
}

/**
 * Return the SQL WHERE statement to query by Lid member type
 *
 * @since 0.1.0
 */
function vgsr_bp_query_is_user_lid() {
	return vgsr_bp_query_where_user_by_member_type( vgsr_bp_lid_member_type() );
}

/**
 * Return the SQL WHERE statement to query by Oud-lid member type
 *
 * @since 0.1.0
 */
function vgsr_bp_query_is_user_oudlid() {
	return vgsr_bp_query_where_user_by_member_type( vgsr_bp_oudlid_member_type() );
}

/**
 * Return the SQL WHERE statement to query users by member type
 *
 * @see BP_User_Query::get_sql_clause_for_member_types()
 *
 * @since 0.1.0
 *
 * @global WPDB $wpdb
 *
 * @param string|array Member type name(s)
 * @param string $users_alias Optional. Alias for the users table. Defaults to 'u'.
 * @return string Member type SQL WHERE statement
 */
function vgsr_bp_query_where_user_by_member_type( $member_types = '', $users_alias = 'u' ) {
	global $wpdb;

	// Parse and sanitize types.
	if ( ! is_array( $member_types ) ) {
		$member_types = preg_split( '/[,\s+]/', $member_types );
	}

	$types = array();
	foreach ( $member_types as $mt ) {
		if ( bp_get_member_type_object( $mt ) ) {
			$types[] = $mt;
		}
	}

	$tax_query = new WP_Tax_Query( array(
		array(
			'taxonomy' => bp_get_member_type_tax_name(),
			'field'    => 'name',
			'operator' => 'IN',
			'terms'    => $types,
		),
	) );

	// Switch to the root blog, where member type taxonomies live.
	$site_id  = bp_get_taxonomy_term_site_id( bp_get_member_type_tax_name() );
	$switched = false;
	if ( $site_id !== get_current_blog_id() ) {
		switch_to_blog( $site_id );
		$switched = true;
	}

	// Generete SQL clause
	$sql_clauses = $tax_query->get_sql( $users_alias, 'ID' );

	$clause = '';

	// The no_results clauses are the same between IN and NOT IN.
	if ( false !== strpos( $sql_clauses['where'], '0 = 1' ) ) {
		$clause = $sql_clauses['where'];

	// IN clauses must be converted to a subquery.
	} elseif ( preg_match( '/' . $wpdb->term_relationships . '\.term_taxonomy_id IN \([0-9, ]+\)/', $sql_clauses['where'], $matches ) ) {
		$clause = "{$users_alias}.ID IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE {$matches[0]} )";
	}

	if ( $switched ) {
		restore_current_blog();
	}

	return $clause;
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

		// Default to querying/sorting by ancienniteit
		if ( is_user_vgsr() && empty( $object_filter ) ) {
			$query_string .= "type=ancienniteit";
		}

		// Default scope All Members to all vgsr member types
		if ( 'all' === $object_scope || empty( $object_scope ) ) {
			foreach ( array_keys( vgsr_bp_get_member_types( true ) ) as $member_type ) {
				$query_string .= "&member_type__in[]={$member_type}";
			}

		// Single member type
		} elseif ( 0 === strpos( $object_scope, 'vgsr_member_type_' ) ) {
			$member_type   = str_replace( 'vgsr_member_type_', '', $object_scope );
			$query_string .= "&member_type__in={$member_type}";
		}
	}

	// Apply extras
	if ( ! empty( $object_extras ) ) {
		/**
		 * Extras reference only a single value, since BP doesn't handle arrays or
		 * objects for extra data. This means that untill a better way is found,
		 * the variable is single-purposed and can only be utilized for jaargroep
		 * filtering.
		 */
		$query_string .= '&vgsr_jaargroep=' . $object_extras;
	}

	return $query_string;
}

/**
 * Return list of custom query args that are passed to `bp_has_members()`
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_bp_custom_has_members_args'
 * @return array Custom args passed to `bp_has_members()`
 */
function vgsr_bp_custom_has_members_args() {
	return (array) apply_filters( 'vgsr_bp_custom_has_members_args', array(
		'vgsr',
		'vgsr_jaargroep'
	) );
}

/**
 * Modify the parsed members query arguments
 *
 * Since BP's directory queries do not allow for custom query arg parsing,
 * we hack around this by hijacking the `type` arg and resetting it later.
 * This filter is paired with {@see vgsr_bp_parse_core_get_users_args()}.
 *
 * @since 0.2.0
 *
 * @param array $args Parsed query args.
 * @return array Parsed query args
 */
function vgsr_bp_parse_has_members_args( $args = array() ) {

	// Get custom args
	$custom_args = vgsr_bp_custom_has_members_args();
	$intersect   = array_intersect_key( $args, array_flip( $custom_args ) );

	// Custom args provided
	if ( $intersect ) {

		// Define type argument container. Hijack `type` argument
		$args['type'] = array_merge(
			array( '_type' => $args['type'] ),
			$intersect
		);
	}

	return $args;
}

/**
 * Modify the to-parse members query arguments
 *
 * Since BP's directory queries do not allow for custom query arg parsing,
 * we hack around this by hijacking the `type` arg and resetting it here.
 * This filter is paired with {@see vgsr_bp_parse_has_members_args()}.
 *
 * @since 0.2.0
 *
 * @param array $args Args to parse
 * @return array Args to parse
 */
function vgsr_bp_parse_core_get_users_args( $args = array() ) {

	// This is our modified 'type' argument
	if ( is_array( $args['type'] ) && isset( $args['type']['_type'] ) ) {

		// Preserve `type` argument
		$type = $args['type']['_type'];
		unset( $args['type']['_type'] );

		// Walk custom args
		foreach ( vgsr_bp_custom_has_members_args() as $arg ) {

			// Move custom query argument over
			if ( isset( $args['type'][ $arg ] ) ) {
				$args[ $arg ] = $args['type'][ $arg ];
			}
		}

		// Reset `type` argument
		$args['type'] = $type;
	}

	return $args;
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
 * Modify the SQL clauses for the user ID query of a BP_User_Query
 *
 * @since 0.2.0
 *
 * @global $wpdb WPDB
 *
 * @param  array $sql SQL clauses
 * @param  BP_User_Query $user_query User query object
 * @return array SQL clauses
 */
function vgsr_bp_user_query_uid_clauses( $sql, $user_query ) {
	global $wpdb;

	$qv = $user_query->query_vars;

	// Ordering by ancienniteit
	if ( 'ancienniteit' == $qv['type'] ) {
		$sql['select'] .= " LEFT OUTER JOIN {$wpdb->usermeta} ancienniteit ON u.ID = ancienniteit.user_id";
		$sql['where'][] = $wpdb->prepare( "ancienniteit.meta_key = %s", 'ancienniteit' );
		$sql['orderby'] = "ORDER BY CAST(ancienniteit.meta_value AS SIGNED)";
		$sql['order']   = "ASC";
	}

	// Jaargroep filtering
	if ( ! empty( $qv['vgsr_jaargroep'] ) ) {
		$sql['select'] .= " LEFT OUTER JOIN {$wpdb->usermeta} jaargroep ON u.{$user_query->uid_name} = jaargroep.user_id";
		$sql['where'][] = $wpdb->prepare( "jaargroep.meta_key = %s AND jaargroep.meta_value = %s", 'jaargroep', $qv['vgsr_jaargroep'] );
	}

	return $sql;
}
