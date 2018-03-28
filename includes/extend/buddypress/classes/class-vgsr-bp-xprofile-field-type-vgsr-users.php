<?php

/**
 * VGSR BuddyPress XProfile Field Type VGSR Users
 *
 * @package VGSR
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_BP_XProfile_Field_Type_VGSR_Users' ) ) :
/**
 * The VGSR BP XProfile Field Type VGSR Users class
 *
 * @since 0.2.0
 */
class VGSR_BP_XProfile_Field_Type_VGSR_Users extends BP_XProfile_Field_Type_Selectbox {

	/**
	 * Constructor for the selectbox field type.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = _x( 'VGSR Users Select Box', 'xprofile field type', 'vgsr' );

		$this->supports_options = false;
		$this->do_settings_section = true;

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_VGSR_Users class.
		 *
		 * @since 0.2.0
		 *
		 * @param BP_XProfile_Field_Type_VGSR_Users $this Current instance of
		 *                                               the field type select box.
		 */
		do_action( 'vgsr_bp_xprofile_field_type_vgsr_users', $this );
	}

	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 0.2.0
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the current
	 *                                         field's child options.
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		$type = array_search( get_class( $this ), bp_xprofile_get_field_types() );
		if ( false === $type ) {
			return;
		}

		$class            = $current_field->type != $type ? 'display: none;' : '';
		$current_type_obj = bp_xprofile_create_field_type( $type );
		$user_type        = bp_xprofile_get_meta( $current_field->id, 'field', 'user_type' );
		?>

		<div id="<?php echo esc_attr( $type ); ?>" class="postbox bp-options-box" style="<?php echo esc_attr( $class ); ?> margin-top: 15px;">
			<h3><?php esc_html_e( 'Please enter options for this Field:', 'buddypress' ); ?></h3>
			<div class="inside" aria-live="polite" aria-atomic="true" aria-relevant="all">
				<p>
					<label for="user_type_<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'User Type:', 'vgsr' ); ?></label>
					<select name="user_type_<?php echo esc_attr( $type ); ?>" id="user_type_<?php echo esc_attr( $type ); ?>" >
						<option value=""                                                   ><?php esc_html_e( 'All',       'vgsr' ); ?></option>
						<option value="lid"     <?php selected( 'lid',     $user_type ); ?>><?php esc_html_e( 'Leden',     'vgsr' ); ?></option>
						<option value="oud-lid" <?php selected( 'oud-lid', $user_type ); ?>><?php esc_html_e( 'Oud-leden', 'vgsr' ); ?></option>
					</select>
				</p>

				<?php

				/**
				 * Fires at the end of the new field additional settings area.
				 *
				 * @since 2.3.0
				 *
				 * @param BP_XProfile_Field $current_field Current field being rendered.
				 */
				do_action( 'bp_xprofile_admin_new_field_additional_settings', $current_field ) ?>
			</div>
		</div>

		<?php
	}
}

endif; // class_exists
