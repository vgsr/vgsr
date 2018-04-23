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
 * Display the jaargroepen member query filter
 *
 * @since 0.2.0
 */
function vgsr_bp_members_jaargroep_filter() {
	$jaargroepen = vgsr_get_jaargroepen();

	// Bail when no jaargroepen exist. Only when user is vgsr and on root blog
	if ( ! $jaargroepen || ! is_user_vgsr() || ! vgsr_bp_is_root_blog() )
		return;

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

	// In Oud-leden scope
	} elseif ( vgsr_bp_members_get_oudleden_scope() === vgsr_bp_members_get_query_arg( 'scope' ) ) {

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

/** Query ******************************************************************/

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
			foreach ( array_keys( vgsr_bp_member_types() ) as $member_type ) {
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

	// Jaargroep filtering
	if ( ! empty( $args['vgsr_jaargroep'] ) ) {

		// Define type argument container. Hijack `type` argument
		$args['type'] = array(
			'_type'     => $args['type'],
			'jaargroep' => $args['vgsr_jaargroep']
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

		// Define query modifiers
		$args['vgsr_jaargroep'] = $args['type']['jaargroep'];

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
		$sql['select'] .= " LEFT OUTER JOIN {$wpdb->usermeta} jaargroep ON u.ID = jaargroep.user_id";
		$sql['where'][] = $wpdb->prepare( "jaargroep.meta_key = %s AND jaargroep.meta_value = %s", 'jaargroep', $qv['vgsr_jaargroep'] );
	}

	return $sql;
}
