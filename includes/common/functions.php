<?php

/**
 * VGSR Common Functions
 * 
 * @package VGSR
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the plugin's admin page url
 *
 * @since 0.1.0
 *
 * @uses add_query_arg()
 * @uses is_multisite()
 * @uses network_admin_url()
 * @uses admin_url()
 * @return string Admin page url
 */
function vgsr_get_admin_page_url() {
	return add_query_arg( 'page', 'vgsr', is_multisite() ? network_admin_url( 'settings.php' ) : admin_url( 'admin.php' ) );
}
