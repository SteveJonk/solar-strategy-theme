<?php

// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/*
 * VITE & Tailwind JIT development
 * Inspired by https://github.com/andrefelipe/vite-php-setup
 *
 */

// dist subfolder - defined in vite.config.json
define('DIST_DEF', 'dist');

// defining some base urls and paths
define('DIST_URI', get_template_directory_uri() . '/' . DIST_DEF);
define('DIST_PATH', get_template_directory() . '/' . DIST_DEF);

// js enqueue settings
define('JS_DEPENDENCY', array()); // array('jquery') as example
define('JS_LOAD_IN_FOOTER', true); // load scripts in footer?

// deafult server address, port and entry point can be customized in vite.config.json
define('VITE_SERVER', 'http://localhost:3000');
define('VITE_ENTRY_POINT', '/main.js');

function theme_resources()
{

    if (defined('IS_VITE_DEVELOPMENT') && IS_VITE_DEVELOPMENT === true) {

        // insert hmr into head for live reload
        function vite_head_module_hook()
        {
            echo '<script type="module" crossorigin src="' . VITE_SERVER . VITE_ENTRY_POINT . '"></script>';
        }
        add_action('wp_head', 'vite_head_module_hook');
    } else {

        // production version, 'npm run build' must be executed in order to generate assets
        // ----------

        // read manifest.json to figure out what to enqueue
        $manifest = json_decode(file_get_contents(DIST_PATH . '/manifest.json'), true);
        // is ok
        if (is_array($manifest)) {
            // get first key, by default is 'main.js' but it can change
            if (isset($manifest['main.js'])) {

                // enqueue CSS files
                foreach ($manifest['main.js']['css'] as $css_file) {
                    wp_enqueue_style('main', DIST_URI . '/' . $css_file);
                }

                // enqueue main JS file
                $js_file = @$manifest['main.js']['file'];
                if (!empty($js_file)) {
                    wp_enqueue_script('main', DIST_URI . '/' . $js_file, JS_DEPENDENCY, '', JS_LOAD_IN_FOOTER);
                }
            }
        }
    }
}

// enqueue hook
// Check vite dev
if (defined('IS_VITE_DEVELOPMENT') && IS_VITE_DEVELOPMENT === true) {
    add_action('wp_enqueue_scripts', 'theme_resources');
} else {
    add_action('enqueue_block_assets', 'theme_resources');
}
add_action('login_enqueue_scripts', 'theme_resources');

// ponytail: workaround for host firewall blocking /wp-json/batch/v1. Remove once the host whitelists it.
add_action('rest_api_init', function () {
    $server = rest_get_server();
    $routes = $server->get_routes();
    if (empty($routes['/batch/v1'][0])) return;

    register_rest_route('solar/v1', '/multi', [
        'methods'             => 'POST',
        'callback'            => [$server, 'serve_batch_request_v1'],
        'permission_callback' => '__return_true', // same as core; each sub-request checks its own permissions
        'args'                => $routes['/batch/v1'][0]['args'],
    ]);
}, 99);

add_action('admin_enqueue_scripts', function () {
    wp_add_inline_script('wp-api-fetch', "wp.apiFetch.use(function(o,n){if(o.path&&o.path.indexOf('/batch/v1')===0){o.path=o.path.replace('/batch/v1','/solar/v1/multi');}return n(o);});");
});