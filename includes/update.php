<?php

/**
 * VGSR Updater
 *
 * @package VGSR
 * @subpackage Updater
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * If there is no raw DB version, this is the first installation
 *
 * @since 0.0.1
 *
 * @return bool True if update, False if not
 */
function vgsr_is_install() {
	return ! vgsr_get_db_version_raw();
}

/**
 * Compare the VGSR version to the DB version to determine if updating
 *
 * @since 0.0.1
 *
 * @return bool True if update, False if not
 */
function vgsr_is_update() {
	$raw    = (int) vgsr_get_db_version_raw();
	$cur    = (int) vgsr_get_db_version();
	$retval = (bool) ( $raw < $cur );
	return $retval;
}

/**
 * Determine if VGSR is being activated
 *
 * Note that this function currently is not used in VGSR core and is here
 * for third party plugins to use to check for VGSR activation.
 *
 * @since 0.0.1
 *
 * @return bool True if activating VGSR, false if not
 */
function vgsr_is_activation( $basename = '' ) {
	global $pagenow;

	$vgsr   = vgsr();
	$action = false;

	// Bail if not in admin/plugins
	if ( ! ( is_admin() && ( 'plugins.php' === $pagenow ) ) ) {
		return false;
	}

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' !== $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' !== $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not activating
	if ( empty( $action ) || ! in_array( $action, array( 'activate', 'activate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being activated
	if ( $action === 'activate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && ! empty( $vgsr->basename ) ) {
		$basename = $vgsr->basename;
	}

	// Bail if no basename
	if ( empty( $basename ) ) {
		return false;
	}

	// Is VGSR being activated?
	return in_array( $basename, $plugins );
}

/**
 * Determine if VGSR is being deactivated
 *
 * @since 0.0.1
 * 
 * @return bool True if deactivating VGSR, false if not
 */
function vgsr_is_deactivation( $basename = '' ) {
	global $pagenow;

	$vgsr   = vgsr();
	$action = false;

	// Bail if not in admin/plugins
	if ( ! ( is_admin() && ( 'plugins.php' === $pagenow ) ) ) {
		return false;
	}

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' !== $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' !== $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not deactivating
	if ( empty( $action ) || ! in_array( $action, array( 'deactivate', 'deactivate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being deactivated
	if ( $action === 'deactivate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && ! empty( $vgsr->basename ) ) {
		$basename = $vgsr->basename;
	}

	// Bail if no basename
	if ( empty( $basename ) ) {
		return false;
	}

	// Is VGSR being deactivated?
	return in_array( $basename, $plugins );
}

/**
 * Update the DB to the latest version
 *
 * @since 0.0.1
 */
function vgsr_version_bump() {
	update_site_option( 'vgsr_db_version', vgsr_get_db_version() );
}

/**
 * Setup the VGSR updater
 *
 * @since 0.0.1
 */
function vgsr_setup_updater() {

	// Bail if no update needed
	if ( ! vgsr_is_update() )
		return;

	// Call the automated updater
	vgsr_version_updater();
}

/**
 * VGSR's version updater looks at what the current database version is, and
 * runs whatever other code is needed.
 *
 * This is most-often used when the data schema changes, but should also be used
 * to correct issues with VGSR meta-data silently on software update.
 *
 * @since 0.0.1
 *
 * @todo Log update event
 */
function vgsr_version_updater() {

	// Get the raw database version
	$raw_db_version = (int) vgsr_get_db_version_raw();

	/** 0.1.0 Branch ********************************************************/

	// 0.1.0
	if ( $raw_db_version < 10 ) {

		// Do stuff
	}

	/** All done! *********************************************************/

	// Bump the version
	vgsr_version_bump();
}
