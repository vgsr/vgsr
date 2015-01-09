<?php

/**
 * VGSR Template Functions
 * 
 * @package VGSR
 * @subpackage Template
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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
