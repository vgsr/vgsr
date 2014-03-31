<?php

/**
 * VGSR bbPress Functions
 *
 * @package VGSR
 * @subpackage Extend
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Settings ***************************************************************/

/**
 * Add settings section for bbPress options
 *
 * @since 1.0.0
 * 
 * @param array $sections VGSR settings sections
 * @return array $sections
 */
function vgsr_bbp_settings_section( $sections = array() ) {
	$sections['vgsr_settings_bbpress'] = array(
		'title'    => __('bbPress Settings', 'vgsr'),
		'callback' => 'vgsr_bbp_setting_callback_bbpress_section',
		'page'     => 'vgsr'
	);

	return $sections;
}

/**
 * Add settings fields for bbPress options
 *
 * @since 1.0.0
 * 
 * @param array $fields VGSR settings fields
 * @return array $fields
 */
function vgsr_bbp_settings_fields( $fields = array() ) {
	$fields['vgsr_settings_bbpress'] = (array) apply_filters( 'vgsr_bbp_settings_fields', array(
	
		// Hide profile root slug
		'vgsr_bbp_hide_profile_root' => array(
			'title'             => __('Hide profile root', 'vgsr'),
			'callback'          => 'vgsr_bbp_setting_callback_hide_profile_root',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),
	
		// Breadcrumbs Home
		'vgsr_bbp_breadcrumbs_home' => array(
			'title'             => __('Breadcrumbs Home', 'vgsr'),
			'callback'          => 'vgsr_bbp_setting_callback_breadcrumbs_home',
			'sanitize_callback' => 'esc_sql',
			'args'              => array()
		)
	) );

	return $fields;
}

/**
 * bbPress settings section description for the settings page
 *
 * @since 1.0.0
 */
function vgsr_bbp_setting_callback_bbpress_section() {
?>

	<p><?php esc_html_e('Set the main bbPress manipulations for VGSR.', 'vgsr'); ?></p>

<?php
}

/**
 * Profile root slug settings field
 *
 * @since 1.0.0
 *
 * @todo Check rewrite rules to work properly
 *
 * @uses bbp_get_user_profile_url()
 * @uses remove_filter()
 * @uses add_filter()
 */
function vgsr_bbp_setting_callback_hide_profile_root() {

	// Flush rewrite rules when this page is saved
	if ( isset( $_GET['settings-updated'] ) && isset( $_GET['page'] ) )
		flush_rewrite_rules(); 

	// Get extend class
	$bbp_extend = vgsr()->extend->bbpress;

	// Remove the profile filter now for demonstration purposes
	remove_filter( 'bbp_get_user_slug', array( $bbp_extend, 'hide_profile_root' ) );

	// Get default user profile link
	$show_root_url = bbp_get_user_profile_url( get_current_user_id() );

	// Get modified user profile link
	add_filter( 'bbp_maybe_get_root_slug', '__return_false' );
	$hide_root_url = bbp_get_user_profile_url( get_current_user_id() );
	remove_filter( 'bbp_maybe_get_root_slug', '__return_false' );

	// Setup the profile filter back again
	add_filter( 'bbp_get_user_slug', array( $bbp_extend, 'hide_profile_root' ) ); ?>

	<input id="vgsr_bbp_hide_profile_root" name="vgsr_bbp_hide_profile_root" type="checkbox" value="1" <?php checked( vgsr_get_form_option( 'vgsr_bbp_hide_profile_root' ) ); vgsr_maybe_admin_setting_disabled( 'vgsr_bbp_hide_profile_root' ); ?> />
	<label for="vgsr_bbp_hide_profile_root"><span class="description"><?php printf( esc_html__('Remove forums root slug for user profile pages. Turns %1$s into %2$s.', 'vgsr'), $show_root_url, $hide_root_url ); ?></span></label>

<?php
}

/**
 * Breadcrumbs home text settings field
 *
 * @since 1.0.0
 *
 * @uses bbp_get_user_profile_url()
 * @uses remove_filter()
 * @uses add_filter()
 */
function vgsr_bbp_setting_callback_breadcrumbs_home() {
?>

	<input id="vgsr_bbp_breadcrumbs_home" name="vgsr_bbp_breadcrumbs_home" type="text" class="regular-text" value="<?php vgsr_form_option( 'vgsr_bbp_breadcrumbs_home' ); ?>" <?php vgsr_maybe_admin_setting_disabled( 'vgsr_bbp_breadcrumbs_home' ); ?> />
	<label for="vgsr_bbp_breadcrumbs_home"><span class="description"><?php esc_html_e('Overwrite the forums breadcrumbs home text. Keep empty to default to the home page title.', 'vgsr' ); ?></span></label>

<?php
}

/** Options ***************************************************************/

/**
 * Return whether to hide the root slug for user profiles
 *
 * @since 1.0.0
 *
 * @param int $default Optional. Default value
 * @uses get_option()
 * @return bool Hide root slug
 */
function vgsr_bbp_hide_profile_root( $default = 0 ) {
	return (bool) apply_filters( 'vgsr_bbp_hide_profile_root', get_option( 'vgsr_bbp_hide_profile_root', $default ) );
}

/**
 * Return the breadcrumbs home text
 *
 * @since 1.0.0
 *
 * @param string $default Optional. Default value
 * @uses get_option()
 * @return string Breadcrumbs home
 */
function vgsr_bbp_breadcrumbs_home( $default = '' ) {
	return apply_filters( 'vgsr_bbp_breadcrumbs_home', get_option( 'vgsr_bbp_breadcrumbs_home', $default ) );
}

