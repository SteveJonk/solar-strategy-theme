# Address Lookup (Contact Form 7 → URL parameters → Popup Maker)

## Install

1. Copy this folder into your theme, e.g. `wp-content/themes/your-theme/inc/address-lookup/`.
2. In `functions.php`:

```php
require_once get_theme_file_path( 'inc/address-lookup/init.php' );
```

3. **Settings → Address Lookup**: paste the Google API key, check the two short-form field names.
   The parameter names default to `postcode`, `house-number` and `address`.

Key can also live outside the database:

```php
// wp-config.php
define( 'TAL_GOOGLE_API_KEY', 'AIza...' );
```

In Google Cloud: enable the **Address Validation API** and billing, and restrict the key by
**IP address** — the call is server side, so a referrer restriction will not work.

## How it flows

1. Visitor submits the short form (`[submit]`, untouched — no click handler involved).
2. `wpcf7_feedback_response` fires server side. The address is looked up and added to the AJAX
   response as `tal_params` and `tal_address`.
3. The script writes those parameters into the browser URL with `history.replaceState`, fills any
   field on the page whose `name` matches a parameter, and fires `tal:address`.
4. You open the pop-up.

```js
document.addEventListener( 'tal:address', function ( e ) {
	// e.detail.params  → { "postcode": "1012 AB", "house-number": "12", "address": "Damrak" }
	// e.detail.address → full parsed address, incl. city, province, formatted
	// e.detail.url     → the current URL including the parameters
	PUM.open( 123 ); // your pop-up ID
} );
```

Because CF7 finishes its own AJAX cycle normally, `on_sent_ok` style redirects, the CF7 `wpcf7mailsent`
event and Popup Maker's own CF7 integration all keep working alongside this.

## Pop-up form fields

Name the fields in the pop-up form after the parameters:

```
[text* postcode]
[text* house-number]
[text* address]
```

The script fills them as soon as the response arrives, and again if Popup Maker injects the markup
later. If the pop-up lives on its own page or in an iframe, add `default:get` to each tag and CF7
will read the parameters itself.

## Settings reference

| Setting | Default | Notes |
| --- | --- | --- |
| Postcode / house number field | `your-postcode`, `your-housenumber` | Names in the short form. If a submitted form lacks both, the lookup is skipped, so other forms on the site are unaffected. |
| Postcode / house number / address parameter | `postcode`, `house-number`, `address` | Empty name = parameter omitted. |
| City parameter | *(empty)* | Set a name to add it. |
| Address parameter contains | Street name only | Or street + number, or the full formatted address. |
| Update the browser URL | on | Turn off to get only the `tal:address` event. |
| Cache results | 30 days | Every uncached submission is a billable Google request. |

Two PHP filters if you want to reshape things: `tal_url_params` (the parameter array) and
`tal_address` (the parsed address).

## Why the old click listener did not fire

`[submit]` only accepts `class:` and `id:` options, so the `data-tal-open` attribute never landed on
the button — the delegated listener had nothing to match. That whole mechanism is gone now; the
integration hangs off CF7's own submit event instead.

## Endpoint (still available)

`GET /wp-json/tal/v1/lookup?postcode=1012AB&housenumber=12` — used by the test box on the settings
screen, and by `TALAddressLookup.lookup()` if you ever want a lookup outside a submission. Rate
limited to 30 requests per minute per IP.

## Note

For NL specifically, Postcode.nl or PDOK is cheaper and more precise than Google for postcode +
house number. Only `TAL_Lookup::get_address()` and `TAL_Lookup::parse()` would need swapping.
