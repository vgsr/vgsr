<?php

/**
 * VGSR Core Functions
 *
 * @package VGSR
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** General ****************************************************************/

/**
 * Return the plugin's admin page url
 *
 * @since 0.1.0
 *
 * @return string Admin page url
 */
function vgsr_admin_url() {
	return add_query_arg( array( 'page' => 'vgsr' ), is_multisite() ? network_admin_url( 'settings.php' ) : admin_url( 'admin.php' ) );
}

/** Site *******************************************************************/

/**
 * Catch the manifest request and serve it for the main site
 *
 * @see vgsr_manifest_json()
 *
 * @since 0.1.0
 */
function vgsr_manifest_json_route() {

	// Bail when headers are already sent
	if ( headers_sent() )
		return;

	// Bail when this is not the main site
	if ( ! is_main_site() )
		return;

	// Requesting the manifest file
	if ( isset( $_GET['action'] ) && 'manifest' === $_GET['action'] ) {

		// Serve the json contents
		vgsr_manifest_json();

	// Navigating from the manifest
	} elseif ( isset( $_GET['homescreen'] ) && $_GET['homescreen'] ) {

		// Force login
		auth_redirect();
	}
}

/**
 * Output the manifest.json meta-tag
 *
 * This link provides information to the (mobile) browser on how
 * the website should be installed on the device as a web app.
 *
 * @since 0.1.0
 *
 * @uses is_main_site()
 */
function vgsr_manifest_meta_tag() {

	// Bail when this is not the main site
	if ( ! is_main_site() )
		return;

	// Append manifest link to the <head>
	printf( '<link rel="manifest" href="/?action=manifest">' . "\n" );
}

/**
 * Output the manifest.json content to the browser
 *
 * Provides information to install the website on your device as
 * a web app.
 *
 * @link http://html5doctor.com/web-manifest-specification/
 * @link https://medium.com/@franciov/how-to-make-your-web-app-installable-8b71571605e
 *
 * @since 0.1.0
 *
 * @uses has_site_icon()
 * @uses get_site_icon_url()
 * @uses wp_send_json()
 */
function vgsr_manifest_json() {

	// Define default params
	$params = array(
		'short_name'       => 'VGSR',
		'name'             => 'Vereniging van Gereformeerde Studenten te Rotterdam',
		// Always start at front page
		'start_url'        => '/?homescreen=1',
		'display'          => 'fullscreen',
		// Force orientation
		'orientation'      => 'portrait',
	);

	// Add icons
	if ( has_site_icon() ) {
		$params['icons'] = array(
			array(
				'src'     => esc_url( get_site_icon_url( 32 ) ),
				'sizes'   => '32x32',
			),
			array(
				'src'     => esc_url( get_site_icon_url( 64 ) ),
				'sizes'   => '32x32',
				'density' => 2,
			),
			array(
				'src'     => esc_url( get_site_icon_url( 64 ) ),
				'sizes'   => '64x64',
			),
			array(
				'src'     => esc_url( get_site_icon_url( 128 ) ),
				'sizes'   => '64x64',
				'density' => 2,
			),
			array(
				'src'     => esc_url( get_site_icon_url( 128 ) ),
				'sizes'   => '128x128',
			),
			array(
				'src'     => esc_url( get_site_icon_url( 256 ) ),
				'sizes'   => '128x128',
				'density' => 2,
			),
		);

	// Fallback to logo asset
	} else {

		// Use white logo with green background
		$params['background_color'] = '#33A537';
		$params['icons'] = array(
			array(
				'src'   => vgsr()->includes_url . 'assets/images/logo-wit.png',
				'sizes' => '32x32 64x64 128x128 200x200',
			),
		);
	}

	// Send json headers and output the content
	wp_send_json( apply_filters( 'vgsr_manifest_json', $params ) );
}

/** Comments ***************************************************************/

/**
 * Modify the approved status of a comment before setting it
 *
 * @since 0.1.0
 *
 * @uses get_user_by()
 * @uses is_user_vgsr()
 *
 * @param int|string $approved Approved status
 * @param array $commentdata New comment data
 * @return int|string Approved status
 */
function vgsr_pre_comment_approved( $approved, $commentdata ) {

	// Approve a VGSR user's comments without further moderation.
	if ( ! $approved && ! empty( $commentdata['user_id'] ) ) {
		$user = get_user_by( 'id', $commentdata['user_id'] );
		if ( $user && is_user_vgsr( $user->ID ) ) {
			$approved = 1;
		}
	}

	return $approved;
}
