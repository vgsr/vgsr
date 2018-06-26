<?php

/**
 * VGSR Admin Actions
 *
 * @package VGSR
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Sub-actions ***************************************************************/

add_action( vgsr_admin_menu_hook(), 'vgsr_admin_menu',              10 );
add_action( 'admin_init',           'vgsr_admin_init',              10 );
add_action( 'admin_head',           'vgsr_admin_head',              10 );
add_action( 'admin_footer',         'vgsr_admin_footer',            10 );
add_action( 'admin_notices',        'vgsr_admin_notices',           10 );

/** Main **********************************************************************/

add_action( 'vgsr_admin_init', 'vgsr_setup_updater',               999 );

/** Settings ******************************************************************/

add_action( 'vgsr_admin_init', 'vgsr_register_admin_settings',      10 );
add_action( 'vgsr_admin_init', 'vgsr_admin_save_network_settings', 100 );
