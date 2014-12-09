<?php

/**
 * VGSR BuddyPress Functions
 *
 * @package VGSR
 * @subpackage Extend
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Settings ***************************************************************/

/**
 * Add settings section for BuddyPress options
 *
 * @since 0.0.1
 * 
 * @param array $sections VGSR settings sections
 * @return array $sections
 */
function vgsr_bp_settings_sections( $sections = array() ) {

	// General
	$sections['vgsr_settings_bp_main'] = array(
		'title'    => __( 'BuddyPress Main', 'vgsr' ),
		'callback' => 'vgsr_bp_setting_callback_general_section',
		'page'     => 'vgsr'
	);

	// Groups
	if ( bp_is_active( 'groups' ) ) {	
		$sections['vgsr_settings_bp_groups'] = array(
			'title'    => __( 'BuddyPress Groups', 'vgsr' ),
			'callback' => 'vgsr_bp_setting_callback_groups_section',
			'page'     => 'vgsr'
		);
	}

	return $sections;
}

/**
 * Add settings fields for BuddyPress options
 *
 * @since 0.0.1
 * 
 * @param array $fields VGSR settings fields
 * @return array $fields
 */
function vgsr_bp_settings_fields( $fields = array() ) {

	// General
	$fields['vgsr_settings_bp_main'] = (array) apply_filters( 'vgsr_settings_fields_bp_main', array(

		// Remove My Account area
		'vgsr_bp_remove_ab_my_account_root' => array(
			'title'             => __( 'My Account', 'vgsr' ),
			'callback'          => 'vgsr_bp_setting_callback_remove_ab_my_account_root',
			'sanitize_callback' => 'intval',
			'args'              => array()
		)

	) );
	
	// Component: Groups
	if ( bp_is_active( 'groups' ) ) {
		$groups_settings = array(

			// VGSR main group
			'vgsr_bp_group_vgsr' => array(
				'title'             => __( 'Main Group', 'vgsr' ),
				'callback'          => 'vgsr_bp_setting_callback_group_vgsr',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),
			
			// VGSR leden group
			'vgsr_bp_group_leden' => array(
				'title'             => __( 'Leden Group', 'vgsr' ),
				'callback'          => 'vgsr_bp_setting_callback_group_leden',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// VGSR oud-leden group
			'vgsr_bp_group_oudleden' => array(
				'title'             => __( 'Oud-leden Group', 'vgsr' ),
				'callback'          => 'vgsr_bp_setting_callback_group_oudleden',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			'vgsr_bp_remove_groups_admin_nav' => array(
				'title'             => __( 'Admin bar', 'vgsr' ),
				'callback'          => 'vgsr_bp_setting_callback_remove_groups_admin_nav',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),
		);

		// Not using group hierarchy
		if ( ! vgsr()->extend->bp->bp_group_hierarchy ) {
			unset( $groups_settings['vgsr_bp_group_vgsr'] );
		}

		// Remove group admin nav
		$fields['vgsr_settings_bp_groups'] = (array) apply_filters( 'vgsr_settings_fields_bp_groups', $groups_settings );
	}

	return $fields;
}

/** Settings sections ******************************************************/

/**
 * BuddyPress General settings section description for the settings page
 *
 * @since 0.0.1
 */
function vgsr_bp_setting_callback_general_section() { /* Do nothing here */ }

/**
 * BuddyPress Groups settings section description for the settings page
 *
 * @since 0.0.1
 */
function vgsr_bp_setting_callback_groups_section() { /* Do nothing here */ }

/** Settings General *******************************************************/

/**
 * Remove My Account area settings field
 * 
 * @since 0.0.1
 *
 * @uses vgsr_bp_remove_ab_my_account_root()
 */
function vgsr_bp_setting_callback_remove_ab_my_account_root() { ?>

	<input type="checkbox" id="vgsr_bp_remove_ab_my_account_root" name="vgsr_bp_remove_ab_my_account_root" value="1" <?php checked( vgsr_bp_remove_ab_my_account_root() ); ?> />
	<label for="vgsr_bp_remove_ab_my_account_root"><?php esc_html_e( 'Remove the BuddyPress My Account area in the admin bar', 'vgsr' ); ?></label>

	<?php
}

/** Settings Groups ********************************************************/

/**
 * VGSR group settings field
 * 
 * @since 0.0.1
 *
 * @uses groups_get_groups()
 * @uses vgsr_get_group_vgsr_id()
 */
function vgsr_bp_setting_callback_group_vgsr() {

	// Get all group data
	$data   = groups_get_groups( array( 'type' => 'alphabetical', 'show_hidden' => true ) ); 
	$groups = $data['groups']; ?>

	<select id="vgsr_bp_group_vgsr" name="vgsr_bp_group_vgsr">
		<option><?php _e( 'Select a group', 'vgsr' ); ?></option>

		<?php foreach ( $groups as $group ) : ?>
			<option value="<?php echo $group->id; ?>" <?php selected( vgsr_get_group_vgsr_id(), $group->id ); ?>><?php echo $group->name; ?></option>
		<?php endforeach; ?>
	</select>

	<?php
}

/**
 * Leden group settings field
 *
 * @since 0.0.1
 *
 * @uses groups_get_groups()
 * @uses vgsr_get_group_leden_id()
 */
function vgsr_bp_setting_callback_group_leden() {

	// Get all group data
	$data   = groups_get_groups( array( 'type' => 'alphabetical', 'show_hidden' => true ) ); 
	$groups = $data['groups']; ?>

	<select id="vgsr_bp_group_leden" name="vgsr_bp_group_leden">
		<option><?php _e( 'Select a group', 'vgsr' ); ?></option>

		<?php foreach ( $groups as $group ) : ?>
			<option value="<?php echo $group->id; ?>" <?php selected( vgsr_get_group_leden_id(), $group->id ); ?>><?php echo $group->name; ?></option>
		<?php endforeach; ?>
	</select>

	<?php
}

/**
 * Oud-leden group settings field
 *
 * @since 0.0.1
 * 
 * @uses groups_get_groups()
 * @uses vgsr_get_group_oudleden_id()
 */
function vgsr_bp_setting_callback_group_oudleden() {

	// Get all group data
	$data   = groups_get_groups( array( 'type' => 'alphabetical', 'show_hidden' => true ) ); 
	$groups = $data['groups']; ?>

	<select id="vgsr_bp_group_oudleden" name="vgsr_bp_group_oudleden">
		<option><?php _e( 'Select a group', 'vgsr' ); ?></option>

		<?php foreach ( $groups as $group ) : ?>
			<option value="<?php echo $group->id; ?>" <?php selected( vgsr_get_group_oudleden_id(), $group->id ); ?>><?php echo $group->name; ?></option>
		<?php endforeach; ?>
	</select>

	<?php
}

/**
 * Remove groups admin nav settings field
 * 
 * @since 0.0.1
 *
 * @uses vgsr_bp_remove_groups_admin_nav()
 */
function vgsr_bp_setting_callback_remove_groups_admin_nav() { ?>

	<input id="vgsr_bp_remove_groups_admin_nav" name="vgsr_bp_remove_groups_admin_nav" type="checkbox" value="1" <?php checked( vgsr_bp_remove_groups_admin_nav() ); ?> />
	<label for="vgsr_bp_remove_groups_admin_nav"><?php esc_html_e( 'Remove the groups admin bar menu items', 'vgsr' ); ?></label>

	<?php
}

/** Options ***************************************************************/

/**
 * Return whether to remove the My Account area
 *
 * @since 0.0.1
 *
 * @uses get_site_option()
 * @return bool Remove area
 */
function vgsr_bp_remove_ab_my_account_root() {
	return (bool) get_site_option( 'vgsr_bp_remove_ab_my_account_root', 0 );
}

/**
 * Return whether to remove the group admin bar menu items
 *
 * @since 0.0.1
 *
 * @uses get_site_option()
 * @return bool Remove menu items
 */
function vgsr_bp_remove_groups_admin_nav() {
	return (bool) get_site_option( 'vgsr_bp_remove_groups_admin_nav', 0 );
}
