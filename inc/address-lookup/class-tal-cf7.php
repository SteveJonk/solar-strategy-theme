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

	public static function init() {
		add_filter( 'wpcf7_feedback_response', array( __CLASS__, 'add_address' ), 10, 2 );
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
			)
		);
	}

	/**
	 * Turn a resolved address into URL parameters.
	 */
	public static function build_params( array $address ) {
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

		$data    = $submission->get_posted_data();
		$options = TAL_Settings::get();

		$postcode    = self::field( $data, $options['field_postcode'] );
		$housenumber = self::field( $data, $options['field_housenumber'] );

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
				)
			);

			return $response;
		}

		$response['tal_address'] = $address;
		$response['tal_params']  = self::build_params( $address );

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
