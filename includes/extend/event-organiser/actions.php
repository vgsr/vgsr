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

add_filter( 'get_the_archive_title',       'vgsr_eo_get_the_archive_title'       );
add_filter( 'get_the_archive_description', 'vgsr_eo_get_the_archive_description' );
