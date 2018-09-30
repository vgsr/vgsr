<?php

/**
 * VGSR Extension for Initials Default Avatar
 *
 * @package VGSR
 * @subpackage Initials Default Avatar
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Initials_Default_Avatar' ) ) :
/**
 * The VGSR Initials Default Avatar class
 *
 * @since 1.0.0
 */
class VGSR_Initials_Default_Avatar {

	/**
	 * Setup this class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {
		add_filter( 'initials_default_avatar_user_data', array( $this, 'user_data' ), 10, 3 );
	}

	/** Public methods **************************************************/

	/**
	 * Modify the avatar's user data
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Avatar data
	 * @param mixed $avatar_id Avatar identifier
	 * @param string $name Avatar name
	 * @return array Avatar data
	 */
	public function user_data( $data, $avatar_id, $name ) {

		// Only keep uppercase letters from a vgsr user's name
		if ( $avatar_id && is_numeric( $avatar_id ) && is_user_vgsr( $avatar_id ) ) {
			$data['initials'] = preg_replace( '/[^A-Z]/', '', $name );
		}

		return $data;
	}
}

/**
 * Setup the extension logic for Initials Default Avatar
 *
 * @since 1.0.0
 *
 * @uses VGSR_Initials_Default_Avatar
 */
function vgsr_setup_initials_default_avatar() {
	vgsr()->extend->initals_default_avatar = new VGSR_Initials_Default_Avatar;
}

endif; // class_exists
