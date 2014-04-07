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
 * @since 1.0.0
 * 
 * @param array $sections VGSR settings sections
 * @return array $sections
 */
function vgsr_bp_settings_section( $sections = array() ) {
	$sections['vgsr_settings_buddypress'] = array(
		'title'    => __('BuddyPress Settings', 'vgsr'),
		'callback' => 'vgsr_bp_setting_callback_buddypress_section',
		'page'     => 'vgsr'
	);

	return $sections;
}

/**
 * Add settings fields for BuddyPress options
 *
 * @since 1.0.0
 * 
 * @param array $fields VGSR settings fields
 * @return array $fields
 */
function vgsr_bp_settings_fields( $fields = array() ) {
	$fields['vgsr_settings_buddypress'] = (array) apply_filters( 'vgsr_bp_settings_fields', array(
	
		// VGSR main group
		'vgsr_bp_group_vgsr' => array(
			'title'             => __('Main group', 'vgsr'),
			'callback'          => 'vgsr_bp_setting_callback_group_vgsr',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),
		
		// VGSR leden group
		'vgsr_bp_group_leden' => array(
			'title'             => __('Leden group', 'vgsr'),
			'callback'          => 'vgsr_bp_setting_callback_group_leden',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),

		// VGSR oud-leden group
		'vgsr_bp_group_oudleden' => array(
			'title'             => __('Oud-leden group', 'vgsr'),
			'callback'          => 'vgsr_bp_setting_callback_group_oudleden',
			'sanitize_callback' => 'intval',
			'args'              => array()
		)
	) );

	return $fields;
}

/**
 * BuddyPress settings section description for the settings page
 *
 * @since 1.0.0
 */
function vgsr_bp_setting_callback_buddypress_section() {
?>

	<p><?php esc_html_e('Set the main BuddyPress manipulations for VGSR.', 'vgsr'); ?></p>

<?php
}


/**
 * Main group settings field
 * 
 * @since 1.0.0
 *
 * @uses groups_get_groups()
 * @uses vgsr_get_group_vgsr_id()
 */
function vgsr_bp_setting_callback_group_vgsr() {
?>

	<select id="vgsr_bp_group_vgsr" name="vgsr_bp_group_vgsr">
		<option><?php _e('Select a group', 'vgsr'); ?></option>

		<?php foreach ( groups_get_groups() as $group ) : ?>

		<option value="<?php echo $group->id; ?>" <?php selected( vgsr_get_group_vgsr_id(), $group->id ); ?>><?php echo $group->name; ?></option>

		<?php endforeach; ?>
	</select>
	<label for="vgsr_bp_group_vgsr"><span class="description"><?php esc_html_e('The main VGSR group.', 'vgsr'); ?></span></label>

<?php
}

/**
 * Leden group settings field
 *
 * @since 1.0.0
 *
 * @uses groups_get_groups()
 * @uses vgsr_get_group_leden_id()
 */
function vgsr_bp_setting_callback_group_leden() {
?>

	<select id="vgsr_bp_group_leden" name="vgsr_bp_group_leden">
		<option><?php _e('Select a group', 'vgsr'); ?></option>

		<?php foreach ( groups_get_groups() as $group ) : ?>

		<option value="<?php echo $group->id; ?>" <?php selected( vgsr_get_group_leden_id(), $group->id ); ?>><?php echo $group->name; ?></option>

		<?php endforeach; ?>
	</select>
	<label for="vgsr_bp_group_leden"><span class="description"><?php esc_html_e('The leden VGSR group.', 'vgsr'); ?></span></label>

<?php
}

/**
 * Oud-leden group settings field
 *
 * @since 1.0.0
 * 
 * @uses groups_get_groups()
 * @uses vgsr_get_group_oudleden_id()
 */
function vgsr_bp_setting_callback_group_oudleden() {
?>

	<select id="vgsr_bp_group_oudleden" name="vgsr_bp_group_oudleden">
		<option><?php _e('Select a group', 'vgsr'); ?></option>

		<?php foreach ( groups_get_groups() as $group ) : ?>

		<option value="<?php echo $group->id; ?>" <?php selected( vgsr_get_group_oudleden_id(), $group->id ); ?>><?php echo $group->name; ?></option>

		<?php endforeach; ?>
	</select>
	<label for="vgsr_bp_group_oudleden"><span class="description"><?php esc_html_e('The oud-leden VGSR group.', 'vgsr'); ?></span></label>

<?php
}

/** Options ***************************************************************/

