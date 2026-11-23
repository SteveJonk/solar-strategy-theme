/**
 * Address Lookup.
 *
 * On Contact Form 7 submission the server has already resolved the address and attached it to the
 * AJAX response. This script:
 *
 *   1. writes the parameters into the browser URL (no reload),
 *   2. fills any field on the page whose name matches a parameter name,
 *   3. fires `tal:address` so you can open the Popup Maker pop-up yourself.
 *
 *   document.addEventListener( 'tal:address', function ( e ) {
 *       PUM.open( 123 );  // e.detail.params, e.detail.address, e.detail.url
 *   } );
 */
( function () {
	'use strict';

	var cfg    = window.TALConfig || {};
	var names  = cfg.params || [];
	var latest = null;

	/* ---------------------------------------------------------------- utils */

	function setValue( input, value ) {
		if ( ! input || ! value ) {
			return;
		}
		input.value = value;
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function emit( name, detail ) {
		document.dispatchEvent( new CustomEvent( name, { detail: detail, bubbles: true } ) );
	}

	/* ------------------------------------------------------------ the work */

	function updateUrl( params ) {
		var url = new URL( window.location.href );

		Object.keys( params ).forEach( function ( name ) {
			url.searchParams.set( name, params[ name ] );
		} );

		if ( cfg.updateUrl !== false && window.history && history.replaceState ) {
			history.replaceState( history.state, '', url.toString() );
		}

		return url.toString();
	}

	/**
	 * Fill fields by name. CF7's `default:get` only runs on page load, so a pop-up that is already
	 * in the DOM needs this.
	 */
	function fillFields( params, root ) {
		if ( cfg.prefill === false ) {
			return;
		}

		root = root || document;

		Object.keys( params ).forEach( function ( name ) {
			var selector = '[name="' + name.replace( /"/g, '\\"' ) + '"]';

			root.querySelectorAll( selector ).forEach( function ( input ) {
				// Never overwrite something the visitor is typing in.
				if ( input === document.activeElement ) {
					return;
				}
				setValue( input, params[ name ] );
			} );
		} );
	}

	function apply( params, address ) {
		latest = { params: params, address: address || null };

		var url = updateUrl( params );

		fillFields( params );

		if ( window.console ) {
			console.debug( '[Address Lookup] parameters applied', params, 'URL update: ' + ( cfg.updateUrl !== false ) );
		}

		emit( 'tal:address', {
			params: params,
			address: address || null,
			url: url
		} );
	}

	/* ----------------------------------------------------------------- CF7 */

	function handleSubmit( event, jqDetail ) {
		var detail   = ( event && event.detail ) || jqDetail;
		var response = detail && detail.apiResponse;

		if ( ! response ) {
			return;
		}

		if ( ! response.tal_params ) {
			if ( window.console ) {
				console.warn( '[Address Lookup] CF7 responded without address parameters. Status: ' + response.status, response );
			}
			return;
		}

		// wpcf7submit and wpcf7mailsent both carry the same response object.
		if ( response.__talHandled ) {
			return;
		}
		response.__talHandled = true;

		if ( response.tal_error && window.console ) {
			console.warn( '[Address Lookup] ' + response.tal_error );
		}

		apply( response.tal_params, response.tal_address || null );
	}

	document.addEventListener( 'wpcf7submit', handleSubmit );
	document.addEventListener( 'wpcf7mailsent', handleSubmit );

	// Older Contact Form 7 versions fire these through jQuery, which native listeners cannot hear.
	if ( window.jQuery ) {
		window.jQuery( document ).on( 'wpcf7submit wpcf7mailsent', handleSubmit );
	}

	/* --------------------------------------------------- prefill on load */

	function prefillFromUrl( root ) {
		if ( ! names.length ) {
			return;
		}

		var search = new URLSearchParams( window.location.search );
		var params = {};

		names.forEach( function ( name ) {
			var value = search.get( name );
			if ( value ) {
				params[ name ] = value;
			}
		} );

		if ( Object.keys( params ).length ) {
			fillFields( params, root );
		}
	}

	function init() {
		prefillFromUrl( document );

		// Pop-up Maker injects markup late, so fill again when new nodes appear.
		if ( window.MutationObserver ) {
			new MutationObserver( function ( mutations ) {
				mutations.forEach( function ( mutation ) {
					mutation.addedNodes.forEach( function ( node ) {
						if ( node.nodeType !== 1 ) {
							return;
						}
						if ( latest ) {
							fillFields( latest.params, node );
						} else {
							prefillFromUrl( node );
						}
					} );
				} );
			} ).observe( document.body, { childList: true, subtree: true } );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	/* ------------------------------------------------------------- public */

	window.TALAddressLookup = {
		/** Manual lookup, e.g. TALAddressLookup.lookup( '1012AB', '12' ).then( ... ) */
		lookup: function ( postcode, housenumber ) {
			var url = cfg.endpoint +
				( cfg.endpoint.indexOf( '?' ) === -1 ? '?' : '&' ) +
				'postcode=' + encodeURIComponent( postcode ) +
				'&housenumber=' + encodeURIComponent( housenumber );

			return fetch( url, {
				headers: { 'X-WP-Nonce': cfg.nonce || '' },
				credentials: 'same-origin'
			} ).then( function ( response ) {
				return response.json().then( function ( data ) {
					if ( ! response.ok ) {
						throw new Error( ( data && data.message ) || 'Lookup failed' );
					}
					return data;
				} );
			} );
		},

		/** Apply parameters by hand. */
		apply: apply,

		/** Last resolved result, or null. */
		latest: function () {
			return latest;
		}
	};
}() );
