<?php

/**
 * VGSR Gravity Forms Functions
 *
 * @package VGSR
 * @subpackage Gravity Forms
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Meta *******************************************************************/

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
 * Return the given form's meta value
 *
 * @since 0.0.7
 *
 * @param array|int $form Form object or form ID
 * @param string $meta_key Form meta key
 * @return mixed Form setting's value or NULL when not found
 */
function vgsr_gf_get_form_meta( $form, $meta_key ) {

	// Get form metadata
	if ( ! is_array( $form ) && is_numeric( $form ) ) {
		$form = GFFormsModel::get_form_meta( (int) $form );
	} elseif ( ! is_array( $form ) ) {
		return null;
	}

	// Get form setting
	return isset( $form[ $meta_key ] ) ? $form[ $meta_key ] : null;
}

/**
 * Return the given field's meta value
 *
 * @since 0.0.7
 *
 * @param array|int $field Field object or field ID
 * @param string $meta_key Field meta key
 * @param array|int $form Form object or form ID
 * @return mixed Field setting's value or NULL when not found
 */
function vgsr_gf_get_field_meta( $field, $meta_key, $form = '' ) {

	// Get field metadata
	if ( is_numeric( $field ) && ! empty( $form ) ) {

		// Form ID provided
		if ( is_numeric( $form ) ) {
			$form = GFFormsModel::get_form_meta( (int) $form );
		}

		// Read the field from the form's data
		$field = GFFormsModel::get_field( $form, $field );

	} elseif ( ! is_array( $field ) && ! is_object( $field ) ) {
		return null;
	}

	$field = (array) $field;

	// Get field setting
	return isset( $field[ $meta_key ] ) ? $field[ $meta_key ] : null;
}

/** Is VGSR ****************************************************************/

/**
 * Return whether the given form is exclusive
 *
 * @since 0.0.6
 *
 * @uses apply_filters() Calls 'vgsr_gf_is_form_vgsr'
 *
 * @param array|int $form Form object or form ID
 * @param bool $check_fields Optional. Whether to check fields for exclusivity.
 * @return bool Form is exclusive
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
 * @param array|int $field Field object or Field ID
 * @param array|int $form Form object or form ID
 * @return bool Field is exclusive
 */
function vgsr_gf_is_field_vgsr( $field, $form = '' ) {
	return (bool) apply_filters( 'vgsr_gf_is_field_vgsr', (bool) vgsr_gf_get_field_meta( $field, vgsr_gf_get_exclusivity_meta_key(), $form ), $field, $form );
}

