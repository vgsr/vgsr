<?php

/**
 * Main VGSR Ancienniteit Class
 *
 * @package VGSR
 * @subpackage Plugins
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Ancienniteit' ) ) :
/**
 * Loads Ancienniteit Extension
 *
 * @since 0.0.3
 */
class VGSR_Ancienniteit {

	/** Setup Methods ******************************************************/

	/**
	 * The main VGSR Ancienniteit loader
	 * 
	 * @since 0.0.3
	 */
	public function __construct() {

		// Bail if BP Groups component is not active
		if ( ! function_exists( 'buddypress' ) || ! bp_is_active( 'groups' ) )
			return;

		// Hook settings
		add_filter( 'vgsr_settings_fields_bp_groups', array( $this, 'add_settings_field' ) );

		// User queries
		add_filter( 'pre_user_query', array( $this, 'user_query_orderby' ), 9 );
	}

	/** Methods ************************************************************/

	/**
	 * Add ancienniteit BP groups settings field
	 *
	 * @since 0.0.3
	 * 
	 * @param array $settings Settings
	 * @return array Settings
	 */
	public function add_settings_field( $settings ) {

		// Add setting
		$settings['vgsr_always_ancienniteit'] = array(
			'title'             => __( 'Always anci&#235;nniteit', 'vgsr' ),
			'callback'          => array( $this, 'the_settings_field' ),
			'sanitize_callback' => 'intval',
			'args'              => array()
		);

		return $settings;
	}

	/**
	 * Always ancienniteit settings field
	 *
	 * @since 0.0.3
	 *
	 * @uses get_site_option()
	 */
	public function the_settings_field() { ?>

		<input id="vgsr_always_ancienniteit" name="vgsr_always_ancienniteit" type="checkbox" value="1" <?php checked( get_site_option( 'vgsr_always_ancienniteit' ) ); ?> />
		<label for="vgsr_always_ancienniteit"><span class="description"><?php esc_html_e( 'Return VGSR group members always in anci&#235;nniteit.', 'vgsr' ); ?></span></label>

		<?php
	}

	/**
	 * Amend user query for always ancienniteit
	 *
	 * @since 0.0.3
	 * 
	 * @param WP_User_Query $query
	 */
	public function user_query_orderby( $query ) {

		// Bail if not always required
		if ( ! get_site_option( 'vgsr_always_ancienniteit' ) )
			return;

		$qv = $query->query_vars;

		// Bail if not querying a VGSR group
		if ( ! isset( $qv['group_id'] ) || ! vgsr_is_vgsr_group( $qv['group_id'] ) )
			return;

		// @todo Unless explicitly set elsewhere

		// Set orderby value
		$query->query_vars['orderby'] = 'ancienniteit';
	}
}

endif; // class_exists