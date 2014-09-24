<?php

/**
 * VGSR Admin Functions
 *
 * @package VGSR
 * @subpackage Administration
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Posts *****************************************************************/

/**
 * Display the vgsr-only post meta field
 *
 * @since 0.0.6
 *
 * @uses is_vgsr_only_post_type()
 * @uses get_post_type_object()
 * @uses vgsr_is_post_vgsr_only()
 */
function vgsr_post_vgsr_only_meta() {
	global $post;

	// Bail if this post cannot be marked vgsr-only
	if ( ! is_vgsr_only_post_type( $post->post_type ) )
		return;

	// Bail if user is not capable
	if ( ! current_user_can( get_post_type_object( $post->post_type )->cap->publish_posts ) )
		return; ?>

		<div class="misc-pub-section misc-pub-vgsr-only dashicons-before dashicons-flag">
			<style>
				.misc-pub-vgsr-only:before {
					position: relative;
					top: 0;
					left: -1px;
					padding: 0 2px 0 0;
					color: #888;
				}
			</style>

			<?php wp_nonce_field( 'vgsr_post_vgsr_only_save', 'vgsr_post_vgsr_only_nonce' ); ?>
			<label for="post_vgsr_only"><?php _e( 'VGSR only', 'vgsr' ); ?>:</label>
			<input type="checkbox" id="post_vgsr_only" name="vgsr_post_vgsr_only" value="1" <?php checked( vgsr_is_post_vgsr_only( $post->ID ) ); ?>/>
		</div>

	<?php
}

/**
 * Output quick edit vgsr-only post fields
 *
 * @since 0.0.6
 *
 * @uses is_vgsr_only_post_type()
 * @uses wp_nonce_field()
 */
function vgsr_post_vgsr_only_quick_edit( $column_name, $post_type ) {

	// Bail if this is not our column or post cannot be marked
	if ( 'vgsr-only' != $column_name || ! is_vgsr_only_post_type( $post_type ) )
		return; ?>

	<fieldset class="inline-edit-col-right"><div class="inline-edit-col">
		<div class="inline-edit-group">
			<label class="alignleft">
				<?php wp_nonce_field( 'vgsr_post_vgsr_only_save', 'vgsr_post_vgsr_only_nonce' ); ?>
				<input type="checkbox" name="vgsr_post_vgsr_only" value="1" />
				<span class="checkbox-title"><?php _e( 'VGSR only', 'vgsr' ); ?></span>
			</label>
		</div>
	</div></fieldset>

    <script type="text/javascript">
    jQuery(document).ready( function( $ ) {

    	// When selecting new post to edit inline
        $('#the-list').on('click', 'a.editinline', function() {
			var id    = inlineEditPost.getId( this ),
			    input = $('#inline-edit input[name="vgsr_post_vgsr_only"]').attr('checked', false);

			// Mark checked if vgsr-only. Value is in hidden input field in vgsr-only column
			if ( 1 == parseInt( $('#post-' + id + ' td.column-vgsr-only input').val() ) )
				input.attr('checked', 'checked');
        });
    });
    </script>

	<?php
}

/**
 * Save the vgsr-only post meta field
 *
 * Handles saving from metabox as well as from quick edit.
 *
 * @since 0.0.6
 *
 * @uses get_post_type_object()
 * @uses update_post_meta()
 * @uses delete_post_meta()
 */
function vgsr_post_vgsr_only_meta_save( $post_id ) {

	// Bail if doing an autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return $post_id;

	// Bail if not a post request
	if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) )
		return $post_id;

	// Check action exists
	if ( empty( $_POST['action'] ) )
		return $post_id;

	// Nonce check
	if ( ! isset( $_POST['vgsr_post_vgsr_only_nonce'] ) || ! wp_verify_nonce( $_POST['vgsr_post_vgsr_only_nonce'], 'vgsr_post_vgsr_only_save' ) )
		return $post_id;

	$post_type_object = get_post_type_object( get_post_type( $post_id ) );

	// Current user cannot publish posts
	if ( ! current_user_can( $post_type_object->cap->publish_posts ) )
		return $post_id;

	// Current user cannot edit this post
	if ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) )
		return $post_id;

	// Field selected
	if ( isset( $_POST['vgsr_post_vgsr_only'] ) && ! empty( $_POST['vgsr_post_vgsr_only'] ) ) {
		update_post_meta( $post_id, '_vgsr_post_vgsr_only', 1 );

	// Not selected
	} else {
		delete_post_meta( $post_id, '_vgsr_post_vgsr_only' );
	}

	// Update hierarchy
	_vgsr_only_update_post_hierarchy( $post_id );

	return $post_id;
}
