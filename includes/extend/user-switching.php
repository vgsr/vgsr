<?php

/**
 * VGSR Extension for User Switching
 *
 * @package VGSR
 * @subpackage User Switching
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_User_Switching' ) ) :
/**
 * The VGSR User Switching class
 *
 * @since 1.0.0
 */
class VGSR_User_Switching {

	/**
	 * Setup this class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Bail when User Switching is not active
		if ( ! class_exists( 'user_switching' ) )
			return;

		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {
		global $user_switching;

		// Replace BP button action
		remove_action( 'bp_directory_members_actions', array( $user_switching, 'action_bp_button' ), 11 );
		add_action(    'bp_directory_members_actions', array( $this,           'action_bp_button' ), 11 );
	}

	/** Public methods **************************************************/

	/**
	 * Output user switching button for the members directory item
	 *
	 * Removed restriction-check for `bp_is_members_directory()` from original.
	 *
	 * @see User_Switching::action_bp_button()
	 *
	 * @since 1.0.0
	 */
	public function action_bp_button() {
		$user = null;

		if ( bp_is_user() ) {
			$user = get_userdata( bp_displayed_user_id() );
		} else { // Assume any other page, even outside the members directory
			$user = get_userdata( bp_get_member_user_id() );
		}

		if ( ! $user ) {
			return;
		}

		$link = user_switching::maybe_switch_url( $user );

		if ( ! $link ) {
			return;
		}

		$link = add_query_arg( array(
			'redirect_to' => urlencode( bp_core_get_user_domain( $user->ID ) ),
		), $link );

		$components = array_keys( buddypress()->active_components );

		echo bp_get_button( array(
			'id'         => 'user_switching',
			'component'  => reset( $components ),
			'link_href'  => esc_url( $link ),
			'link_text'  => esc_html__( 'Switch&nbsp;To', 'user-switching' ),
			'wrapper_id' => 'user_switching_switch_to',
		) );
	}
}

/**
 * Setup the extension logic for User Switching
 *
 * @since 1.0.0
 *
 * @uses VGSR_User_Switching
 */
function vgsr_setup_user_switching() {
	vgsr()->extend->user_switching = new VGSR_User_Switching;
}

endif; // class_exists
