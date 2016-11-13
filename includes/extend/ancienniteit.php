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

		// Hook settings
		add_filter( 'vgsr_settings_fields_members', array( $this, 'add_settings_field' ) );

		// User queries
		add_filter( 'pre_user_query', array( $this, 'user_query_orderby' ), 9 );
	}

	/** Methods ************************************************************/

	/**
	 * Add ancienniteit settings field
	 *
	 * @since 0.0.3
	 * 
	 * @param array $settings Settings
	 * @return array Settings
	 */
	public function add_settings_field( $settings ) {

		// Add setting
		$settings['vgsr_force_ancienniteit'] = array(
			'title'             => __( 'Force seniority', 'vgsr' ),
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

		<input id="vgsr_force_ancienniteit" name="vgsr_force_ancienniteit" type="checkbox" value="1" <?php checked( get_site_option( 'vgsr_force_ancienniteit' ) ); ?> />
		<label for="vgsr_force_ancienniteit"><span class="description"><?php esc_html_e( 'Return VGSR members always sorted by seniority.', 'vgsr' ); ?></span></label>

		<?php
	}

	/**
	 * Amend user query for always ancienniteit
	 *
	 * @since 0.0.3
	 *
	 * @uses get_site_option()
	 * @uses vgsr_has_query_var()
	 * 
	 * @param WP_User_Query $query
	 */
	public function user_query_orderby( $query ) {

		// Bail when not forcing ancienniteit
		if ( ! get_site_option( 'vgsr_force_ancienniteit' ) )
			return;

		// Bail when not querying for VGSR
		if ( ! vgsr_has_query_var( $query ) )
			return;

		// @todo Unless explicitly set elsewhere

		// Set orderby value
		$query->query_vars['orderby'] = 'ancienniteit';
	}
}

endif; // class_exists