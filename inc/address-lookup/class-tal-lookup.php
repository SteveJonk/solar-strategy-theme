<?php
/**
 * Address lookup service + REST endpoint.
 *
 * Two providers:
 *   - pdok   : the Dutch Locatieserver (BAG data). Free, no key, best for NL.
 *   - google : Google Address Validation API. Needs a key and billing.
 *
 * @package Theme\AddressLookup
 */

defined( 'ABSPATH' ) || exit;

class TAL_Lookup {

	const GOOGLE_ENDPOINT = 'https://addressvalidation.googleapis.com/v1:validateAddress';
	const PDOK_ENDPOINT   = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free';
	const RATE_LIMIT      = 30; // Requests per minute per IP, REST endpoint only.

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/* ------------------------------------------------------------- service */

	/**
	 * Resolve a postcode + house number into address parts.
	 *
	 * @return array|WP_Error
	 */
	public static function get_address( $postcode, $housenumber, $refresh = false ) {
		$options     = TAL_Settings::get();
		$provider    = 'pdok' === $options['provider'] ? 'pdok' : 'google';
		$postcode    = self::clean_postcode( $postcode );
		$housenumber = self::clean_housenumber( $housenumber );

		if ( '' === $postcode || '' === $housenumber ) {
			return new WP_Error( 'tal_bad_input', __( 'Enter both a postcode and a house number.', 'theme' ), array( 'status' => 400 ) );
		}

		// The provider is part of the key, so switching providers bypasses stale results.
		$cache_key = 'tal_' . md5( $provider . '|' . $options['region'] . '|' . $postcode . '|' . $housenumber );

		if ( ! $refresh && $options['cache_days'] > 0 ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				$cached['cached'] = true;
				return $cached;
			}
		}

		$address = 'pdok' === $provider
			? self::lookup_pdok( $postcode, $housenumber )
			: self::lookup_google( $postcode, $housenumber, $options );

		if ( is_wp_error( $address ) ) {
			return $address;
		}

		if ( $options['cache_days'] > 0 ) {
			set_transient( $cache_key, $address, $options['cache_days'] * DAY_IN_SECONDS );
		}

		/**
		 * Filter the resolved address before it is used.
		 */
		return apply_filters( 'tal_address', $address, $postcode, $housenumber );
	}

	/* ---------------------------------------------------------------- PDOK */

	protected static function lookup_pdok( $postcode, $housenumber ) {
		// Split "18A" into number and suffix; the Locatieserver matches better that way.
		preg_match( '/^(\d+)\s*(.*)$/', $housenumber, $parts );
		$number = isset( $parts[1] ) ? $parts[1] : $housenumber;

		$url = add_query_arg(
			array(
				'q'    => rawurlencode( $postcode . ' ' . $housenumber ),
				'fq'   => rawurlencode( 'type:adres' ),
				'rows' => 10,
				'fl'   => rawurlencode( 'weergavenaam,straatnaam,huisnummer,huisletter,huis_nlt,postcode,woonplaatsnaam,provincienaam' ),
			),
			self::PDOK_ENDPOINT
		);

		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'tal_http_error', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$docs = isset( $body['response']['docs'] ) ? $body['response']['docs'] : array();

		if ( empty( $docs ) ) {
			return new WP_Error( 'tal_not_found', __( 'No address found for this postcode and house number.', 'theme' ), array( 'status' => 404 ) );
		}

		// Prefer an exact house number match; the free endpoint sorts by relevance, not exactness.
		$doc = $docs[0];

		foreach ( $docs as $candidate ) {
			$candidate_number = isset( $candidate['huisnummer'] ) ? (string) $candidate['huisnummer'] : '';

			if ( $candidate_number === (string) $number ) {
				$doc = $candidate;
				break;
			}
		}

		$found = isset( $doc['postcode'] ) ? $doc['postcode'] : $postcode;

		return array(
			'street'       => isset( $doc['straatnaam'] ) ? $doc['straatnaam'] : '',
			'house_number' => isset( $doc['huis_nlt'] ) ? $doc['huis_nlt'] : $housenumber,
			'postcode'     => self::format_postcode_nl( $found ),
			'city'         => isset( $doc['woonplaatsnaam'] ) ? $doc['woonplaatsnaam'] : '',
			'province'     => isset( $doc['provincienaam'] ) ? $doc['provincienaam'] : '',
			'country'      => 'NL',
			'formatted'    => isset( $doc['weergavenaam'] ) ? $doc['weergavenaam'] : '',
			'complete'     => ! empty( $doc['straatnaam'] ),
			'cached'       => false,
		);
	}

	/* -------------------------------------------------------------- Google */

	protected static function lookup_google( $postcode, $housenumber, $options ) {
		if ( empty( $options['api_key'] ) ) {
			return new WP_Error( 'tal_no_key', __( 'No Google API key configured.', 'theme' ), array( 'status' => 500 ) );
		}

		/**
		 * Referer sent with the request. Google matches this against an HTTP referrer key
		 * restriction; a server side call has no referrer of its own, so we supply one.
		 */
		$referer = apply_filters( 'tal_referer', home_url( '/' ) );
		$headers = array( 'Content-Type' => 'application/json' );

		if ( $referer ) {
			$headers['Referer'] = $referer;
		}

		$response = wp_remote_post(
			add_query_arg( 'key', rawurlencode( $options['api_key'] ), self::GOOGLE_ENDPOINT ),
			array(
				'timeout' => 10,
				'headers' => $headers,
				'body'    => wp_json_encode(
					array(
						'address' => array(
							'regionCode'   => $options['region'],
							'postalCode'   => $postcode,
							'addressLines' => array( $housenumber ),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'tal_http_error', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Address lookup failed.', 'theme' );
			return new WP_Error( 'tal_api_error', $message, array( 'status' => 502 ) );
		}

		$address = self::parse_google( $body, $postcode, $housenumber );

		if ( '' === $address['street'] && '' === $address['city'] ) {
			return new WP_Error( 'tal_not_found', __( 'No address found for this postcode and house number.', 'theme' ), array( 'status' => 404 ) );
		}

		return $address;
	}

	/**
	 * Flatten the Google response into the fields the form needs.
	 */
	protected static function parse_google( $body, $postcode, $housenumber ) {
		$result     = isset( $body['result'] ) ? $body['result'] : array();
		$addr       = isset( $result['address'] ) ? $result['address'] : array();
		$postal     = isset( $result['address']['postalAddress'] ) ? $result['address']['postalAddress'] : array();
		$components = isset( $addr['addressComponents'] ) ? $addr['addressComponents'] : array();

		$component = function ( $type ) use ( $components ) {
			foreach ( $components as $item ) {
				if ( isset( $item['componentType'] ) && $type === $item['componentType'] ) {
					return isset( $item['componentName']['text'] ) ? $item['componentName']['text'] : '';
				}
			}
			return '';
		};

		return array(
			'street'       => $component( 'route' ),
			'house_number' => $component( 'street_number' ) ? $component( 'street_number' ) : $housenumber,
			'postcode'     => $component( 'postal_code' ) ? $component( 'postal_code' ) : $postcode,
			'city'         => $component( 'locality' ) ? $component( 'locality' ) : ( isset( $postal['locality'] ) ? $postal['locality'] : '' ),
			'province'     => $component( 'administrative_area_level_1' ),
			'country'      => isset( $postal['regionCode'] ) ? $postal['regionCode'] : '',
			'formatted'    => isset( $addr['formattedAddress'] ) ? $addr['formattedAddress'] : '',
			'complete'     => ! empty( $result['verdict']['addressComplete'] ),
			'cached'       => false,
		);
	}

	/* --------------------------------------------------------------- utils */

	public static function clean_postcode( $value ) {
		return substr( strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $value ) ), 0, 12 );
	}

	public static function clean_housenumber( $value ) {
		return substr( trim( preg_replace( '/[^A-Za-z0-9\-\/ ]/', '', (string) $value ) ), 0, 12 );
	}

	/**
	 * 2014JG becomes 2014 JG.
	 */
	protected static function format_postcode_nl( $value ) {
		$value = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $value ) );

		if ( preg_match( '/^(\d{4})([A-Z]{2})$/', $value, $m ) ) {
			return $m[1] . ' ' . $m[2];
		}

		return $value;
	}

	/* ---------------------------------------------------------------- REST */

	public static function register_routes() {
		register_rest_route(
			'tal/v1',
			'/lookup',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => '__return_true', // Public data, protected by rate limiting.
				'args'                => array(
					'postcode'    => array(
						'required' => true,
						'type'     => 'string',
					),
					'housenumber' => array(
						'required' => true,
						'type'     => 'string',
					),
					'refresh'     => array(
						'required' => false,
						'type'     => 'boolean',
					),
				),
			)
		);
	}

	public static function handle( WP_REST_Request $request ) {
		if ( ! self::check_rate_limit() ) {
			return new WP_Error( 'tal_rate_limited', __( 'Too many lookups. Please try again in a minute.', 'theme' ), array( 'status' => 429 ) );
		}

		$refresh = (bool) $request->get_param( 'refresh' ) && current_user_can( 'manage_options' );

		$address = self::get_address(
			$request->get_param( 'postcode' ),
			$request->get_param( 'housenumber' ),
			$refresh
		);

		if ( is_wp_error( $address ) ) {
			return $address;
		}

		return rest_ensure_response( $address );
	}

	protected static function check_rate_limit() {
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key   = 'tal_rl_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_LIMIT ) {
			return false;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		return true;
	}
}
