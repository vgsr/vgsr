<?php

/**
 * Main VGSR Admin Class
 *
 * @package VGSR
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Admin' ) ) :
/**
 * Loads the VGSR plugin admin area
 *
 * @since 0.0.1
 */
class VGSR_Admin {

	/**
	 * The main VGSR admin loader
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Admin globals
	 *
	 * @since 0.0.1
	 */
	private function setup_globals() {

		/** Paths *************************************************************/

		$this->admin_dir = trailingslashit( vgsr()->includes_dir . 'admin'  ); // Admin path
		$this->admin_url = trailingslashit( vgsr()->includes_url . 'admin'  ); // Admin url

		/** Details ***********************************************************/

		$this->parent_page        = is_multisite() ? 'settings.php' : 'options-general.php';
		$this->minimum_capability = is_multisite() ? 'manage_network_options' : 'manage_options';
		$this->plugin_screen_id   = 'settings_page_vgsr';

		if ( is_network_admin() ) {
			$this->plugin_screen_id .= '-network';
		}
	}

	/**
	 * Include required files
	 *
	 * @since 0.0.1
	 */
	private function includes() {

		// Core
		require( $this->admin_dir . 'functions.php'   );
		require( $this->admin_dir . 'settings.php'    );

		// Actions
		require( $this->admin_dir . 'actions.php'     );
		require( $this->admin_dir . 'sub-actions.php' );
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @since 0.0.1
	 */
	private function setup_actions() {

		// Bail to prevent interfering with the deactivation process
		if ( vgsr_is_deactivation() )
			return;

		/** General Actions ***************************************************/

		add_action( 'vgsr_admin_menu',              array( $this, 'vgsr_admin_menu'   ), 10 );
		add_action( 'admin_menu',                   array( $this, 'admin_menu'        ), 10 );
		add_action( 'vgsr_admin_init',              array( $this, 'admin_redirect'    ),  0 );
		add_action( 'vgsr_register_admin_settings', array( $this, 'register_settings' ), 10 );
		add_action( 'admin_enqueue_scripts',        array( $this, 'enqueue_scripts'   ), 10 );

		/** Filters ***********************************************************/

		// Plugin action links
		add_filter( 'plugin_action_links',               array( $this, 'plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

		// Map settings capabilities
		add_filter( 'vgsr_map_meta_caps', array( $this, 'map_settings_meta_caps' ), 10, 4 );

		// Post exclusivity
		add_action( 'vgsr_admin_init',             array( $this, 'setup_vgsr_post_columns' )        );
		add_filter( 'display_post_states',         array( $this, 'display_post_states'     ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'vgsr_post_meta_field'    )        );
		add_action( 'quick_edit_custom_box',       array( $this, 'vgsr_post_quick_edit'    ), 10, 2 );
		add_action( 'save_post',                   array( $this, 'vgsr_post_save_meta'     )        );

		// Users
		add_filter( 'wpmu_users_columns',            array( $this, 'get_users_columns'      )        );
		add_filter( 'manage_users_columns',          array( $this, 'get_users_columns'      )        );
		add_filter( 'manage_users_sortable_columns', array( $this, 'sortable_users_columns' )        );
		add_filter( 'manage_users-network_sortable_columns', array( $this, 'sortable_users_columns' )        );
		add_filter( 'manage_users_custom_column',    array( $this, 'users_columns_content'  ), 10, 3 );

		/** Dependencies ******************************************************/

		// Allow plugins to modify these actions
		do_action_ref_array( 'vgsr_admin_loaded', array( &$this ) );
	}

	/**
	 * Modify the plugin core admin menus
	 *
	 * @since 0.0.1
	 */
	public function vgsr_admin_menu() {

		// When settings are enabled and admin pages were registered
		if ( current_user_can( 'vgsr_settings_page' ) && vgsr_admin_page_has_pages() ) {

			// Register admin page
			$hook = add_submenu_page(
				$this->parent_page,
				esc_html_x( 'VGSR Settings', 'settings page title', 'vgsr' ),
				esc_html_x( 'VGSR',          'settings menu title', 'vgsr' ),
				$this->minimum_capability,
				vgsr_admin_page_get_current_page(),
				'vgsr_admin_page'
			);

			// Register admin page hooks
			add_action( "load-$hook",         'vgsr_load_admin_page'   );
			add_action( "admin_head-$hook",   'vgsr_admin_page_head'   );
			add_action( "admin_footer-$hook", 'vgsr_admin_page_footer' );
		}
	}

	/**
	 * Modify the site's admin menus
	 *
	 * @since 0.2.0
	 *
	 * @global array $menu
	 */
	public function admin_menu() {
		global $menu;

		// For non-admin non-vgsr users
		if ( ! current_user_can( $this->minimum_capability ) && ! is_user_vgsr() ) {

			// Remove all admin pages except the user's profile
			foreach ( $menu as $index => $args ) {
				if ( isset( $args[2] ) && 'profile.php' !== $args[2] ) {
					remove_menu_page( $args[2] );
				}
			}
		}
	}

	/**
	 * Handle admin redirections
	 *
	 * @since 0.2.0
	 *
	 * @global string $pagenow
	 *
	 * @uses apply_filters() Calls 'vgsr_admin_redirect_url'
	 */
	public function admin_redirect() {
		global $pagenow;

		$location = false;

		// For non-admin non-vgsr users block all admin pages except the profile page
		if ( ! current_user_can( $this->minimum_capability ) && ! is_user_vgsr() && 'profile.php' !== $pagenow ) {
			$location = get_edit_profile_url();
		}

		// Allow filtering redirect url
		if ( $location = apply_filters( 'vgsr_admin_redirect_url', $location ) ) {
			wp_safe_redirect( $location );
			exit;
		}
	}

	/**
	 * Maps settings capabilities
	 *
	 * @since 0.0.1
	 *
	 * @uses apply_filters() Calls 'vgsr_map_settings_meta_caps'
	 *
 	 * @param array $caps Capabilities for meta capability
	 * @param string $cap Capability name
	 * @param int $user_id User id
	 * @param mixed $args Arguments
	 * @return array Actual capabilities for meta capability
	 */
	public function map_settings_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

		// What capability is being checked?
		switch ( $cap ) {

			// Admins
			case 'vgsr_settings_page' :   // Settings - Page
			case 'vgsr_settings_main' :   // Settings - General
			case 'vgsr_settings_access' : // Settings - Access
				$caps = array( vgsr()->admin->minimum_capability );
				break;
		}

		return apply_filters( 'vgsr_map_settings_meta_caps', $caps, $cap, $user_id, $args );
	}

	/**
	 * Register the settings
	 *
	 * @since 0.0.1
	 */
	public function register_settings() {

		// Bail if no sections available
		$sections = vgsr_admin_get_settings_sections();
		if ( empty( $sections ) )
			return false;

		// Loop through sections
		foreach ( (array) $sections as $section_id => $section ) {

			// Only proceed if current user can see this section
			if ( ! current_user_can( $section_id ) )
				continue;

			// Only add section and fields if section has fields
			$fields = vgsr_admin_get_settings_fields_for_section( $section_id );
			if ( empty( $fields ) )
				continue;

			// Define section page
			if ( ! empty( $section['page'] ) ) {
				$page = $section['page'];
			} else {
				$page = 'vgsr';
			}

			// Add the section
			add_settings_section( $section_id, $section['title'], $section['callback'], $page );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {

				// Add the field
				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					add_settings_field( $field_id, $field['title'], $field['callback'], $page, $section_id, $field['args'] );
				}

				// Register the setting
				if ( ! empty( $field['sanitize_callback'] ) ) {
					register_setting( $page, $field_id, $field['sanitize_callback'] );
				}
			}
		}
	}

	/**
	 * Enqueue or output admin scripts
	 *
	 * @since 0.1.0
	 */
	public function enqueue_scripts() {

		// Define local variable
		$screen = get_current_screen();
		$styles = array();

		// Posts administration
		if ( 'edit' === $screen->base ) {
			$styles[] = ".fixed .column-vgsr { width: 5%; text-align: center; }";

		// Single edit of vgsr post type
		} elseif ( 'post' === $screen->base && is_vgsr_post_type( $screen->post_type ) ) {

			// Exclusivity meta
			$styles[] = ".misc-pub-vgsr input[type=\"checkbox\"] { display: none; }";
			$styles[] = ".misc-pub-vgsr label:before { content: '\\f154'; position: relative; top: 0; left: -1px; padding: 0 2px 0 0; color: #ddd; -webkit-transition: all .1s ease-in-out; transition: all .1s ease-in-out; }";

			$styles[] = ".misc-pub-vgsr input[type=\"checkbox\"]:not(:checked) + label span.post-is-open, .misc-pub-vgsr input[type=\"checkbox\"]:checked + label span.post-is-vgsr { display: inline; }";
			$styles[] = ".misc-pub-vgsr input[type=\"checkbox\"]:checked + label span.post-is-open, .misc-pub-vgsr input[type=\"checkbox\"]:not(:checked) + label span.post-is-vgsr { display: none; }";

			$styles[] = ".misc-pub-vgsr input[type=\"checkbox\"] + label span span { font-weight: 600; }";
			$styles[] = ".misc-pub-vgsr input[type=\"checkbox\"]:checked + label:before { content: '\\f155'; color: #888; }";

		// Users administration
		} elseif ( in_array( $screen->base, array( 'users', 'users-network' ) ) ) {
			$styles[] = ".fixed .column-vgsr-jaargroep { width: 100px; }";
			$styles[] = ".fixed .column-vgsr-jaargroep .row-actions { display: block; color: #aaa; }";
		}

		// Add styles to the screen
		if ( ! empty( $styles ) ) {
			wp_add_inline_style( 'common', implode( "\n", $styles ) );
		}
	}

	/**
	 * Modify the plugin action links
	 *
	 * @since 0.0.1
	 *
	 * @param array $links Plugin action links
	 * @param string $basename Plugin basename
	 * @return array Plugin action links
	 */
	public function plugin_action_links( $links, $basename ) {

		// Add plugin action links for this plugin
		if ( $basename === vgsr()->basename && current_user_can( $this->minimum_capability ) ) {

			// Settings page
			$links['settings'] = '<a href="' . esc_url( vgsr_admin_url() ) . '">' . esc_html_x( 'Settings', 'Plugin action link', 'vgsr' ) . '</a>';
		}

		return $links;
	}

	/** Posts *****************************************************************/

	/**
	 * Setup post administration column actions
	 *
	 * @since 0.1.0
	 */
	public function setup_vgsr_post_columns() {

		// Walk the post types
		foreach ( vgsr_post_types() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns",       array( $this, 'get_post_columns'     )        );
			add_filter( "manage_{$post_type}_posts_custom_column", array( $this, 'post_columns_content' ), 10, 2 );
		}
	}

	/**
	 * Filter the post administration columns
	 *
	 * @since 0.0.6
	 *
	 * @param array $columns Columns
	 * @return array
	 */
	public function get_post_columns( $columns ) {

		// Only when the user is vgsr
		if ( is_user_vgsr() ) {
			$screen = get_current_screen();

			// We need this column to enable quick edit
			$columns['vgsr'] = esc_html_x( 'VGSR', 'exclusivity title', 'vgsr' );

			// Hide dummy column by default
			add_filter( "get_user_option_manage{$screen->id}columnshidden", array( $this, 'get_post_columns_hidden' ) );
		}

		return $columns;
	}

	/**
	 * Filter the hidden post administration columns
	 *
	 * @since 0.0.6
	 *
	 * @param array $columns Hidden columns
	 * @return array
	 */
	public function get_post_columns_hidden( $columns ) {

		// Hide vgsr dummy column by default
		$columns[] = 'vgsr';

		return $columns;
	}

	/**
	 * Output content for the post administration columns
	 *
	 * @since 0.0.6
	 *
	 * @param string $column Column name
	 * @param int $post_id Post ID
	 */
	public function post_columns_content( $column, $post_id ) {

		// Display whether the post is exclusive (for Quick Edit)
		if ( 'vgsr' === $column && vgsr_is_post_vgsr( $post_id ) ) {
			echo '<div class="post-is-vgsr"><i class="dashicons-before dashicons-star-filled"></i></div>';
		}
	}

	/**
	 * Manipulate post states
	 *
	 * @since 0.0.6
	 *
	 * @param array $states Post states
	 * @param WP_Post $post Post object
	 * @return array $states
	 */
	public function display_post_states( $states, $post ) {

		// Post is exclusive: big notation.
		if ( vgsr_is_post_vgsr( $post->ID ) ) {
			$states['vgsr'] = _x( 'VGSR', 'exclusivity label', 'vgsr' );

		// Some parent is exclusive: small notation.
		} elseif ( vgsr_is_post_vgsr( $post->ID, true ) ) {
			$states['vgsr'] = _x( 'vgsr', 'exclusivity label', 'vgsr' );
		}

		return $states;
	}

	/** Users *****************************************************************/

	/**
	 * Filter the (network) users administration columns
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns List table columns
	 * @return array List table columns
	 */
	public function get_users_columns( $columns ) {

		// Insert Jaargroep column after 'name' column
		$jaargroep = array( 'vgsr-jaargroep' => esc_html_x( 'Jaargroep', 'Users list table column header', 'vgsr' ) );
		$pos       = array_search( 'name', array_keys( $columns ) ) + 1;
		$columns   = array_slice( $columns, 0, $pos, true ) + $jaargroep + array_slice( $columns, $pos, count( $columns ) - 1, true );

		return $columns;
	}

	/**
	 * Filter the sortable (network) users administration columns
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns List table columns
	 * @return array List table columns
	 */
	public function sortable_users_columns( $columns ) {

		// Make Jaargroep column sortable by anciënniteit
		$columns['vgsr-jaargroep'] = array( 'ancienniteit', false );

		return $columns;
	}

	/**
	 * Return content for the (network) users administration columns
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Column content
	 * @param string $column Column name
	 * @param int $user_id User ID
	 * @return string Column content
	 */
	public function users_columns_content( $content, $column, $user_id ) {

		switch ( $column ) {

			// Jaargroep
			case 'vgsr-jaargroep':

				// When the user is vgsr or ex-lid
				if ( is_user_vgsr( $user_id ) || is_user_exlid( $user_id ) ) {
					$jaargroep = vgsr_get_jaargroep( $user_id, array( 'default_to_ancienniteit' => false ) );

					// User has jaargroep
					if ( $jaargroep ) {
						$content .= sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'jaargroep' => $jaargroep ) ), $jaargroep );

					// User has no jaargroep
					} else {
						$content .= '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . _x( 'Unknown', 'name' ) . '</span>';
					}

					// Add anciënniteit data for super admins
					if ( is_super_admin() && $ancienniteit = vgsr_get_ancienniteit( $user_id ) ) {
						$content .= sprintf( '<small class="row-actions" title="%s">%s</small>', esc_html__( 'The ancienniteit value of this user.', 'vgsr' ), $ancienniteit );
					}
				}

				break;
		}

		return $content;
	}

	/** Exclusivity ***********************************************************/

	/**
	 * Display the Exclusivity post meta field
	 *
	 * @since 0.1.0
	 */
	public function vgsr_post_meta_field() {

		// Bail when this post cannot be exclusive
		if ( ( ! $post = get_post() ) || ! is_vgsr_post_type( $post->post_type ) )
			return;

		// Bail when user is not capable
		if ( ! is_user_vgsr() || ! current_user_can( get_post_type_object( $post->post_type )->cap->publish_posts ) )
			return; ?>

		<div class="misc-pub-section misc-pub-vgsr">
			<input type="checkbox" id="post_vgsr" name="vgsr_post_vgsr" value="1" <?php checked( vgsr_is_post_vgsr( $post->ID ) ); ?>/>
			<label for="post_vgsr" class="dashicons-before">
				<span class="post-is-open"><?php _e( 'Show to <span>all site visitors</span>', 'vgsr' ); ?></span>
				<span class="post-is-vgsr"><?php _e( 'Show only to <span>VGSR members</span>', 'vgsr' ); ?></span>
			</label>
			<?php wp_nonce_field( 'vgsr_post_vgsr_save', 'vgsr_post_vgsr_nonce' ); ?>
		</div>

		<?php
	}

	/**
	 * Output Exclusivity quick edit post field
	 *
	 * @since 0.1.0
	 *
	 * @param string $column_name Column name
	 * @param string $post_type Post type
	 */
	public function vgsr_post_quick_edit( $column_name, $post_type ) {

		// Bail when this is not our column or post cannot be exclusive
		if ( 'vgsr' !== $column_name || ! is_vgsr_post_type( $post_type ) || ! is_user_vgsr() )
			return;

		?>

		<fieldset class="inline-edit-col-right" style="display: none;"><div class="inline-edit-col">
			<div class="inline-edit-group">
				<div id="inline-edit-vgsr" style="display: inline-block; margin-left: .5em;">
					<em class="alignleft inline-edit-or"><?php _e( '&ndash;OR&ndash;' ); ?></em>
					<label class="alignleft inline-edit-vgsr">
						<?php wp_nonce_field( 'vgsr_post_vgsr_save', 'vgsr_post_vgsr_nonce' ); ?>
						<input type="checkbox" name="vgsr_post_vgsr" value="1" />
						<span class="checkbox-title"><?php _ex( 'VGSR', 'exclusivity label', 'vgsr' ); ?></span>
					</label>
				</div>
			</div>
		</div></fieldset>

		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {

				// When selecting new post to edit inline
				$( '#the-list' ).on( 'click', 'a.editinline', function() {
					var id     = inlineEditPost.getId( this ),
					    _edit  = $( '#inline-edit' ),
					    _field = _edit.find( '#inline-edit-vgsr' ),
					    _input = _field.find( 'input[name="vgsr_post_vgsr"]' ).attr( 'checked', false );

					// Check an exlusive post
					if ( $( '#post-' + id + ' td.column-vgsr .post-is-vgsr' ).length ) {
						_input.attr( 'checked', 'checked' );
					}

					// Move field, insert after Private setting
					_field.insertAfter( _edit.find( '.inline-edit-private' ) );
				} );
			} );
		</script>

		<?php
	}

	/**
	 * Save the Exclusivity post meta field
	 *
	 * Handles saving from metabox as well as from quick edit.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Post ID
	 */
	public function vgsr_post_save_meta( $post_id ) {

		// Bail when doing an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Bail when not a post request
		if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) )
			return;

		// Nonce check
		if ( ! isset( $_POST['vgsr_post_vgsr_nonce'] ) || ! wp_verify_nonce( $_POST['vgsr_post_vgsr_nonce'], 'vgsr_post_vgsr_save' ) )
			return;

		$post_type_object = get_post_type_object( get_post_type( $post_id ) );

		// Current user cannot edit this post
		if ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) )
			return;

		// Field selected
		if ( isset( $_POST['vgsr_post_vgsr'] ) && ! empty( $_POST['vgsr_post_vgsr'] ) ) {
			update_post_meta( $post_id, '_vgsr_post_vgsr_only', 1 );

		// Not selected
		} else {
			delete_post_meta( $post_id, '_vgsr_post_vgsr_only' );
		}

		// Update hierarchy
		_vgsr_post_update_hierarchy( $post_id );
	}
}

endif; // class_exists

/**
 * Load the VGSR Admin
 *
 * @since 0.0.1
 */
function vgsr_admin() {
	vgsr()->admin = new VGSR_Admin;
}
