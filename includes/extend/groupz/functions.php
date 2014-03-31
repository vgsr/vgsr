<?php

/**
 * VGSR Groupz Functions
 *
 * @package VGSR
 * @subpackage Extend
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Settings ***************************************************************/

/**
 * Add settings section for Groupz options
 *
 * @since 1.0.0
 * 
 * @param array $sections VGSR settings sections
 * @return array $sections
 */
function vgsr_groupz_settings_section( $sections = array() ) {
	$sections['vgsr_settings_groupz'] = array(
		'title'    => __('Groupz Settings', 'vgsr'),
		'callback' => 'vgsr_groupz_setting_callback_groupz_section',
		'page'     => 'vgsr'
	);

	return $sections;
}

/**
 * Add settings fields for Groupz options
 *
 * @since 1.0.0
 * 
 * @param array $fields VGSR settings fields
 * @return array $fields
 */
function vgsr_groupz_settings_fields( $fields = array() ) {
	$fields['vgsr_settings_groupz'] = (array) apply_filters( 'vgsr_groupz_settings_fields', array(
		
		// VGSR main group
		'vgsr_group_vgsr' => array(
			'title'             => __('Main group', 'vgsr'),
			'callback'          => 'vgsr_groupz_setting_callback_group_vgsr',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),
		
		// VGSR leden group
		'vgsr_group_leden' => array(
			'title'             => __('Leden group', 'vgsr'),
			'callback'          => 'vgsr_groupz_setting_callback_group_leden',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),

		// VGSR oud-leden group
		'vgsr_group_oudleden' => array(
			'title'             => __('Oud-leden group', 'vgsr'),
			'callback'          => 'vgsr_groupz_setting_callback_group_oudleden',
			'sanitize_callback' => 'intval',
			'args'              => array()
		)
	) );

	return $fields;
}

/**
 * Groupz settings section description for the settings page
 *
 * @since 1.0.0
 */
function vgsr_groupz_setting_callback_groupz_section() {
?>

	<p><?php esc_html_e('Set VGSR groups and other Groupz functionalities for VGSR.', 'vgsr'); ?></p>

<?php
}

/**
 * Main group settings field
 * 
 * @since 1.0.0
 *
 * @uses get_groups()
 * @uses vgsr_get_group_vgsr()
 */
function vgsr_groupz_setting_callback_group_vgsr() {
?>

	<select id="vgsr_group_vgsr" name="vgsr_group_vgsr">
		<option><?php _e('Select a group', 'vgsr'); ?></option>

		<?php foreach ( get_groups() as $group ) : ?>

		<option value="<?php echo $group->term_id; ?>" <?php selected( vgsr_get_group_vgsr(), $group->term_id ); ?>><?php echo $group->name; ?></option>

		<?php endforeach; ?>
	</select>
	<label for="vgsr_group_vgsr"><span class="description"><?php esc_html_e('The main VGSR group.', 'vgsr'); ?></span></label>

<?php
}

/**
 * Leden group settings field
 *
 * @since 1.0.0
 *
 * @uses get_groups()
 * @uses vgsr_get_group_leden()
 */
function vgsr_groupz_setting_callback_group_leden() {
?>

	<select id="vgsr_group_leden" name="vgsr_group_leden">
		<option><?php _e('Select a group', 'vgsr'); ?></option>

		<?php foreach ( get_groups() as $group ) : ?>

		<option value="<?php echo $group->term_id; ?>" <?php selected( vgsr_get_group_leden(), $group->term_id ); ?>><?php echo $group->name; ?></option>

		<?php endforeach; ?>
	</select>
	<label for="vgsr_group_leden"><span class="description"><?php esc_html_e('The leden VGSR group.', 'vgsr'); ?></span></label>

<?php
}

/**
 * Oud-leden group settings field
 *
 * @since 1.0.0
 * 
 * @uses get_groups()
 * @uses vgsr_get_group_oudleden()
 */
function vgsr_groupz_setting_callback_group_oudleden() {
?>

	<select id="vgsr_group_oudleden" name="vgsr_group_oudleden">
		<option><?php _e('Select a group', 'vgsr'); ?></option>

		<?php foreach ( get_groups() as $group ) : ?>

		<option value="<?php echo $group->term_id; ?>" <?php selected( vgsr_get_group_oudleden(), $group->term_id ); ?>><?php echo $group->name; ?></option>

		<?php endforeach; ?>
	</select>
	<label for="vgsr_group_oudleden"><span class="description"><?php esc_html_e('The oud-leden VGSR group.', 'vgsr'); ?></span></label>

<?php
}

/** Options ***************************************************************/

/**
 * Return the vgsr group ID
 *
 * @since 1.0.0
 *
 * @uses get_option()
 * @return int VGSR group ID
 */
function vgsr_get_group_vgsr() {
	return (int) get_option( 'vgsr_group_vgsr', 0 );
}

/**
 * Return the leden group ID
 *
 * @since 1.0.0
 *
 * @uses get_option()
 * @return int Leden group ID
 */
function vgsr_get_group_leden() {
	return (int) get_option( 'vgsr_group_leden', 0 );
}

/**
 * Return the oud-leden group ID
 *
 * @since 1.0.0
 *
 * @uses get_option()
 * @return int Oud-leden group ID
 */
function vgsr_get_group_oudleden() {
	return (int) get_option( 'vgsr_group_oudleden', 0 );
}

