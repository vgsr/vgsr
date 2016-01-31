<?php

/**
 * VGSR Template Functions
 * 
 * @package VGSR
 * @subpackage Template
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Meta *******************************************************************/

/**
 * Catch the manifest request and serve the json
 *
 * @since 0.1.0
 *
 * @uses is_main_site()
 * @uses vgsr_manifest_json()
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
		vgsr_manifest_json();
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
		'start_url'        => '/',
		'display'          => 'fullscreen',
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

/** Login ******************************************************************/

/**
 * Return the site's home url for the login logo
 *
 * @since 0.1.0
 *
 * @uses home_url()
 *
 * @param string $url Login header url
 * @return string Login header url
 */
function vgsr_login_header_url( $url ) {
	return home_url();
}

/**
 * Return the site's title for the login logo
 * 
 * @since 0.1.0
 *
 * @param string $title Login header title
 * @return string Login header title
 */
function vgsr_login_header_title( $title ) {
	return __( 'Vereniging van Gereformeerde Studenten te Rotterdam', 'vgsr' );
}

/**
 * Output scripts for the login page
 * 
 * @since 0.1.0
 *
 * @uses vgsr()
 */
function vgsr_login_enqueue_scripts() { 
	$vgsr = vgsr(); ?>

	<style id="vgsr-login">
		.login h1 a {
			background-image: url('<?php echo $vgsr->assets_url . 'images/logo.svg'; ?>');
			-webkit-background-size: 94px;
			background-size: 94px;
			width: 94px;
			height: 94px;
		}

		/* Firefox-specific */
		@-moz-document url-prefix() {
			/**
			 * Firefox does not render letter-spacing and word-spacing correctly
			 * on svg textpaths, so fall back to an image.
			 * 
			 * @link https://bugzilla.mozilla.org/show_bug.cgi?id=371787
			 */
			.login h1 a {
				background-image: url('<?php echo $vgsr->assets_url . 'images/logo.png'; ?>');
			}
		}
	</style>

	<?php
}
