<?php

/**
 * VGSR Core Functions
 *
 * @package VGSR
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Versions ******************************************************************/

/**
 * Output the plugin version
 *
 * @since 1.0.0
 */
function vgsr_version() {
	echo vgsr_get_version();
}

	/**
	 * Return the plugin version
	 *
	 * @since 1.0.0
	 *
	 * @return string The plugin version
	 */
	function vgsr_get_version() {
		return vgsr()->version;
	}

/**
 * Output the plugin database version
 *
 * @since 1.0.0
 */
function vgsr_db_version() {
	echo vgsr_get_db_version();
}

	/**
	 * Return the plugin database version
	 *
	 * @since 1.0.0
	 *
	 * @return string The plugin version
	 */
	function vgsr_get_db_version() {
		return vgsr()->db_version;
	}

/**
 * Output the plugin database version directly from the database
 *
 * @since 1.0.0
 */
function vgsr_db_version_raw() {
	echo vgsr_get_db_version_raw();
}

	/**
	 * Return the plugin database version directly from the database
	 *
	 * @since 1.0.0
	 *
	 * @return string The current plugin version
	 */
	function vgsr_get_db_version_raw() {
		return get_option( 'vgsr_db_version', '' );
	}

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

/** Login ******************************************************************/

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
 * Add additional scripts for the login page
 *
 * @since 0.1.0
 */
function vgsr_login_enqueue_scripts() {

	// Define style url
	$style = array();

	// Replace the WP logo
	$style[] = ".login h1 a { background-image: url('" . vgsr()->assets_url . "images/logo.svg'); -webkit-background-size: 94px; background-size: 94px; width: 94px; height: 94px; }";

	/**
	 * Firefox does not render letter-spacing and word-spacing correctly
	 * on svg textpaths, so fall back to an image.
	 *
	 * @link https://bugzilla.mozilla.org/show_bug.cgi?id=371787
	 */	
	$style[] = "@-moz-document url-prefix() {";
	$style[] = ".login h1 a { background-image: url('" . vgsr()->assets_url . "images/logo.png'; ?>'); }";
	$style[] = "}";

	// Append additional styles
	if ( ! empty( $style ) ) {
		wp_add_inline_style( 'login', implode( "\n", $style ) );
	}
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
				'src'   => vgsr()->assets_url . 'images/logo-wit.png',
				'sizes' => '32x32 64x64 128x128 200x200',
			),
		);
	}

	// Send json headers and output the content
	wp_send_json( apply_filters( 'vgsr_manifest_json', $params ) );
}

/** Nav Menus **************************************************************/

/**
 * Setup nav menu item details for certain pages
 *
 * @since 1.0.0
 *
 * @param WP_Post $menu_item Nav menu item object
 * @return WP_Post Nav menu item object
 */
function vgsr_setup_nav_menu_item( $menu_item ) {

	// Post format
	if ( 'post_format' === $menu_item->object ) {
		$term = get_term( $menu_item->object_id );

		// This is the parent page
		if ( $term && has_post_format( 'gallery' ) && 'gallery' === str_replace( 'post-format-', '', $term->slug ) ) {
			$menu_item->classes[] = 'current_page_parent';
			$menu_item->classes[] = 'current-menu-parent';
		}
	}

	return $menu_item;
}

/**
 * Modify the sorted list of menu items
 *
 * @since 1.0.0
 *
 * @param  array $items Menu items
 * @param  array $args Arguments for `wp_nav_menu()`
 * @return array Menu items
 */
function vgsr_nav_menu_objects( $items, $args ) {

	// When 404-ing or when on a gallery page
	if ( is_404() || has_post_format( 'gallery' ) ) {
		$posts_page = (int) get_option( 'page_for_posts' );

		foreach ( $items as $k => $item ) {

			// Remove the posts page's parent status/class. By default WordPress
			// appoints the posts page as parent for non-page pages. Please not.
			if ( $item->object_id == $posts_page && 'post_type' == $item->type && in_array( 'current_page_parent', $item->classes ) ) {
				unset( $items[ $k ]->classes[ array_search( 'current_page_parent', $item->classes ) ] );
			}
		}
	}

	return $items;
}

/** Taxonomy ***************************************************************/

/**
 * Modify the categories in the category list
 *
 * @since 1.0.0
 *
 * @param array $cats Post categories
 * @param int $post_id Post ID
 * @return array Post categories
 */
function vgsr_the_category_list( $cats, $post_id ) {

	// Remove the default category ('uncategorized') from the list
	$cats = array_values( wp_list_filter( $cats, array( 'term_id' => (int) get_option( 'default_category' ) ), 'NOT' ) );

	return $cats;
}

/**
 * Modify the category display name
 *
 * When the category list is empty, `the_category_list()` will default
 * to the unlinked text 'Uncategorized'. As we don't want that, this
 * filter is in place to undo that.
 *
 * @since 1.0.0
 *
 * @param string $cat Category name
 * @return string Category name
 */
function vgsr_the_category( $cat ) {

	// In the loop, hide the 'uncategorized' (default) category
	if ( __( 'Uncategorized' ) === $cat && ! is_admin() && in_the_loop() ) {
		$cat = '';
	}

	return $cat;
}

/** Comments ***************************************************************/

/**
 * Modify the approved status of a comment before setting it
 *
 * @since 0.1.0
 *
 * @param int|string $approved Approved status
 * @param array $commentdata New comment data
 * @return int|string Approved status
 */
function vgsr_pre_comment_approved( $approved, $commentdata ) {

	// Approve a vgsr user's comments without further moderation.
	if ( ! $approved && ! empty( $commentdata['user_id'] ) ) {
		$user = get_user_by( 'id', $commentdata['user_id'] );

		// Approve the vgsr user's comment
		if ( $user && is_user_vgsr( $user->ID ) ) {
			$approved = 1;
		}
	}

	return $approved;
}

/** Data *******************************************************************/

/**
 * Return the collection of possible surname prefixes
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'vgsr_surname_prefixes'
 * @return array Surname prefixes
 */
function vgsr_surname_prefixes() {
	$prefixes = _x(
		"aan,aan de,aan den,aan der,aan het,aan 't,bij,bij de,bij den,bij het,bij 't,boven d',d',de,den,der,in,in de,in den,in der,in het,in 't,onder,onder de,onder den,onder het,onder 't,op,over,over de,over den,over het,over 't,op de,op den,op der,op het,op 't,op ten,'s,'t,te,ten,ter,tot,uit,uit de,uit den,uit het,uit 't,uit ten,uijt,uijt de,uijt den,uijt het,uijt 't,uijt ten,van,van de,van den,van der,van het,van 't,van ter,ver,voor,voor de,voor den,voor in 't",
		'Comma-separated list of surname prefixes in your language',
		'vgsr'
	);

	// Create array and sanitize
	$prefixes = array_map( 'trim', explode( ',', $prefixes ) );

	return (array) apply_filters( 'vgsr_surname_prefixes', $prefixes );
}
