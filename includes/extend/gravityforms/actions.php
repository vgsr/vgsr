<?php

/**
 * VGSR Gravity Forms Actions
 * 
 * @package VGSR
 * @subpackage Gravity Forms
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Export
add_filter( 'gform_export_separator', 'vgsr_gf_export_separator' );
