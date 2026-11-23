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
 * Public URL of a file in this folder.
 *
 * Don't derive this from __DIR__: on hosts with symlinked paths __DIR__ is the resolved path,
 * which never matches get_template_directory(), and the URL came out empty.
 */
function tal_url($file = '')
{
	return get_theme_file_uri('inc/address-lookup/' . ltrim($file, '/'));
}

require_once TAL_PATH . 'class-tal-settings.php';
require_once TAL_PATH . 'class-tal-lookup.php';
require_once TAL_PATH . 'class-tal-cf7.php';
require_once TAL_PATH . 'class-tal-frontend.php';

TAL_Settings::init();
TAL_Lookup::init();
TAL_CF7::init();
TAL_Frontend::init();
