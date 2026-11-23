<?php

// Exit if accessed directly
if (!defined('ABSPATH'))
	exit;

// functions.php is empty so you can easily track what code is needed in order to Vite + Tailwind JIT run well


// Main switch to get frontend assets from a Vite dev server OR from production built folder
// it is recommended to move it into wp-config.php
define('IS_VITE_DEVELOPMENT', false);


require_once "inc/inc.vite.php";

// Include needed functions
require_once "inc/components/login-screen.php";
require_once "inc/components/navbar.php";
require_once "inc/components/sidebars.php";
require_once "inc/components/group-block-style.php";
require_once "inc/components/text-block-style.php";
require_once "inc/components/divider-block-style.php";
require_once "inc/components/list-block-style.php";
require_once "inc/shortcodes/archive-name.php";
require_once "inc/shortcodes/category-name.php";
require_once "inc/shortcodes/child-pages.php";
require_once 'inc/address-lookup/init.php';

// Include Gutenberg Blocks
require_once "inc/gutenberg-blocks/example-block/example-block.php";

function theme_support()

{
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('responsive-embeds');
	add_theme_support('wp-block-styles');
	add_theme_support('appearance-tools');
	add_theme_support('align-wide');
	add_theme_support('editor-styles');

	register_nav_menu('headerMenuLeft', 'Header Menu Left');
	register_nav_menu('headerMenuRight', 'Header Menu Right');
	add_editor_style('editor.css');
}

add_action('after_setup_theme', 'theme_support');

function replace_all_nbsp_with_space($content)
{
	// Replace all forms of non-breaking space with a regular space
	return str_replace(
		["&nbsp;", "\u{00A0}", "\xC2\xA0"],
		' ',
		$content
	);
}

add_filter('the_content', 'replace_all_nbsp_with_space', 20);
add_filter('the_title', 'replace_all_nbsp_with_space', 20);
add_filter('the_excerpt', 'replace_all_nbsp_with_space', 20);

add_filter('get_the_excerpt', function ($excerpt) {
	return '<p class="excerpt">' . wp_trim_words($excerpt, 10, '...') . '</p>';
}, 999);

/*
 * Add service selection to contactform 7 webhook
 */

add_filter( 'ctz_post_request_args', function ( $args ) {
    $submission = WPCF7_Submission::get_instance();
    $services   = $submission ? (array) $submission->get_posted_data( 'services' ) : [];

    $map = [
        'Zonnepanelen'  => 'zonnepaneel_1_in_offerte',
        'Thuisbatterij' => 'thuisbatterij_1_in_offerte',
        'Laadpaal'      => 'laadpaal_1_in_offerte',
        'EMS'           => 'ems_1_in_offerte',
    ];

    // "Totaaloplossing" = all products
    if ( in_array( 'Totaaloplossing', $services, true ) ) {
        $services = array_keys( $map );
    }

    $is_json = is_string( $args['body'] );
    $body    = $is_json ? json_decode( $args['body'], true ) : $args['body'];
    if ( ! is_array( $body ) ) {
        return $args; // invalid custom body JSON: leave the request untouched
    }

    foreach ( $map as $label => $key ) {
        if ( in_array( $label, $services, true ) ) {
            $body[ $key ] = 1;
        }
    }

    $args['body'] = $is_json ? wp_json_encode( $body ) : $body;
    return $args;
} );
