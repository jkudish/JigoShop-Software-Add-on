<?php

// [debug]: Enable update check on every request. Normally you don't need this! This is for testing only!
set_site_transient('update_plugins', null);

// [debug]: Show which variables are being requested when query plugin API
add_filter('plugins_api_result', 'aaa_result', 10, 3);
function aaa_result($res, $action, $args) {
	print_r($res);
	return $res;
}


// Take over the update check
add_filter('pre_set_site_transient_update_plugins', 'check_for_plugin_update');

function check_for_plugin_update($checked_data) {
	
	// Start checking for an update
	$raw_response = wp_remote_get('https://raw.github.com/jkudish/JigoShop-Software-Add-on/master/README.md');
	$__version = explode('~Current Version:', $raw_response['body']);
	$_version = explode('~', $__version[1]);
	die(var_dump($_version[0]));
	
	if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
		$response = unserialize($raw_response['body']);
	
	if (isset($response) && is_object($response) && !empty($response)) // Feed the update data into WP updater
		$checked_data->response[$plugin_slug .'/'. $plugin_slug .'.php'] = $response;
	
	return $checked_data;
	die(var_dump($checked_data));
}

function prepare_request($action, $args) {
	global $wp_version;
	
	return array(
		'body' => array(
			'action' => $action, 
			'request' => serialize($args),
			'api-key' => md5(get_bloginfo('url'))
		),
		'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
	);	
}
