<?php

/**
 * VGSR Event Organiser Actions
 *
 * @package VGSR
 * @subpackage Event Organiser
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Template ******************************************************************/

add_filter( 'document_title_parts',        'vgsr_eo_page_title'                  );
add_filter( 'get_the_archive_title',       'vgsr_eo_get_the_archive_title'       );
add_filter( 'get_the_archive_description', 'vgsr_eo_get_the_archive_description' );

/** Admin *********************************************************************/

add_filter( 'vgsr_admin_init', 'vgsr_eo_register_settings_fields' );
