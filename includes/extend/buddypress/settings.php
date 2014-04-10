<?php

/**
 * VGSR BuddyPress Functions
 *
 * @package VGSR
 * @subpackage Extend
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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
	$sections['vgsr_settings_bp_general'] = array(
		'title'    => __('BuddyPress General', 'vgsr'),
		'callback' => 'vgsr_bp_setting_callback_general_section',
		'page'     => 'vgsr'
	);

	// Groups
	if ( bp_is_active( 'groups' ) ) {	
		$sections['vgsr_settings_bp_groups'] = array(
			'title'    => __('BuddyPress Groups', 'vgsr'),
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
	$fields['vgsr_settings_bp_general'] = (array) apply_filters( 'vgsr_settings_fields_bp_general', array(
	
		// Remove My Account area
		'vgsr_bp_remove_ab_my_account_root' => array(
			'title'             => __('Remove My Account area', 'vgsr'),
			'callback'          => 'vgsr_bp_setting_callback_remove_ab_my_account_root',
			'sanitize_callback' => 'intval',
			'args'              => array()
		)

	) );
	
	// Groups
	if ( bp_is_active( 'groups' ) ) {
		$fields['vgsr_settings_bp_groups'] = (array) apply_filters( 'vgsr_settings_fields_bp_groups', array(
			
			// VGSR main group
			'vgsr_bp_group_vgsr' => array(
				'title'             => __('Main Group', 'vgsr'),
				'callback'          => 'vgsr_bp_setting_callback_group_vgsr',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),
			
			// VGSR leden group
			'vgsr_bp_group_leden' => array(
				'title'             => __('Leden Group', 'vgsr'),
				'callback'          => 'vgsr_bp_setting_callback_group_leden',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// VGSR oud-leden group
			'vgsr_bp_group_oudleden' => array(
				'title'             => __('Oud-leden Group', 'vgsr'),
				'callback'          => 'vgsr_bp_setting_callback_group_oudleden',
				'sanitize_callback' => 'intval',
				'args'              => array()
			)

		) );
	}

	return $fields;
}

/** Settings sections ******************************************************/

/**
 * BuddyPress General settings section description for the settings page
 *
 * @since 0.0.1
 */
function vgsr_bp_setting_callback_general_section() {
?>

	<p><?php esc_html_e('BuddyPress manipulations for VGSR.', 'vgsr'); ?></p>

<?php
}

/**
 * BuddyPress Groups settings section description for the settings page
 *
 * @since 0.0.1
 */
function vgsr_bp_setting_callback_groups_section() {
	// Nothing to show
}

/** Settings fields ********************************************************/

/**
 * Hide profile links settings field
 * 
 * @since 0.0.1
 *
 * @uses groups_get_groups()
 * @uses vgsr_get_group_vgsr_id()
 */
function vgsr_bp_setting_callback_remove_ab_my_account_root() {
?>

	<input id="vgsr_bp_remove_ab_my_account_root" name="vgsr_bp_remove_ab_my_account_root" type="checkbox" value="1" <?php checked( vgsr_bp_remove_ab_my_account_root() ); ?> />
	<label for="vgsr_bp_remove_ab_my_account_root"><span class="description"><?php esc_html_e('Remove the BuddyPress My Account area in the admin bar.', 'vgsr'); ?></span></label>

<?php
}

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
		<option><?php _e('Select a group', 'vgsr'); ?></option>

		<?php foreach ( $groups as $group ) : ?>

		<option value="<?php echo $group->id; ?>" <?php selected( vgsr_get_group_vgsr_id(), $group->id ); ?>><?php echo $group->name; ?></option>

		<?php endforeach; ?>
	</select>
	<label for="vgsr_bp_group_vgsr"><span class="description"><?php esc_html_e('The main VGSR group.', 'vgsr'); ?></span></label>

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
		<option><?php _e('Select a group', 'vgsr'); ?></option>

		<?php foreach ( $groups as $group ) : ?>

		<option value="<?php echo $group->id; ?>" <?php selected( vgsr_get_group_leden_id(), $group->id ); ?>><?php echo $group->name; ?></option>

		<?php endforeach; ?>
	</select>
	<label for="vgsr_bp_group_leden"><span class="description"><?php esc_html_e('The leden VGSR group.', 'vgsr'); ?></span></label>

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
		<option><?php _e('Select a group', 'vgsr'); ?></option>

		<?php foreach ( $groups as $group ) : ?>

		<option value="<?php echo $group->id; ?>" <?php selected( vgsr_get_group_oudleden_id(), $group->id ); ?>><?php echo $group->name; ?></option>

		<?php endforeach; ?>
	</select>
	<label for="vgsr_bp_group_oudleden"><span class="description"><?php esc_html_e('The oud-leden VGSR group.', 'vgsr'); ?></span></label>

<?php
}

/** Options ***************************************************************/

/**
 * Return whether to remove the My Account area
 *
 * @since 0.0.1
 *
 * @uses get_option()
 * @return bool Remove area
 */
function vgsr_bp_remove_ab_my_account_root() {
	return (bool) get_option( 'vgsr_bp_remove_ab_my_account_root', 0 );
}
