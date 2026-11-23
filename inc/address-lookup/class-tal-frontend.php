<?php

/**
 * Front end: loads the script and hands it the parameter names.
 *
 * @package Theme\AddressLookup
 */

defined('ABSPATH') || exit;

class TAL_Frontend
{

	public static function init()
	{
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'));
	}

	public static function enqueue()
	{
		/**
		 * Filter whether the address lookup script loads on this request.
		 */
		if (! apply_filters('tal_enqueue_script', true)) {
			return;
		}

		$file = TAL_PATH . 'assets/address-lookup.js';

		wp_enqueue_script(
			'tal-address-lookup',
			tal_url('/address-lookup.js'),
			array(),
			file_exists($file) ? filemtime($file) : TAL_VERSION,
			true
		);

		$options = TAL_Settings::get();

		wp_localize_script(
			'tal-address-lookup',
			'TALConfig',
			array(
				'endpoint'  => rest_url('tal/v1/lookup'),
				'nonce'     => wp_create_nonce('wp_rest'),
				'params'    => array_values(TAL_CF7::param_names()),
				'updateUrl' => (bool) $options['update_url'],
				'prefill'   => (bool) apply_filters('tal_prefill_forms', true),
			)
		);
	}
}
