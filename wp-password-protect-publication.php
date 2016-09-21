<?php
/**
 * Plugin Name:    WP password protect publication
 * Plugin URI:     http://bearne.ca
 * Description:    Protect your posts from publication / updates with password
 * Version:        1.0.1
 * Author:         pbearne
 * Author URI:     http://bearne.ca
 * License:        GNU General Public License
 * License URI:    http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp_password_protect_publication
 * Domain Path: /languages
 * @package Wp_password_protect_publication
 */

namespace wp_password_protect_publication;

class Password_Protect_Publication {

	static $meta_key = __NAMESPACE__;
	static $supported_post_types = array( 'post', 'page' );


	/**
	 * Password_Protect_Publication constructor.
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'post_submitbox_misc_actions', array( __class__, 'form' ) );

			add_filter( 'wp_insert_post_empty_content', array( __class__, 'save_password' ), 1, 1 );
			add_filter( 'wp_insert_post_empty_content', array( __class__, 'check_password' ), 2, 2 );
		}

	}

	/**
	 *
	 *
	 * @static
	 */
	public static function form() {
		$screen = get_current_screen();

		if ( ! in_array( $screen->id, apply_filters( 'wp-password-protect-publication-supported_post_types', self::$supported_post_types ), true ) ) {
			return false;
		}

		global $post;

		$place_holder        = __( 'Password to protect publication', 'wp-password-protect-publication' );
		$unlock_place_holder = __( 'Password to unlock publication', 'wp-password-protect-publication' );
		switch ( $post->post_status ) {
			case 'publish':
				$place_holder        = __( 'Password to protect update', 'wp-password-protect-publication' );
				$unlock_place_holder = __( 'Password to unlock update', 'wp-password-protect-publication' );
				break;

		}

		$meta = get_post_meta( absint( $post->ID ), self::$meta_key );

		echo '<div style="padding-left: 2%">';
		if ( empty( $meta ) ) {
			printf( '<input name="%s" placeholder="%s" style="width: %s" />',
				esc_attr( 'set_' . self::$meta_key ),
				esc_html( $place_holder ),
				esc_attr( '94%' )
			);
			printf( '<input type="submit" name="save" id="%s" style="width: %s" class="button button-primary button-large" value="%s">',
				esc_attr( self::$meta_key ),
				esc_attr( '94%' ),
				esc_html__( 'Set Password', 'wp-password-protect-publication' )
			);
		} else {
			printf( '<input name="%s" placeholder="%s" type="password" style="width: %s"/>',
				esc_attr( 'check_' . self::$meta_key ),
				esc_html( $unlock_place_holder ),
				esc_attr( '94%' )
			);
			printf( '<input type="submit" name="save" id="%s" style="width: %s" class="button button-primary button-large" value="%s">',
				esc_attr( self::$meta_key ),
				esc_attr( '94%' ),
				esc_html__( 'Unlock', 'wp-password-protect-publication' )
			);
			echo '<script type="application/javascript">jQuery(document).ready(function() {jQuery("#publish").prop("disabled", true); });	</script>';
		}
		echo '</div>';
	}

	/**
	 *
	 *
	 * @static
	 *
	 * @param $maybe_empty
	 * @param $postarr
	 *
	 * @return mixed
	 * @internal param $data
	 */
	public static function check_password( $maybe_empty, $postarr ) {
		$input_id = 'check_' . self::$meta_key;
		$password = null;

		if ( isset( $postarr['ID'] ) ) {
			$password = get_post_meta( absint( $postarr['ID'] ), self::$meta_key, true );
		}

		// no password set return
		if ( null === $password || '' === $password ) {

			return $maybe_empty;
		}

		// do they match then delete and return
		if ( isset( $_POST[ $input_id ] ) && '' !== trim( $_POST[ $input_id ] ) ) {
			if ( trim( $_POST[ $input_id ] ) === $password ) {
				delete_post_meta( absint( $postarr['ID'] ), self::$meta_key, $password );

				return $maybe_empty;
			}
		}

		// if these types of saves them redirect
		$publish_strings = array( __( 'Publish' ), __( 'Submit for Review' ), __( 'Schedule' ), __( 'Update' ) );

		$save_or_publish = ( isset( $postarr['save'] ) ) ? $postarr['save'] : '';
		$save_or_publish = ( '' === $save_or_publish && isset( $postarr['publish'] ) ) ? $postarr['publish'] : $save_or_publish;
		if ( in_array( $save_or_publish, $publish_strings, true ) ) {
			wp_safe_redirect( $postarr['_wp_http_referer'] );
			die();
		}

		return $maybe_empty;
	}

	/**
	 *
	 *
	 * @static
	 *
	 * @param $maybe_empty
	 *
	 * @return
	 * @internal param $post_id
	 */
	public static function save_password( $maybe_empty ) {
		$input_id = 'set_' . self::$meta_key;
		if ( isset( $_POST[ $input_id ] ) && '' !== trim( $_POST[ $input_id ] ) ) {
			update_post_meta( absint( $_POST['post_ID'] ), self::$meta_key, sanitize_text_field( trim( $_POST[ $input_id ] ) ) );
		}

		return $maybe_empty;
	}
}

new Password_Protect_Publication();
