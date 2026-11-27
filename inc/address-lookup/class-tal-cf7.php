<?php
/**
 * Contact Form 7 integration.
 *
 * On submit, looks the address up server side and adds it to the AJAX feedback response as
 * `tal_params` (ready made URL parameters) and `tal_address` (all parsed parts).
 *
 * @package Theme\AddressLookup
 */

defined( 'ABSPATH' ) || exit;

class TAL_CF7 {

	const REDIRECT_META = '_tal_redirect_page';

	public static function init() {
		add_filter( 'wpcf7_feedback_response', array( __CLASS__, 'add_address' ), 10, 2 );
		add_filter( 'wpcf7_editor_panels', array( __CLASS__, 'add_panel' ) );
		add_action( 'wpcf7_save_contact_form', array( __CLASS__, 'save_panel' ) );
	}

	/* ------------------------------------------------- per form redirect */

	public static function add_panel( $panels ) {
		$panels['tal-redirect'] = array(
			'title'    => __( 'Redirect', 'theme' ),
			'callback' => array( __CLASS__, 'render_panel' ),
		);

		return $panels;
	}

	/**
	 * @param WPCF7_ContactForm $form
	 */
	public static function render_panel( $form ) {
		echo '<h2>' . esc_html__( 'Redirect after submission', 'theme' ) . '</h2>';
		echo '<p>' . esc_html__( 'Send the visitor to this page after the mail is sent. The address parameters are added to its URL.', 'theme' ) . '</p>';

		wp_dropdown_pages(
			array(
				'name'              => 'tal_redirect_page',
				'selected'          => (int) get_post_meta( $form->id(), self::REDIRECT_META, true ),
				'show_option_none'  => __( '— No redirect —', 'theme' ),
				'option_none_value' => 0,
			)
		);
	}

	/**
	 * CF7 has already checked the nonce and capability before this runs.
	 *
	 * @param WPCF7_ContactForm $form
	 */
	public static function save_panel( $form ) {
		if ( ! isset( $_POST['tal_redirect_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$page = absint( $_POST['tal_redirect_page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( $page ) {
			update_post_meta( $form->id(), self::REDIRECT_META, $page );
		} else {
			delete_post_meta( $form->id(), self::REDIRECT_META );
		}
	}

	protected static function redirect_url( $submission ) {
		$form = $submission->get_contact_form();
		$page = $form ? (int) get_post_meta( $form->id(), self::REDIRECT_META, true ) : 0;
		$url  = $page && 'publish' === get_post_status( $page ) ? get_permalink( $page ) : '';

		return $url ? $url : '';
	}

	/**
	 * The parameter name map: internal key => URL parameter name.
	 * Empty names are dropped, so a parameter can be switched off in the settings.
	 */
	public static function param_names() {
		$options = TAL_Settings::get();

		return array_filter(
			array(
				'postcode'     => $options['param_postcode'],
				'house_number' => $options['param_housenumber'],
				'address'      => $options['param_address'],
				'city'         => $options['param_city'],
				'email'        => $options['param_email'],
			)
		);
	}

	/**
	 * Turn a resolved address into URL parameters.
	 */
	public static function build_params( array $address, $email = '' ) {
		$options = TAL_Settings::get();
		$names   = self::param_names();

		switch ( $options['address_format'] ) {
			case 'street_number':
				$street = trim( $address['street'] . ' ' . $address['house_number'] );
				break;
			case 'formatted':
				$street = $address['formatted'];
				break;
			default:
				$street = $address['street'];
		}

		$values = array(
			'postcode'     => $address['postcode'],
			'house_number' => $address['house_number'],
			'address'      => $street,
			'city'         => $address['city'],
			'email'        => $email,
		);

		$params = array();

		foreach ( $names as $key => $name ) {
			if ( isset( $values[ $key ] ) && '' !== $values[ $key ] ) {
				$params[ $name ] = $values[ $key ];
			}
		}

		/**
		 * Filter the URL parameters before they are sent to the browser.
		 *
		 * @param array $params  name => value.
		 * @param array $address The full parsed address.
		 */
		return apply_filters( 'tal_url_params', $params, $address );
	}

	/**
	 * @param array $response The CF7 AJAX response.
	 * @param array $result   The submission result.
	 */
	public static function add_address( $response, $result ) {
		$status = isset( $response['status'] ) ? $response['status'] : '';

		if ( ! in_array( $status, array( 'mail_sent', 'mail_failed' ), true ) ) {
			return $response;
		}

		if ( ! class_exists( 'WPCF7_Submission' ) ) {
			return $response;
		}

		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return $response;
		}

		// Only redirect when the mail actually went out.
		if ( 'mail_sent' === $status ) {
			$redirect = self::redirect_url( $submission );
			if ( $redirect ) {
				$response['tal_redirect'] = $redirect;
			}
		}

		$data    = $submission->get_posted_data();
		$options = TAL_Settings::get();

		$postcode    = self::field( $data, $options['field_postcode'] );
		$housenumber = self::field( $data, $options['field_housenumber'] );
		$email       = sanitize_email( self::field( $data, $options['field_email'] ) );

		// Not our form.
		if ( '' === $postcode || '' === $housenumber ) {
			return $response;
		}

		$address = TAL_Lookup::get_address( $postcode, $housenumber );

		if ( is_wp_error( $address ) ) {
			$response['tal_error'] = $address->get_error_message();

			// Still hand back what the visitor typed, so the pop-up is not empty.
			$response['tal_params'] = self::build_params(
				array(
					'street'       => '',
					'house_number' => $housenumber,
					'postcode'     => $postcode,
					'city'         => '',
					'formatted'    => '',
				),
				$email
			);

			return $response;
		}

		$response['tal_address'] = $address;
		$response['tal_params']  = self::build_params( $address, $email );

		return $response;
	}

	protected static function field( $data, $name ) {
		if ( ! $name || ! isset( $data[ $name ] ) ) {
			return '';
		}

		$value = $data[ $name ];

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		return trim( (string) $value );
	}
}
