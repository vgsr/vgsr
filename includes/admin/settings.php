<?php

/**
 * VGSR Settings Functions
 *
 * @package VGSR
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined('ABSPATH' ) ) exit;

/**************************************************************************/

/**
 * Return admin settings sections
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_admin_get_settings_sections' with the sections
 * @return array Settings sections
 */
function vgsr_admin_get_settings_sections() {
	return (array) apply_filters( 'vgsr_admin_get_settings_sections', array(

		// Main settings
		'vgsr_settings_main' => array(
			'title'    => __( 'Main Settings', 'vgsr' ),
			'callback' => 'vgsr_admin_setting_callback_main_section',
			'page'     => 'vgsr'
		),

		// Access settings
		'vgsr_settings_access' => array(
			'title'    => __( 'Access Settings', 'vgsr' ),
			'callback' => 'vgsr_admin_setting_callback_access_section',
			'page'     => 'vgsr'
		)
	) );
}

/**
 * Return admin settings fields
 *
 * @since 0.0.1
 *
 * @uses apply_filters() Calls 'vgsr_admin_get_settings_fields' with the fields
 * @return array Settings fields
 */
function vgsr_admin_get_settings_fields() {
	return (array) apply_filters( 'vgsr_admin_get_settings_fields', array(

		/** Main Section **************************************************/
		
		'vgsr_settings_main' => array(
			// Fields
		),

		/** Access Section ************************************************/
		
		'vgsr_settings_access' => array(

			// Private reading post types
			// '_vgsr_private_reading_post_types' => array(
			// 	'title'             => __( 'Private Reading', 'vgsr' ),
			// 	'callback'          => 'vgsr_setting_callback_private_reading_post_types',
			// 	'sanitize_callback' => '',
			// 	'args'              => array()
			// )
		)
	) );
}

/**
 * Get settings fields by section
 *
 * @since 0.0.1
 * 
 * @param string $section_id Section id
 * @return mixed False if section is invalid, array of fields otherwise
 */
function vgsr_admin_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) )
		return false;

	$fields = vgsr_admin_get_settings_fields();
	$retval = isset( $fields[$section_id] ) ? $fields[$section_id] : false;

	return (array) apply_filters( 'vgsr_admin_get_settings_fields_for_section', $retval, $section_id );
}

/** Main Section **********************************************************/

/**
 * Main settings section description for the settings page
 * 
 * @since 0.0.1
 */
function vgsr_admin_setting_callback_main_section() {
?>

	<p><?php esc_html_e( 'Set the main functionality options for VGSR.', 'vgsr' ); ?></p>

<?php
}

/** Access Section ********************************************************/

/**
 * Access settings section description for the settings page
 * 
 * @since 0.0.6
 */
function vgsr_admin_setting_callback_access_section() { ?>

	<p><?php esc_html_e( 'Tweak here the settings for accessing your site contents.', 'vgsr' ); ?></p>

	<?php
}

/**
 * Private reading post types settings field
 *
 * @since 0.0.6
 *
 * @uses vgsr_get_private_reading_post_types()
 */
function vgsr_setting_callback_private_reading_post_types() {
	global $wp_post_types;

	// Get the saved post types
	$option = vgsr_get_private_reading_post_types(); ?>

	<p><?php _e( 'Select the post types of which users in VGSR groups will read posts that are privately published.', 'vgsr' ); ?></p>
	<ul>
		<?php foreach ( $wp_post_types as $post_type ) : ?>

		<li>
			<label>
				<input type="checkbox" name="_vgsr_private_reading_post_types[]" value="<?php echo $post_type->name; ?>" <?php checked( in_array( $post_type->name, $option ) ); ?>/> 
				<?php echo $post_type->labels->name; ?>
			</label>
		</li>

		<?php endforeach; ?>
	</ul>

	<?php
}

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @since 0.0.1
 *
 * @uses screen_icon() To display the screen icon
 * @uses settings_fields() To output the hidden fields for the form
 * @uses do_settings_sections() To output the settings sections
 */
function vgsr_admin_settings() {

	// Define the form destination
	$destination = is_multisite() ? 'edit.php?action=vgsr' : 'options.php'; ?>

	<div class="wrap">
		<?php screen_icon(); ?>

		<h2><?php esc_html_e( 'VGSR Settings', 'vgsr' ); ?></h2>

		<form action="<?php echo $destination; ?>" method="post">

			<?php settings_fields( 'vgsr' ); ?>
			<?php do_settings_sections( 'vgsr' ); ?>
			<?php submit_button(); ?>

		</form>
	</div>

<?php
}

/** Helpers *******************************************************************/

/**
 * Contextual help for VGSR settings page
 *
 * @since 0.0.1
 * 
 * @uses get_current_screen()
 */
function vgsr_admin_settings_help() {

	$current_screen = get_current_screen();

	// Bail if current screen could not be found
	if ( empty( $current_screen ) )
		return;

	// Overview
	$current_screen->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __( 'Overview', 'vgsr' ),
		'content' => '<p>' . __( 'This screen provides access to all of the VGSR settings.',                            'vgsr' ) . '</p>' .
					 '<p>' . __( 'Please see the additional help tabs for more information on each indiviual section.', 'vgsr' ) . '</p>'
	) );

	// Main Settings
	$current_screen->add_help_tab( array(
		'id'      => 'main_settings',
		'title'   => __( 'Main Settings', 'vgsr' ),
		'content' => '<p>' . __( 'In the Main Settings you have a number of options:', 'vgsr' ) . '</p>' .
					 '<p>' .
						'<ul>' .
							'<li>' . __( 'You can choose to lock a post after a certain number of minutes. "Locking post editing" will prevent the author from editing some amount of time after saving a post.',              'vgsr' ) . '</li>' .
							'<li>' . __( '"Throttle time" is the amount of time required between posts from a single author. The higher the throttle time, the longer a user will need to wait between posting to the forum.', 'vgsr' ) . '</li>' .
							'<li>' . __( 'Favorites are a way for users to save and later return to topics they favor. This is enabled by default.',                                                                           'vgsr' ) . '</li>' .
							'<li>' . __( 'Subscriptions allow users to subscribe for notifications to topics that interest them. This is enabled by default.',                                                                 'vgsr' ) . '</li>' .
							'<li>' . __( 'Topic-Tags allow users to filter topics between forums. This is enabled by default.',                                                                                                'vgsr' ) . '</li>' .
							'<li>' . __( '"Anonymous Posting" allows guest users who do not have accounts on your site to both create topics as well as replies.',                                                             'vgsr' ) . '</li>' .
							'<li>' . __( 'The Fancy Editor brings the luxury of the Visual editor and HTML editor from the traditional WordPress dashboard into your theme.',                                                  'vgsr' ) . '</li>' .
							'<li>' . __( 'Auto-embed will embed the media content from a URL directly into the replies. For example: links to Flickr and YouTube.',                                                            'vgsr' ) . '</li>' .
						'</ul>' .
					'</p>' .
					'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.', 'vgsr' ) . '</p>'
	) );

	// Groupz Settings
	$current_screen->add_help_tab( array(
		'id'      => 'groupz_settings',
		'title'   => __( 'Per Page', 'vgsr' ),
		'content' => '<p>' . __( 'Per Page settings allow you to control the number of topics and replies appear on each page.',                                                    'vgsr' ) . '</p>' .
					 '<p>' . __( 'This is comparable to the WordPress "Reading Settings" page, where you can set the number of posts that should show on blog pages and in feeds.', 'vgsr' ) . '</p>' .
					 '<p>' . __( 'These are broken up into two separate groups: one for what appears in your theme, another for RSS feeds.',                                        'vgsr' ) . '</p>'
	) );

	// Help Sidebar
	$current_screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'vgsr' ) . '</strong></p>' .
		'<p>' . __( '<a href="http://codex.vgsr.nl" target="_blank">VGSR Documentation</a>',       'vgsr' ) . '</p>' .
		'<p>' . __( '<a href="http://www.vgsr.nl/forum/" target="_blank">VGSR Support Forums</a>', 'vgsr' ) . '</p>'
	);
}

/**
 * Output settings API option
 *
 * @since 0.0.1
 *
 * @uses vgsr_get_form_option()
 *
 * @param string $option
 * @param string $default
 * @param bool $slug
 */
function vgsr_form_option( $option, $default = '' , $slug = false ) {
	echo vgsr_get_form_option( $option, $default, $slug );
}
	/**
	 * Return settings API option
	 *
	 * @since 0.0.1
	 *
	 * @uses get_option()
	 * @uses esc_attr()
	 * @uses apply_filters()
	 *
	 * @param string $option
	 * @param string $default
	 * @param bool $slug
	 */
	function vgsr_get_form_option( $option, $default = '', $slug = false ) {

		// Get the option and sanitize it
		$value = get_option( $option, $default );

		// Slug?
		if ( true === $slug ) {
			$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
		} else {
			$value = esc_attr( $value );
		}

		// Fallback to default
		if ( empty( $value ) )
			$value = $default;

		// Allow plugins to further filter the output
		return apply_filters( 'vgsr_get_form_option', $value, $option );
	}
