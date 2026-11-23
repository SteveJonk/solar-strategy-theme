<?php

/**
 * Address Lookup — bootstrap.
 *
 * In functions.php:
 *     require_once get_theme_file_path( 'inc/address-lookup/init.php' );
 *
 * @package Theme\AddressLookup
 */

defined('ABSPATH') || exit;

define('TAL_VERSION', '1.1.1');
define('TAL_PATH', trailingslashit(__DIR__));

/**
 * Resolve the public URL of this folder, whether it lives in the parent or child theme.
 */
function tal_url($file = '')
{
	static $base = null;

	if (null === $base) {
		$dir  = wp_normalize_path(trailingslashit(__DIR__));
		$base = '';

		$roots = array(
			wp_normalize_path(trailingslashit(get_stylesheet_directory())) => trailingslashit(get_stylesheet_directory_uri()),
			wp_normalize_path(trailingslashit(get_template_directory()))   => trailingslashit(get_template_directory_uri()),
		);

		foreach ($roots as $path => $uri) {
			if (0 === strpos($dir, $path)) {
				$base = $uri . substr($dir, strlen($path));
				break;
			}
		}
	}

	return $base . ltrim($file, '/');
}

require_once TAL_PATH . 'class-tal-settings.php';
require_once TAL_PATH . 'class-tal-lookup.php';
require_once TAL_PATH . 'class-tal-cf7.php';
require_once TAL_PATH . 'class-tal-frontend.php';

TAL_Settings::init();
TAL_Lookup::init();
TAL_CF7::init();
TAL_Frontend::init();
