<?php

class jigoshop_software_updater extends jigoshop_software {
	
	function __construct() {
		
		parent::define_constants();
		$this->slug = JIGOSHOP_SOFTWARE_SLUG;		
		$this->api_url = JIGOSHOP_SOFTWARE_GITHUB_API_URL;
		$this->raw_url = JIGOSHOP_SOFTWARE_GITHUB_RAW_URL;
		$this->github_url = JIGOSHOP_SOFTWARE_GITHUB_URL;
		$this->zip_url = JIGOSHOP_SOFTWARE_GITHUB_ZIP_URL;
	  $this->requires = JIGOSHOP_SOFTWARE_REQUIRES_WP;
    $this->tested = JIGOSHOP_SOFTWARE_TESTED_WP;	

		$data = $this->get_plugin_data();
    $this->plugin_name = $data['Name'];
    $this->version = $data['Version'];
    $this->author = $data['Author'];
    $this->homepage = $data['PluginURI'];

		$this->new_version = $this->get_new_version();
		$this->last_updated = $this->get_date();
		$this->description = $this->get_description();
		

		if (WP_DEBUG) add_action( 'init', array(&$this, 'delete_transients') );

		add_filter('pre_set_site_transient_update_plugins', array(&$this, 'api_check'));
		
		// Hook into the plugin details screen
		add_filter('plugins_api', array(&$this, 'get_plugin_info'), 10, 3);
		
	}



	// For testing purpose, the site transient will be reset on each page load
	function delete_transients() {
		delete_site_transient('update_plugins');
		delete_site_transient($this->slug.'_new_version');
		delete_site_transient($this->slug.'_github_data');
		delete_site_transient($this->slug.'_changelog');
	}
	
	function get_new_version() {
		$version = get_site_transient($this->slug.'_new_version');
		if (!isset($version) || !$version || $version == '') {
			$raw_response = wp_remote_get($this->raw_url.'/README.md');
			$__version = explode('~Current Version:', $raw_response['body']);
			$_version = explode('~', $__version[1]);
			$version = $_version[0];
			set_site_transient($this->slug.'_new_version', $version, 60*60*60*6); // refresh every 6 hours
		}
		return $version;
	}
	
	function get_github_data() {
		$github_data = get_site_transient($this->slug.'_github_data');
		if (!isset($github_data) || !$github_data || $github_data == '') {		
			$github_data = wp_remote_get($this->api_url);
			$github_data = json_decode($github_data['body']);
			set_site_transient($this->slug.'_github_data', $github_data, 60*60*60*6); // refresh every 6 hours
		}
		return $github_data;			
	}
	
	function get_date() {
		$_date = $this->get_github_data();
		$date = $_date->updated_at;
		$date = date('Y-m-d', strtotime($_date->updated_at));
		return $date;
	}
	
	function get_description() {
		$_description = $this->get_github_data();
		return $_description->description;
	}
	
	function get_plugin_data() {
		include_once(ABSPATH.'/wp-admin/includes/plugin.php');
		$data = get_plugin_data(WP_PLUGIN_DIR.'/'.$this->slug);
		return $data;
	}

	// Hook into the plugin update check
	function api_check( $transient ) {
		
		// Check if the transient contains the 'checked' information
		// If no, just return its value without hacking it
		if( empty( $transient->checked ) )	return $transient;
		
		// check the version and make sure it's new
		$update = version_compare($this->new_version, $this->version);
		if ($update === 1) {		
			$response = new stdClass;
			$response->new_version = $this->new_version;
			$response->slug = $this->slug;		
			$response->url = $this->github_url;
			$response->package = $this->zip_url;

			// If response is false, don't alter the transient
			if( false !== $response ) $transient->response[$this->slug] = $response;
		}			
		return $transient;
	}

	function get_plugin_info( $false, $action, $args ) {

		$plugin_slug = plugin_basename( __FILE__ );

		// Check if this plugins API is about this plugin
		if( $args->slug != $this->slug ) return false;

    $response->slug = $this->slug;
    $response->plugin_name = $this->plugin_name;
    $response->version = $this->new_version;
    $response->author = $this->author;
    $response->homepage = $this->homepage;
    $response->requires = $this->requires;
    $response->tested = $this->tested;
    $response->downloaded = 0;
    $response->last_updated = $this->last_updated;
    $response->sections = array(
        'description' => $this->description,
    );        
    $response->download_link = $this->zip_url;

		return $response;
	}

}