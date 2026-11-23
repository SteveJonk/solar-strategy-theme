<?php
/**
 * Settings screen: Settings → Address Lookup.
 *
 * @package Theme\AddressLookup
 */

defined( 'ABSPATH' ) || exit;

class TAL_Settings {

	const OPTION = 'tal_options';
	const GROUP  = 'tal_options_group';
	const PAGE   = 'tal-settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	public static function defaults() {
		return array(
			'provider'          => 'pdok',
			'api_key'           => '',
			'region'            => 'NL',
			'cache_days'        => 30,

			// Field names on the short Contact Form 7 form.
			'field_postcode'    => 'your-postcode',
			'field_housenumber' => 'your-housenumber',

			// URL parameter names written after submission.
			'param_postcode'    => 'postcode',
			'param_housenumber' => 'house-number',
			'param_address'     => 'address',
			'param_city'        => '',

			// What goes into the address parameter.
			'address_format'    => 'street',

			'update_url'        => 1,
		);
	}

	/**
	 * Get one option, or all of them. A TAL_GOOGLE_API_KEY constant wins over the stored key.
	 */
	public static function get( $key = null ) {
		$opts = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );

		if ( defined( 'TAL_GOOGLE_API_KEY' ) && TAL_GOOGLE_API_KEY ) {
			$opts['api_key'] = TAL_GOOGLE_API_KEY;
		}

		if ( null === $key ) {
			return $opts;
		}

		return isset( $opts[ $key ] ) ? $opts[ $key ] : null;
	}

	public static function add_page() {
		add_options_page(
			__( 'Address Lookup', 'theme' ),
			__( 'Address Lookup', 'theme' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'tal_api',
			__( 'Lookup provider', 'theme' ),
			function () {
				echo '<p>' . esc_html__( 'PDOK is the Dutch government address register (BAG). It is free, needs no key, and resolves postcode + house number to a street. Google Address Validation is meant to verify an address you already have, so it often returns no street name for a postcode-only lookup — use it for addresses outside the Netherlands.', 'theme' ) . '</p>';
			},
			self::PAGE
		);

		add_settings_section(
			'tal_form',
			__( 'Short form fields', 'theme' ),
			function () {
				echo '<p>' . esc_html__( 'The field names from your Contact Form 7 tags, without the brackets. If a submitted form does not contain both fields, the lookup is skipped.', 'theme' ) . '</p>';
			},
			self::PAGE
		);

		add_settings_section(
			'tal_params',
			__( 'URL parameters', 'theme' ),
			function () {
				echo '<p>' . esc_html__( 'Written to the page URL after a successful submission, and also handed to your JavaScript in the tal:address event. Leave a name empty to omit that parameter.', 'theme' ) . '</p>';
			},
			self::PAGE
		);

		$fields = array(
			'provider'          => array(
				__( 'Provider', 'theme' ),
				'tal_api',
				'select',
				'',
				array(
					'pdok'   => __( 'PDOK Locatieserver (NL, free, no key)', 'theme' ),
					'google' => __( 'Google Address Validation API', 'theme' ),
				),
			),
			'api_key'           => array( __( 'Google API key', 'theme' ), 'tal_api', 'password', __( 'Only used by the Google provider. Restrict the key by IP address — the request is made server side.', 'theme' ) ),
			'region'            => array( __( 'Region code', 'theme' ), 'tal_api', 'text', __( 'Two letter country code, e.g. NL. Google only.', 'theme' ) ),
			'cache_days'        => array( __( 'Cache results (days)', 'theme' ), 'tal_api', 'number', __( '0 disables caching. Google allows caching validated addresses for a limited period, so 30 is the maximum here.', 'theme' ) ),

			'field_postcode'    => array( __( 'Postcode field', 'theme' ), 'tal_form', 'text' ),
			'field_housenumber' => array( __( 'House number field', 'theme' ), 'tal_form', 'text' ),

			'param_postcode'    => array( __( 'Postcode parameter', 'theme' ), 'tal_params', 'text' ),
			'param_housenumber' => array( __( 'House number parameter', 'theme' ), 'tal_params', 'text' ),
			'param_address'     => array( __( 'Address parameter', 'theme' ), 'tal_params', 'text' ),
			'param_city'        => array( __( 'City parameter', 'theme' ), 'tal_params', 'text', __( 'Optional. Empty means no city parameter is added.', 'theme' ) ),
			'address_format'    => array(
				__( 'Address parameter contains', 'theme' ),
				'tal_params',
				'select',
				'',
				array(
					'street'        => __( 'Street name only (Damrak)', 'theme' ),
					'street_number' => __( 'Street and house number (Damrak 12)', 'theme' ),
					'formatted'     => __( 'Full formatted address', 'theme' ),
				),
			),
			'update_url'        => array( __( 'Update the browser URL', 'theme' ), 'tal_params', 'checkbox', __( 'Rewrites the current URL with the parameters above (no page reload). Turn off if you only want the tal:address event.', 'theme' ) ),
		);

		foreach ( $fields as $key => $field ) {
			add_settings_field(
				'tal_' . $key,
				esc_html( $field[0] ),
				array( __CLASS__, 'render_field' ),
				self::PAGE,
				$field[1],
				array(
					'key'         => $key,
					'type'        => $field[2],
					'description' => isset( $field[3] ) ? $field[3] : '',
					'choices'     => isset( $field[4] ) ? $field[4] : array(),
				)
			);
		}
	}

	public static function render_field( $args ) {
		$opts   = self::get();
		$key    = $args['key'];
		$value  = isset( $opts[ $key ] ) ? $opts[ $key ] : '';
		$name   = self::OPTION . '[' . $key . ']';
		$locked = ( 'api_key' === $key && defined( 'TAL_GOOGLE_API_KEY' ) && TAL_GOOGLE_API_KEY );

		if ( $locked ) {
			$stored = (array) get_option( self::OPTION, array() );
			$value  = isset( $stored[ $key ] ) ? $stored[ $key ] : '';
		}

		if ( 'select' === $args['type'] ) {
			echo '<select id="tal_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( $args['choices'] as $choice => $label ) {
				printf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $choice ),
					selected( $value, $choice, false ),
					esc_html( $label )
				);
			}
			echo '</select>';
		} elseif ( 'checkbox' === $args['type'] ) {
			printf(
				'<label><input type="checkbox" id="tal_%1$s" name="%2$s" value="1"%3$s /> %4$s</label>',
				esc_attr( $key ),
				esc_attr( $name ),
				checked( $value, 1, false ),
				esc_html( $args['description'] )
			);
			return;
		} else {
			printf(
				'<input type="%1$s" id="tal_%2$s" name="%3$s" value="%4$s" class="regular-text" autocomplete="off"%5$s />',
				esc_attr( $args['type'] ),
				esc_attr( $key ),
				esc_attr( $name ),
				esc_attr( $value ),
				$locked ? ' disabled' : ''
			);
		}

		if ( $locked ) {
			echo '<p class="description">' . esc_html__( 'Set by the TAL_GOOGLE_API_KEY constant in wp-config.php; that value is used.', 'theme' ) . '</p>';
		} elseif ( $args['description'] ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	public static function sanitize( $input ) {
		$out      = array();
		$defaults = self::defaults();
		$input    = (array) $input;

		foreach ( $defaults as $key => $default ) {
			$value = isset( $input[ $key ] ) ? $input[ $key ] : '';

			switch ( $key ) {
				case 'provider':
					$value = in_array( $value, array( 'pdok', 'google' ), true ) ? $value : 'pdok';
					break;
				case 'cache_days':
					$value = min( 30, max( 0, absint( $value ) ) );
					break;
				case 'region':
					$value = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', $value ), 0, 2 ) );
					$value = $value ? $value : 'NL';
					break;
				case 'address_format':
					$value = in_array( $value, array( 'street', 'street_number', 'formatted' ), true ) ? $value : 'street';
					break;
				case 'update_url':
					$value = empty( $value ) ? 0 : 1;
					break;
				default:
					$value = sanitize_text_field( trim( $value ) );
			}

			$out[ $key ] = $value;
		}

		if ( defined( 'TAL_GOOGLE_API_KEY' ) && TAL_GOOGLE_API_KEY ) {
			$stored         = (array) get_option( self::OPTION, array() );
			$out['api_key'] = isset( $stored['api_key'] ) ? $stored['api_key'] : '';
		}

		return $out;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Address Lookup', 'theme' ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>

			<h2><?php esc_html_e( 'Test a lookup', 'theme' ); ?></h2>
			<p>
				<input type="text" id="tal-test-postcode" placeholder="<?php esc_attr_e( 'Postcode', 'theme' ); ?>" />
				<input type="text" id="tal-test-number" placeholder="<?php esc_attr_e( 'House number', 'theme' ); ?>" />
				<button type="button" class="button" id="tal-test-run"><?php esc_html_e( 'Look up address', 'theme' ); ?></button>
			</p>
			<pre id="tal-test-output" style="background:#fff;border:1px solid #c3c4c7;padding:12px;max-width:600px;overflow:auto;"></pre>

			<script>
			document.getElementById( 'tal-test-run' ).addEventListener( 'click', function () {
				var out = document.getElementById( 'tal-test-output' );
				var url = <?php echo wp_json_encode( rest_url( 'tal/v1/lookup' ) ); ?> +
					'?postcode=' + encodeURIComponent( document.getElementById( 'tal-test-postcode' ).value ) +
					'&housenumber=' + encodeURIComponent( document.getElementById( 'tal-test-number' ).value ) +
					'&refresh=1';

				out.textContent = <?php echo wp_json_encode( __( 'Looking up…', 'theme' ) ); ?>;

				fetch( url, { headers: { 'X-WP-Nonce': <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?> } } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( d ) { out.textContent = JSON.stringify( d, null, 2 ); } )
					.catch( function ( e ) { out.textContent = String( e ); } );
			} );
			</script>
		</div>
		<?php
	}
}
