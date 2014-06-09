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
 */
function vgsr_post_vgsr_only_meta() {
	global $post;

	// Bail if user is not capable
	if ( ! current_user_can( get_post_type_object( $post->post_type )->cap->publish_posts ) )
		return; ?>

		<div class="misc-pub-section misc-pub-vgsr-only">
			<?php wp_nonce_field( 'vgsr_post_vgsr_only_save', 'vgsr_post_vgsr_only_nonce' ); ?>
			<i class="dashicons-before dashicons-flag" style="color:#888;"></i>
			<label for="post_vgsr_only"><?php _e( 'VGSR only', 'vgsr' ); ?>:</label>
			<input type="checkbox" id="post_vgsr_only" name="_vgsr_post_vgsr_only" value="1" <?php checked( vgsr_is_post_vgsr_only( $post->ID ) ); ?>/>
		</div>

	<?php
}

/**
 * Save the vgsr-only post meta field
 *
 * @since 0.0.6
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
	if ( isset( $_POST['_vgsr_post_vgsr_only'] ) ) {
		update_post_meta( $post_id, '_vgsr_post_vgsr_only', 1 );

	// Not selected
	} else {
		delete_post_meta( $post_id, '_vgsr_post_vgsr_only' );
	}

	return $post_id;
}
