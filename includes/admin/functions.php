<?php

/**
 * VGSR Admin Functions
 *
 * @package VGSR
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** General ***************************************************************/

/**
 * Return the plugin's admin menu hook
 *
 * @since 0.1.0
 *
 * @return string Admin menu hook
 */
function vgsr_admin_menu_hook() {
	return is_multisite() ? 'network_admin_menu' : 'admin_menu';
}
