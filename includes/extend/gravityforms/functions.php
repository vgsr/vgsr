<?php

/**
 * VGSR Gravity Forms Functions
 *
 * @package VGSR
 * @subpackage Gravity Forms
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Form *******************************************************************/

/**
 * Return the full form data object
 *
 * @since 0.3.0
 *
 * @param int $form_id Form ID
 * @param bool $with_meta Optional. Whether to return form meta as well. Defaults to true.
 * @return object|bool Form data or False when not found
 */
function vgsr_gf_get_form( $form_id, $with_meta = true ) {

	// Bail when there's no form ID
	if ( empty( $form_id ) ) {
		return false;
	}

	// Get the form data
	if ( is_numeric( $form_id ) ) {
		$form = GFFormsModel::get_form( (int) $form_id );
	} else {
		$form = (object) $form_id;
		$form_id = $form->id;
	}

	// Combine form data
	if ( $form && $with_meta && ! isset( $form->display_meta ) ) {
		$form = (object) array_merge( (array) $form, (array) GFFormsModel::get_form_meta( $form_id ) );

		// Sanitize form
		$form = vgsr_gf_sanitize_form( $form );
	}

	return $form;
}

/**
 * Sanitizes a raw form and sets it up for further usage
 *
 * @since 0.3.0
 *
 * @uses apply_filters() Calls 'vgsr_gf_sanitize_form'
 *
 * @param object $form Raw form
 * @return object Form
 */
function vgsr_gf_sanitize_form( $form ) {

	// Unserialize and attach meta
	if ( isset( $form->display_meta ) ) {
		$meta = GFFormsModel::unserialize( $form->display_meta );

		// Unset meta array
		unset( $form->display_meta );

		// Set meta properties
		foreach ( $meta as $key => $value ) {
			$form->$key = $value;
		}
	}

	// Default view count
	if ( ! isset( $form->view_count ) ) {
		$views = wp_list_filter( GFFormsModel::get_view_count_per_form(), array( 'form_id' => $form->id ) );
		$views = reset( $views );

		$form->view_count = $views ? (int) $views->view_count : 0;
	}

	// Default lead count
	if ( ! isset( $form->lead_count ) ) {
		$form->lead_count = (int) GFFormsModel::get_lead_count( $form->id, null );
	}

	return apply_filters( 'vgsr_gf_sanitize_form', $form );
}

/**
 * Return the full form field data object
 *
 * @since 0.3.0
 *
 * @param object|int $field_id Field object or ID.
 * @param object|int $form Optional. Form object or ID to find field by.
 * @return object|bool Form field or False when not found.
 */
function vgsr_gf_get_field( $field_id, $form = 0 ) {
	$form  = vgsr_gf_get_form( $form );
	$field = false;

	// Get by id
	if ( $form && is_numeric( $field_id ) ) {
		$field = GFFormsModel::get_field( (array) $form, $field_id );

	// The field object
	} elseif ( is_object( $field_id ) ) {
		$field = $field_id;
	}

	return $field;
}

/**
 * Return the given form's meta value
 *
 * @since 0.0.7
 *
 * @param object|int $form Form object or form ID
 * @param string $meta_key Form meta key
 * @return mixed Form setting's value or NULL when not found
 */
function vgsr_gf_get_form_meta( $form, $meta_key ) {
	$form = vgsr_gf_get_form( $form );
	$meta = null;

	// Get form setting
	if ( $form && isset( $form->{$meta_key} ) ) {
		$meta = $form->{$meta_key};
	}

	return $meta;
}

/**
 * Return the given field's meta value
 *
 * @since 0.0.7
 *
 * @param object|int $field Field object or field ID
 * @param string $meta_key Field meta key
 * @param array|int $form Optional. Form object or form ID to find field by.
 * @return mixed Field setting's value or NULL when not found
 */
function vgsr_gf_get_field_meta( $field, $meta_key, $form = 0 ) {
	$field = vgsr_gf_get_field( $field, $form );
	$meta  = null;

	if ( $field && isset( $field->{$meta_key} ) ) {
		$meta = $field->{$meta_key};
	}

	return $meta;
}

/**
 * Query and return forms
 *
 * @since 0.3.0
 *
 * @uses apply_filters() Calls 'vgsr_gf_get_forms'
 *
 * @param array $args Query arguments, supports these args:
 *  - number: The number of forms to query. Accepts -1 for all forms. Defaults to -1.
 *  - paged: The number of the current page for pagination.
 *  - count: Whether to return the form count. Defaults to false.
 *  - show_active: Whether to return active (true) or inactive (false) forms only. Accepts null for either status. Defaults to true.
 *  - orderby: The database column to order the results by. Defaults to 'date_created'.
 *  - order: Designates ascending or descending of ordered forms. Defaults to 'DESC'.
 *  - s: Search terms that could match a form's title.
 *  - suppress_filters: Whether to suppress filters. Defaults to false.
 * @return array Form objects
 */
function vgsr_gf_get_forms( $args = array() ) {

	// Parse arguments
	$r = wp_parse_args( $args, array(
		'number'           => -1,
		'paged'            => 1,
		'count'            => false,
		'show_active'      => true,
		'orderby'          => 'date_created',
		'order'            => 'DESC',
		's'                => '',
		'suppress_filters' => false,
	) );

	// Query forms the GF way: fetch all
	if ( ! empty( $r['s'] ) ) {
		$forms = GFFormsModel::search_forms( $r['s'], $r['show_active'], $r['orderby'], $r['order'] );
	} else {
		$forms = GFFormsModel::get_forms( $r['show_active'], $r['orderby'], $r['order'] );
	}

	// Setup form objects
	$forms = array_map( 'vgsr_gf_get_form', $forms );

	if ( ! $r['suppress_filters'] ) {

		// Enable plugin filtering
		$forms = (array) apply_filters( 'vgsr_gf_get_forms', $forms, $r );
	}

	// Return count early
	if ( $r['count'] ) {
		return count( $forms );
	}

	// Paginate the GF way, after the query
	if ( $r['number'] > 0 ) {
		$r['paged'] = absint( $r['paged'] );
		if ( $r['paged'] == 0 ) {
			$r['paged'] = 1;
		}
		$r['offset'] = absint( ( $r['paged'] - 1 ) * $r['number'] );

		$forms = array_slice( $forms, $r['offset'], $r['number'] );
	}

	return $forms;
}

/** Helpers ****************************************************************/

/**
 * Apply a i18n function with the 'gravityforms' context
 *
 * @since 0.3.0
 *
 * @param string|array $args I18n function argument(s)
 * @param string $i18n Optional. I18n function name. Defaults to '__'.
 * @return string Translated text
 */
function vgsr_gf_i18n( $args, $i18n = '__' ) {

	// Bail when no arguments were passed
	if ( empty( $args ) )
		return '';

	// Append translation domain
	$args   = (array) $args;
	$args[] = 'gravityforms';

	return call_user_func_array( $i18n, $args );
}

/** Is VGSR ****************************************************************/

/**
 * Display the meta key which marks GF assets exclusive for vgsr
 *
 * @since 0.2.0
 */
function vgsr_gf_exclusivity_meta_key() {
	echo vgsr_gf_get_exclusivity_meta_key();
}

	/**
	 * Return the meta key which marks GF assets exclusive for vgsr
	 *
	 * @since 0.2.0
	 *
	 * @uses apply_filters() Calls 'vgsr_gf_get_exclusivity_meta_key'
	 * @return string Meta key
	 */
	function vgsr_gf_get_exclusivity_meta_key() {
		return apply_filters( 'vgsr_gf_get_exclusivity_meta_key', 'vgsrOnly' );
	}

/**
 * Return whether the given form is exclusive
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_gf_is_form_vgsr'
 *
 * @param array|int $form Form object or form ID
 * @param bool $check_fields Optional. Whether to check fields for exclusivity.
 * @return bool Is form exclusive?
 */
function vgsr_gf_is_form_vgsr( $form, $check_fields = true ) {

	// Form itself is exclusive
	$exclusive = (bool) vgsr_gf_get_form_meta( $form, vgsr_gf_get_exclusivity_meta_key() );

	// Or maybe *all* fields are exclusive
	if ( ! $exclusive && $check_fields ) {

		// Assume all fields are exclusive
		$exclusive = true;

		// Walk all fields
		foreach ( vgsr_gf_get_form_meta( $form, 'fields' ) as $field ) {

			// Break when a field is not exclusive
			if ( ! vgsr_gf_is_field_vgsr( $field ) ) {
				$exclusive = false;
				break;
			}
		}
	}

	return (bool) apply_filters( 'vgsr_gf_is_form_vgsr', $exclusive, $form, $check_fields );
}

/**
 * Return whether the given field is exclusive
 *
 * @since 0.0.7
 *
 * @uses apply_filters() Calls 'vgsr_gf_is_field_vgsr'
 *
 * @param object|int $field Field object or Field ID
 * @param array|int $form Form object or form ID
 * @return bool Is form field exclusive?
 */
function vgsr_gf_is_field_vgsr( $field, $form = '' ) {
	$field  = vgsr_gf_get_field( $field, $form );
	$retval = false;

	if ( $field ) {
		$retval = (bool) vgsr_gf_get_field_meta( $field, vgsr_gf_get_exclusivity_meta_key() );
	}

	return (bool) apply_filters( 'vgsr_gf_is_field_vgsr', $retval, $field );
}

/** Export *****************************************************************/

/**
 * Output the contents of the admin export page
 *
 * @see GFForms::export_page()
 * @see GFExport::export_page()
 * @see GFExport::export_lead_page()
 *
 * @since 0.3.0
 */
function vgsr_gf_admin_export_page() {

	if ( GFForms::maybe_display_wizard() ) {
		return;
	};

	require_once( GFCommon::get_base_path() . '/export.php' );

	/**
	 * To limit forms for export, modify the markup generated for GF's
	 * export page, removing the non-allowed forms.
	 */
	ob_start();

	GFExport::export_page();

	$page = ob_get_clean();

	// Walk all forms
	foreach ( vgsr_gf_get_forms( array(
		'show_active' => null, // Query all active states
		'orderby'     => 'title'
	) ) as $form ) {

		// Remove un-exportable form options
		if ( ! vgsr_gf_can_user_export_form( $form ) ) {
			$page = str_replace( sprintf( '<option value="%s">%s</option>', absint( $form->id ), esc_html( $form->title ) ), '', $page );
		}
	}

	echo $page;
}

/**
 * Modify the csv separator for exported GF data
 *
 * By using a semicolon as separator, MS Excel somehow interpretes
 * cells with line breaks (/n,/r) correctly. This prevents values
 * with line breaks to be parsed as separate rows.
 *
 * @since 0.0.8
 *
 * @param string $sep CSV separator
 * @param int $form_id Form ID
 * @return string CSV separator
 */
function vgsr_gf_export_separator( $sep, $form_id ) {
	return ';';
}

/**
 * Return the forms the user can export
 *
 * @since 0.3.0
 *
 * @param int $user_id Optional. User ID. Defaults to the current user.
 * @return array Forms the user can export
 */
function vgsr_gf_get_forms_user_can_export( $user_id = 0 ) {

	// Default to the current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// Get forms
	$forms = vgsr_gf_get_forms( array(
		'show_active' => null // Query all active states
	) );

	// Walk all forms untill we find a match
	foreach ( $forms as $k => $form ) {
		if ( ! vgsr_gf_can_user_export_form( $form, $user_id ) ) {
			unset( $forms[ $k ] );
		}
	}

	$forms = array_values( $forms );

	return (array) apply_filters( 'vgsr_gf_get_forms_user_can_export', $forms, $user_id );
}

/**
 * Return whether the user can export any form
 *
 * @since 0.3.0
 *
 * @param int $user_id Optional. User ID. Defaults to the current user.
 * @return bool Can user export any form?
 */
function vgsr_gf_can_user_export( $user_id = 0 ) {
	return (bool) vgsr_gf_get_forms_user_can_export( $user_id );
}

/**
 * Return whether the user can export the form
 *
 * @since 0.3.0
 *
 * @param object|int $form Form object or ID
 * @param int $user_id Optional. User ID. Defaults to the current user.
 * @return bool Can user export the form?
 */
function vgsr_gf_can_user_export_form( $form, $user_id = 0 ) {

	// Default to the current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// Get form
	$form   = vgsr_gf_get_form( $form );
	$retval = false;

	if ( $form ) {
		$exporters = vgsr_gf_get_form_meta( $form, 'vgsrExporters' );

		if ( $exporters ) {
			$retval = in_array( $user_id, array_map( 'intval', $exporters ), true );
		}
	}

	return (bool) apply_filters( 'vgsr_gf_can_user_export_form', $retval, $form, $user_id );
}

/**
 * Add extra data items to the list exportable entry data
 *
 * @since 1.0.0
 *
 * @param array $form Form data
 * @return array Form data
 */
function vgsr_gf_entry_export_fields( $form ) {

	/**
	 * Add additional user data fields
	 */

	// User login
	$form['fields'][] = array(
		'id'    => 'vgsr-user-login',
		'label' => esc_html_x( 'User login', 'Gravity Forms export field', 'vgsr' )
	);

	// User display name
	$form['fields'][] = array(
		'id'    => 'vgsr-user-display-name',
		'label' => esc_html_x( 'User display name', 'Gravity Forms export field', 'vgsr' )
	);

	// User email
	$form['fields'][] = array(
		'id'    => 'vgsr-user-email',
		'label' => esc_html_x( 'User email', 'Gravity Forms export field', 'vgsr' )
	);

	return $form;
}

/**
 * Modify the value for the given export field
 *
 * @since 1.0.0
 *
 * @param mixed $value Export field value
 * @param int $form_id Form ID
 * @param string $field Export field name
 * @param array $entry Entry data
 * @return mixed Export field value
 */
function vgsr_gf_export_field_value( $value, $form_id, $field, $entry ) {

	// Get entry's user
	$user_id = (int) $entry['created_by'];

	// Bail when the user does not exist
	if ( ! $user_id || ! $user = get_user_by( 'id', $user_id ) )
		return $value;

	// Check the field
	switch ( $field ) {

		// User login
		case 'vgsr-user-login' :
			$value = $user->user_login;

			break;

		// User display name
		case 'vgsr-user-display-name' :
			$value = $user->display_name;

			break;

		// User email
		case 'vgsr-user-email' :
			$value = $user->user_email;

			break;
	}

	return $value;
}
