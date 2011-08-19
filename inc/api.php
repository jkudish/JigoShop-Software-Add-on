<?php
/*
	TODO more inline documentation
*/

class jigoshop_software_api extends jigoshop_software {
	
	public $debug;
	
	function __construct($debug = false) {
		
		$this->debug = (WP_DEBUG) ? true : $debug; // always on if WP_DEBUG is on
		if (isset($_GET['request'])) { 

			$request = $_GET['request'];

			switch ($request) :
				case 'trial' :
					
					if (isset($_GET['productid'])) {
						
						$product_id = $_GET['productid'];				

						$__prod = get_post($product_id); // first trying to see if this is a post_id
						if ($__prod) {
							$trial_prod = $__prod; 

						} else {
							// if that was not a post_id, let's do some meta_query stuff to find the right product
							$_prod = get_posts( array(
								'post_type' => 'product', 
								'posts_per_page' => 1,
								'meta_query' => array(
									'relation' => 'OR',
									array(
										'key' => 'soft_product_id',
										'value' => $product_id,
										),
									// array(
									// 	'key' => '',
									// 	'value' => $product_id,
									// 	),										
									)
								));

							if (is_array($_prod) && count($_prod) == 1) {
								$trial_prod = $_prod[0]; // there is a match, use that
							} else {
								$this->error('100', 'Product ID not found');
							}							
						}
						
						$data = get_post_meta($trial_prod->ID, 'product_data', true);
						$to_output = array('duration' => 'trial', 'units' => 'trial_unit');
						$json = $this->prepare_output($to_output, $data);
						
					} else { 

						$this->error('100', 'No product ID given');

					}	
					
				break;
				case 'activation' :
				
				break;
				
				case 'activation_reset' :
				
				break;
				
			endswitch;
			
			if (!isset($json)) $this->error('100', 'Invalid API Request');

		} else {
			
			$this->error('100', 'No API Request Made');
			
		}
		
		die(json_encode($json));
	}
	
	/**
	 	* prepare_output()
	 	* prepare the array which will be used for the json response. does all the magic for the sig to work
		* @param $to_output (array), the output to include
		* @param $data (array), the data from which to pull the secret product key
		* @return $output (array), the data ready for json including the md5 sig
		*/
	function prepare_output($to_output = array(), $data = array()) {
		$secret = @$data['secret_product_key'];
		$sig_array = array('secret' => $secret);

		foreach ($to_output as $k => $v) {
			$output[$k] = $data[$v];
		}
		
		$sig_out = $output;
		$sig_array = array_merge($sig_array, $sig_out);
		$sig = http_build_query($sig_array);
		$sig = md5($sig);
		$output['sig'] = $sig;
		return $output;
	}
	
	/**
	 	* error()
	 	* spits out an error using json [using die()]
		* @param $code (string), the error code/number
		* @param $code (string), the debug message to include if debug mode is on
		* @return null
		*/
	function error($code = 100, $debug_message = null) {
		switch ($code) :
			case '101' :
				$error = array('101', 'Invalid License Key');
			break;
			case '102' :
				$error = array('102', 'Software has been deactivated');
			break;
			case '103' :
				$error = array('103', 'Exceeded maximum number of activations');
			break;		
			default :
				$error = array('100', 'Invalid Request');
			break;
		endswitch;
		if (isset($this->debug) && $this->debug == true) {
			if (@!$debug_message) $debug_message = 'No debug information available';
			$error[] = $debug_message;
		}	
		$json['error'] = $error;
		die(json_encode($json)); exit;		
	}
		
}

new jigoshop_software_api();