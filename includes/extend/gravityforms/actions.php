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
add_filter( 'gform_export_separator',   'vgsr_gf_export_separator'           );
add_filter( 'gform_export_fields',      'vgsr_gf_entry_export_fields',  5    );
add_filter( 'gform_export_field_value', 'vgsr_gf_export_field_value',   5, 4 );
