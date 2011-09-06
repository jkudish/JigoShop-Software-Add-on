<?php

/**
	* This file controls the whole Web Service API for activations & trials
	* @since 1.0
	* @author Joachim Kudish <info@jkudish.com>
	*/
	
class jigoshop_software_api extends jigoshop_software {
	
	public $debug;
	
	function __construct($debug = false) {
		
		$this->debug = (WP_DEBUG) ? true : $debug; // always on if WP_DEBUG is on
		if (isset($_GET['method'])) $method = $_GET['method'];
		elseif (isset($_GET['productid'])) $method = $_GET;
		else $method = $_POST;
					
		
		if (isset($_GET['request'])) { 

			$request = $_GET['request'];

			switch ($request) :
				case 'trial' :
					
					if (isset($method['productid'])) {
						
						$product_id = $method['productid'];				

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
				
				$required = array('email', 'licensekey', 'productid');
				$i = 0;
				$missing = '';
				foreach ($required as $req) {
					if (!isset($method[$req]) || $req == '') {
						$i++;
						if ($i > 1) $missing .= ', ';
						$missing .= $req;						
					}
				}
				
				if ($missing != '') {
					$this->error('100', 'The following required information is missing: '.$missing, null, array('activated' => false));
				} 
				
				$email = (isset($method['email'])) ? $method['email'] : null;
				$license_key = (isset($method['licensekey'])) ? $method['licensekey'] : null;
				$product_id = (isset($method['productid'])) ? $method['productid'] : null;
				$version = (isset($method['version'])) ? $method['version'] : null;
				$os = (isset($method['os'])) ? $method['os'] : null;
				$instance = (isset($method['instanceid'])) ? $method['instanceid'] : null;				
				
				if (!is_email($email)) $this->error('100', 'The email provided is invalid', null, array('activated' => false));
				
				$_orders = get_posts(array(
					'post_type' => 'shop_order',
					'posts_per_page' => -1,					
					'meta_query' => array(
						array(
							'key' => 'activation_email',
							'value' => $email
						)
					)
				));
				
				if (is_array($_orders) && count($_orders) > 0) {
					foreach ($_orders as $order) {
						$data = get_post_meta($order->ID, 'order_data', true);
						if (@$data['productid'] == $product_id) {
							if (@$data['license_key'] == $license_key) {
		
								// we have a match, let's make sure it's a completed sale
								$order_status = wp_get_post_terms($order->ID, 'shop_order_status');
								$order_status = $order_status[0]->slug;
								if ($order_status == 'completed') {
									
									$global_activations = get_option('jigoshop_software_global_activations');
									$activations = get_post_meta($order->ID, 'activations', true);
									$activations_possible = $data['activations_possible'];
									$remaining_activations = $data['remaining_activations'];
																		
									if ($instance) { // checking existing activation
										$activations = get_post_meta($order->ID, 'activations', true);

										if (isset($activations[$instance]) && is_array($activations[$instance])) { // this instance exists
											if ($activations[$instance]['active'] == true) {
												$activated = true;
												$output_data = $data;
												$output_data['activated'] = true;
												$output_data['instanceid'] = $instance;
												$output_data['message'] = $data['remaining_activations'].' out of '.$activations_possible.' activations remaining';
												$to_output = array('activated', 'instanceid', 'message');
												$json = $this->prepare_output($to_output, $output_data);
											} else {
												$this->error('102', 'This instance isn\'t active', null, array('activated' => false, 'secret' => $data['secret_product_key']));
											}	

										} else { // the instance doesn't exist
											
											$this->error('102', 'This instance doesn\'t exist', null, array('activated' => false, 'secret' => $data['secret_product_key']));
											
										}
										
									} else { // new activation																			
										
										// check number of remaining activations
										if ($remaining_activations > 0) {
											// let's activate
											$activated = true;
											$data['remaining_activations'] = $remaining_activations-1; // decrease remaining activations
											$instance = parent::generate_license_key();
											$activation = array('time' => time(), 'active' => true, 'version' => $version, 'os' => $os, 'instance' => $instance, 'product_id' => $data['productid']);
											// store the activation globally
											$global_activations[$instance] = $activation;
											update_option('jigoshop_software_global_activations', $global_activations);
											
											// store the activation for this purchase only now
											unset($activation['product_id']);
											$activations[$instance] = $activation;
											update_post_meta($order->ID, 'activations', $activations);
											
											// update the order data
											update_post_meta($order->ID, 'order_data', $data);
											
											// send email to the customer
											$order_items = get_post_meta($order->ID, 'order_items', true);											
											$email_data = array(
												'email' => get_post_meta($order->ID, 'activation_email', true),
												'remaining_activations' => $data['remaining_activations'],
												'activations_possible' => $data['activations_possible'],
												'product' => $order_items[0]['name'],
											);
											parent::process_email($email_data, 'new_activation');
											
											// return the json
											$output_data = $data;
											$output_data['activated'] = true;
											$output_data['instanceid'] = $instance;
											$output_data['message'] = $data['remaining_activations'].' out of '.$activations_possible.' activations remaining';
											$to_output = array('activated', 'instanceid', 'message');
											$json = $this->prepare_output($to_output, $output_data);
											
										} else {											
											$this->error('103', 'Remaining activations is equal to zero', null, array('activated' => false, 'secret' => $data['secret_product_key']));
										}
										
									}
								} else {
									$this->error('101', 'The purchase matching this product is not complete', null,  array('activated' => false, 'secret' => $data['secret_product_key']));
								}
							}
						} 					
					}
					if (!isset($activated)) {
						// if we got here than there were no matches for productid and license key
						$data = array('activated' => false);
						$this->error('101', 'No purchase orders match this product ID and license key', null, $data);
					}		
				} else { 
					$data = array('activated' => false);
					$this->error('101', 'No purchase orders match this e-mail', null, $data);
				}					
				
				
				break;
				
				case 'activation_reset' :

				$required = array('email', 'productid');
				$i = 0;
				$missing = '';
				foreach ($required as $req) {
					if (!isset($method[$req]) || $req == '') {
						$i++;
						if ($i > 1) $missing .= ', ';
						$missing .= $req;						
					}
				}
				
				if ($missing != '') {
					$this->error('100', 'The following required information is missing: '.$missing, null, array('reset' => false));
				} 
				
				$email = (isset($method['email'])) ? $method['email'] : null;
				$license_key = (isset($method['licensekey'])) ? $method['licensekey'] : null;
				$product_id = (isset($method['productid'])) ? $method['productid'] : null;				
				
				if (!is_email($email)) $this->error('100', 'The email provided is invalid', null, array('reset' => false));
				
				$_orders = get_posts(array(
					'post_type' => 'shop_order',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => 'activation_email',
							'value' => $email
						)
					)
				));
				
				if (is_array($_orders) && count($_orders) > 0) {
					foreach ($_orders as $order) {
						$data = get_post_meta($order->ID, 'order_data', true);
						if (@$data['productid'] == $product_id) {							
							$__order_id = $order->ID; // product id matches, let's store a var and come back to this one later if need be
							if (@$data['license_key'] == $license_key) {
								
								// get global and purchase only activations
								$global_activations = get_option('jigoshop_software_global_activations');
								$activations = get_post_meta($order->ID, 'activations', true);
								
								// loop through the activations and deactivate them
								foreach ($activations as $instance => $activation) {
									$global_activations[$instance]['active'] = false;
									$activations[$instance]['active'] = false;
								}
								
								update_option('jigoshop_software_global_activations', $global_activations);
								update_post_meta($order->ID, 'activations', $activations);

								// reset number of activations
								$data['remaining_activations'] = $data['activations_possible'];
								update_post_meta($order->ID, 'order_data', $data);
								
								$output_data = $data;
								$output_data['reset'] = true;
								$to_output = array('reset');
								$json = $this->prepare_output($to_output, $output_data);								
								
							} elseif (isset($__order_id)) {
								
								// get global and purchase only activations
								$global_activations = get_option('jigoshop_software_global_activations');
								$activations = get_post_meta($__order_id, 'activations', true);
								
								// loop through the activations and deactivate them
								foreach ($activations as $instance => $activation) {
									$global_activations[$instance]['active'] = false;
									$activations[$instance]['active'] = false;
								}
								
								update_option('jigoshop_software_global_activations', $global_activations);
								update_post_meta($__order_id, 'activations', $activations);

								// reset number of activations
								$remaining_activations = $data['remaining_activations'];
								$data['activations_possible'] = $remaining_activations;
								update_post_meta($__order_id, 'order_data', $data);
								
								$output_data = $data;
								$output_data['reset'] = true;
								$to_output = array('reset');
								$json = $this->prepare_output($to_output, $output_data);																
								
							} else {
								$this->error('101', 'No purchase orders match this product ID and/or license key', null, $data);
							}								
						} 
					}						
				} else {
					$this->error('101', 'No purchase orders match this email');
				}	
				
				break;
				
				case 'generate_key' :
				
				$key = parent::generate_license_key();
				$json = array('key' => $key);
				
				if (isset($method['format']) && $method['format'] == 'plain') {
					die($key);
				}
				
				break;
				
			endswitch;
			
			if (!isset($json)) $this->error('100', 'Invalid API Request');

		} else {
			
			$this->error('100', 'No API Request Made');
			
		}
		
		header( "Cache-Control: no-store");
		header( "Pragma: ");
		header( "Content-Type: application/json" );
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
		$secret = (isset($data['secret_product_key'])) ? $data['secret_product_key'] : 'null';
		$sig_array = array('secret' => $secret);

		foreach ($to_output as $k => $v) {
			if (is_string($k)) $output[$k] = $data[$v];
			else $output[$v] = $data[$v];
		}
		
		$sig_out = $output;
		$sig_array = array_merge($sig_array, $sig_out);
		foreach ($sig_array as $k => $v) {
			if ($v === false) $v = "false";
			if ($v === true) $v = "true";			
			$sigjoined[] = "$k=$v";
		}
		$sig = implode('&', $sigjoined);
		if (!$this->debug) $sig = md5($sig);
		$output['sig'] = $sig;
		return $output;
	}
	
	/**
	 	* error()
	 	* spits out an error in json
		* @param $code (string), the error code/number
		* @param $code (string), the debug message to include if debug mode is on
		* @return null
		*/
	function error($code = 100, $debug_message = null, $secret = null, $addtl_data = array() ) {
		switch ($code) :
			case '101' :
				$error = array('error' => 'Invalid License Key', 'code' => '101');
			break;
			case '102' :
				$error = array('error' => 'Software has been deactivated', 'code' => '102');
			break;
			case '103' :
				$error = array('error' => 'Exceeded maximum number of activations', 'code' => '103');
			break;		
			default :
				$error = array('error' => 'Invalid Request', 'code' => '100');
			break;
		endswitch;
		if (isset($this->debug) && $this->debug == true) {
			if (@!$debug_message) $debug_message = 'No debug information available';
			$error['additional info'] = $debug_message;
		}	
		if (isset($addtl_data['secret'])) {
			$secret = $addtl_data['secret'];
			unset($addtl_data['secret']);
		}
		foreach ($addtl_data as $k => $v) {
			$error[$k] = $v;
		}	
		$secret = ($secret) ? $secret : 'null';
		foreach ($error as $k => $v) {
			if ($v === false) $v = "false";
			if ($v === true) $v = "true";
			$sigjoined[] = "$k=$v";
		}
		$sig = implode('&', $sigjoined);
		$sig = 'secret='.$secret.'&'.$sig;
		if (!$this->debug) $sig = md5($sig);
		$error['sig'] = $sig;		
		$json = $error;
		header( "Cache-Control: no-store");
		header( "Pragma: ");
		header( "Content-Type: application/json" );
		die(json_encode($json));
		exit;
	}
		
}

new jigoshop_software_api(); // run the API