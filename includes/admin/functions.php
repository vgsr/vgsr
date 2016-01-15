<?php

/**
 * VGSR Admin Functions
 *
 * @package VGSR
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Posts *****************************************************************/

/**
 * Display the Exclusivity post meta field
 *
 * @since 0.0.6
 *
 * @uses is_vgsr_post_type()
 * @uses get_post_type_object()
 * @uses vgsr_is_post_vgsr()
 */
function vgsr_is_post_vgsr_meta() {

	// Bail when the post is invalid
	if ( ! $post = get_post() )
		return;

	// Bail when this post cannot be exclusive
	if ( ! is_vgsr_post_type( $post->post_type ) )
		return;

	// Bail when user is not capable
	if ( ! current_user_can( get_post_type_object( $post->post_type )->cap->publish_posts ) )
		return; ?>

	<div class="misc-pub-section misc-pub-vgsr dashicons-before dashicons-flag">
		<style>
			.misc-pub-vgsr:before {
				position: relative;
				top: 0;
				left: -1px;
				padding: 0 2px 0 0;
				color: #888;
			}
		</style>

		<?php wp_nonce_field( 'vgsr_post_vgsr_save', 'vgsr_post_vgsr_nonce' ); ?>
		<label for="post_vgsr"><?php _ex( 'VGSR', 'exclusivity label', 'vgsr' ); ?>:</label>
		<input type="checkbox" id="post_vgsr" name="vgsr_post_vgsr" value="1" <?php checked( vgsr_is_post_vgsr( $post->ID ) ); ?>/>
	</div>

	<?php
}

/**
 * Output quick edit Exclusivity post fields
 *
 * @since 0.0.6
 *
 * @uses is_vgsr_post_type()
 * @uses wp_nonce_field()
 */
function vgsr_post_vgsr_quick_edit( $column_name, $post_type ) {

	// Bail when this is not our column or post cannot be exclusive
	if ( 'vgsr' !== $column_name || ! is_vgsr_post_type( $post_type ) )
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
				if ( $( '#post-' + id + ' td.column-vgsr i.dashicons-yes' ).length ) {
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
 * @since 0.0.6
 *
 * @uses get_post_type_object()
 * @uses update_post_meta()
 * @uses delete_post_meta()
 */
function vgsr_is_post_vgsr_meta_save( $post_id ) {

	// Bail when doing an autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return $post_id;

	// Bail when not a post request
	if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return $post_id;

	// Check action exists
	if ( empty( $_POST['action'] ) )
		return $post_id;

	// Nonce check
	if ( ! isset( $_POST['vgsr_post_vgsr_nonce'] ) || ! wp_verify_nonce( $_POST['vgsr_post_vgsr_nonce'], 'vgsr_post_vgsr_save' ) )
		return $post_id;

	$post_type_object = get_post_type_object( get_post_type( $post_id ) );

	// Current user cannot publish posts
	if ( ! current_user_can( $post_type_object->cap->publish_posts ) )
		return $post_id;

	// Current user cannot edit this post
	if ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) )
		return $post_id;

	// Field selected
	if ( isset( $_POST['vgsr_post_vgsr'] ) && ! empty( $_POST['vgsr_post_vgsr'] ) ) {
		update_post_meta( $post_id, '_vgsr_post_vgsr_only', 1 );

	// Not selected
	} else {
		delete_post_meta( $post_id, '_vgsr_post_vgsr_only' );
	}

	// Update hierarchy
	_vgsr_post_update_hierarchy( $post_id );

	return $post_id;
}
