<?php

/**
	* This file controls the whole Web Service API for activations & trials
	*
	* @since 1.0
	* @author Joachim Kudish <info@jkudish.com>
	*/


class Jigoshop_Software_Api extends jigoshop_software {

	/**
	 * determine if debug is on or off
	 * @var bool
	 */
	public $debug;

	/**
	 * class constructor & where all the magic happens
	 *
	 * @param bool $debug debug on/off
	 * @return void
	 */
	function __construct( $debug = false ) {

		$this->debug = (WP_DEBUG) ? true : $debug; // always on if WP_DEBUG is on

		if ( isset( $_REQUEST['request'] ) ) {
			$request = $_REQUEST['request'];

			$nonce = (isset($_REQUEST['nonce'])) ? $_REQUEST['nonce'] : false;

			switch ($request) :
				case 'trial' :

					if ( isset( $_REQUEST['productid'] ) ) {
						$product_id = $_REQUEST['productid'];

						$__prod = get_post( $product_id ); // first trying to see if this is a post_id
						if ( $__prod ) {
							$trial_prod = $__prod;
						} else {
							// if that was not a post_id, let's do some meta_query stuff to find the right product
							$_prod = get_posts(
											array(
												'post_type' => 'product',
												'posts_per_page' => 1,
												'meta_query' => array(
													'relation' => 'OR',
													array(
														'key' => 'soft_product_id',
														'value' => $product_id,
													),
												),
											)
							);

							if ( is_array( $_prod ) && count( $_prod ) == 1 ) {
								$trial_prod = $_prod[0]; // there is a match, use that
							} else {
								$this->error( '100', __( 'Product ID not found', 'jigoshop-software' ) );
							}
						}

						$data = get_post_meta( $trial_prod->ID, 'product_data', true );
						$data['time'] = time();
						$to_output['duration'] = 'trial';
						if ( $nonce ) {
							$data['nonce'] = $nonce;
							$to_output['nonce'] = 'nonce';
						}
						$to_output['timestamp'] = 'time';
						$to_output['units'] = 'trial_unit';
						$json = $this->prepare_output( $to_output, $data );
					} else {
						$this->error( '100', __( 'No product ID given', 'jigoshop-software' ) );
					}

				break;
				case 'activation' :

				$required = array( 'email', 'licensekey', 'productid' );
				$i = 0;
				$missing = '';
				foreach ( $required as $req ) {
					if ( !isset( $_REQUEST[$req] ) || $req == '' ) {
						$i++;
						if ($i > 1) $missing .= ', ';
						$missing .= $req;
					}
				}

				if ( $missing != '' ) {
					$this->error( '100', __( 'The following required information is missing', 'jigoshop-software' ) . ': ' . $missing, null, array( 'activated' => false ) );
				}

				$email = ( isset( $_REQUEST['email'] ) ) ? $_REQUEST['email'] : null;
				$license_key = ( isset( $_REQUEST['licensekey'] ) ) ? $_REQUEST['licensekey'] : null;
				$product_id = ( isset( $_REQUEST['productid'] ) ) ? $_REQUEST['productid'] : null;
				$version = ( isset( $_REQUEST['version'] ) ) ? $_REQUEST['version'] : null;
				$os = ( isset( $_REQUEST['os'] ) ) ? $_REQUEST['os'] : null;
				$instance = ( isset( $_REQUEST['instanceid'] ) ) ? $_REQUEST['instanceid'] : null;

				if ( !is_email( $email ) ) $this->error( '100', __( 'The email provided is invalid', 'jigoshop-software' ), null, array( 'activated' => false ) );

				$_orders = get_posts(
								array(
									'post_type' => 'shop_order',
									'posts_per_page' => -1,
									'meta_query' => array(
										array(
											'key' => 'activation_email',
											'value' => $email,
										),
									),
								)
				);

				if ( is_array( $_orders ) && count( $_orders ) > 0 ) {
					foreach ( $_orders as $order ) {
						$data = get_post_meta( $order->ID, 'order_data', true );
                        if ( isset( $data['license_key'] ) && $data['license_key'] == $license_key ) {
						    if ( isset( $data['productid'] ) && $data['productid'] == $product_id ) {
								// check if the order has been upgraded
								if ( empty( $data['has_been_upgraded'] ) || 'on' != $data['has_been_upgraded'] ) {
									// make sure it's a completed sale
									$order_status = wp_get_post_terms( $order->ID, 'shop_order_status' );
									$order_status = $order_status[0]->slug;
									if ( $order_status == 'completed' ) {
										$activations = get_post_meta( $order->ID, 'activations', true );
										$activations_possible = $data['activations_possible'];
										$remaining_activations = $data['remaining_activations'];

										if ( $instance ) {
											// checking existing activation
											$activations = get_post_meta( $order->ID, 'activations', true );

											if ( isset( $activations[$instance] ) && is_array( $activations[$instance] ) ) {
												// this instance exists
												if ( $activations[$instance]['active'] == true ) {
													$activated = true;
													$output_data = $data;
													$output_data['activated'] = true;
													$output_data['instanceid'] = $instance;
													$output_data['message'] = $data['remaining_activations'].' out of '.$activations_possible.' activations remaining';
													$output_data['time'] = time();
													$to_output = array( 'activated', 'instanceid' );
													$to_output['message'] = 'message';
													if ( $nonce ) {
														$output_data['nonce'] = $nonce;
														$to_output['nonce'] = 'nonce';
													}
													$to_output['timestamp'] = 'time';
													$json = $this->prepare_output( $to_output, $output_data );
												} else {
													$this->error( '102', __( 'This instance isn\'t active', 'jigoshop-software' ), null, array( 'activated' => false, 'secret' => $data['secret_product_key'] ) );
												}
											} else {
												// the instance doesn't exist
												$this->error( '102', __( 'This instance doesn\'t exist', 'jigoshop-software' ), null, array( 'activated' => false, 'secret' => $data['secret_product_key'] ) );
											}
										} else {
											// new activation
											// check number of remaining activations
											if ( $remaining_activations > 0 ) {
												// let's activate
												$activated = true;
												$data['remaining_activations'] = $remaining_activations - 1; // decrease remaining activations
												$instance = parent::generate_license_key();
												$activation = array( 'time' => time(), 'active' => true, 'version' => $version, 'os' => $os, 'instance' => $instance, 'product_id' => $data['productid'] );

												// store the activation for this purchase
												unset( $activation['product_id'] );
												$activations[$instance] = $activation;
												update_post_meta( $order->ID, 'activations', $activations );

												// update the order data
												update_post_meta( $order->ID, 'order_data', $data );

												// send email to the customer
												$order_items = get_post_meta( $order->ID, 'order_items', true );
												$email_data = array(
													'email' => get_post_meta( $order->ID, 'activation_email', true ),
													'remaining_activations' => $data['remaining_activations'],
													'activations_possible' => $data['activations_possible'],
													'product' => $order_items[0]['name'],
												);
												parent::process_email( $email_data, 'new_activation' );

												// return the json
												$output_data = $data;
												$output_data['activated'] = true;
												$output_data['instanceid'] = $instance;
												$output_data['message'] = $data['remaining_activations'] .' '. __( 'out of', 'jigoshop-software' ) .' ' . $activations_possible . ' ' . __( 'activations remaining', 'jigoshop-software' );
												$output_data['time'] = time();
												$to_output = array( 'activated', 'instanceid' );
												$to_output['message'] = 'message';
												if ( $nonce ) {
													$output_data['nonce'] = $nonce;
													$to_output['nonce'] = 'nonce';
												}
												$to_output['timestamp'] = 'time';
												$json = $this->prepare_output( $to_output, $output_data );
											} else {
												$this->error( '103', __( 'Remaining activations is equal to zero', 'jigoshop-software' ), null, array( 'activated' => false, 'secret' => $data['secret_product_key'] ) );
											}
										}
									} else {
										$this->error( '101', __( 'The purchase matching this product is not complete', 'jigoshop-software' ), null,  array( 'activated' => false, 'secret' => $data['secret_product_key'] ) );
									}
								} else {
									$this->error( '105', __( 'This purchase has been upgraded and is no longer active', 'jigoshop-software' ), null,  array( 'activated' => false, 'secret' => $data['secret_product_key'] ) );
								}
							} else {
        						$data = array( 'activated' => false );
        						$this->error( '106', __( 'License key for wrong product', 'jigoshop-software' ), null, $data );							    
							}
						}
					}
					if ( !isset( $activated ) ) {
						// if we got here than there were no matches for productid and license key
						$data = array( 'activated' => false );
						$this->error( '101', __( 'No purchase orders match this product ID and license key', 'jigoshop-software' ), null, $data );
					}
				} else {
					$data = array( 'activated' => false );
					$this->error( '101', __( 'No purchase orders match this e-mail', 'jigoshop-software' ), null, $data );
				}


				break;

				case 'activation_reset' :

				$required = array( 'email', 'productid', 'licensekey' );
				$i = 0;
				$missing = '';
				foreach ( $required as $req ) {
					if ( !isset( $_REQUEST[$req] ) || $req == '' ) {
						$i++;
						if ($i > 1) $missing .= ', ';
						$missing .= $req;
					}
				}

				if ( $missing != '' ) {
					$this->error( '100', __( 'The following required information is missing', 'jigoshop-software' ) . ': ' . $missing, null, array( 'reset' => false ) );
				}

				$email = ( isset( $_REQUEST['email'] ) ) ? $_REQUEST['email'] : null;
				$license_key = ( isset( $_REQUEST['licensekey'] ) ) ? $_REQUEST['licensekey'] : null;
				$product_id = ( isset( $_REQUEST['productid'] ) ) ? $_REQUEST['productid'] : null;

				if ( !is_email( $email ) ) $this->error( '100', __( 'The email provided is invalid', 'jigoshop-software' ), null, array( 'reset' => false ) );

				$_orders = get_posts(
								array(
									'post_type' => 'shop_order',
									'posts_per_page' => -1,
									'meta_query' => array(
										array(
											'key' => 'activation_email',
											'value' => $email,
										),
									),
								)
				);

				if ( is_array( $_orders ) && count( $_orders ) > 0 ) {
					$no_match_license_key = 0;
					$no_match_product_id = 0;
					foreach ( $_orders as $order ) {
						$data = get_post_meta( $order->ID, 'order_data', true );
						if ( isset( $data['productid'] ) && $data['productid'] == $product_id ) {
							if ( isset( $data['license_key'] ) && $data['license_key'] == $license_key ) {
								$activations = get_post_meta( $order->ID, 'activations', true );

								// loop through the activations and deactivate them
								foreach ( $activations as $instance => $activation ) {
									$activations[$instance]['active'] = false;
								}

								update_post_meta( $order->ID, 'activations', $activations );

								// reset number of activations
								$data['remaining_activations'] = $data['activations_possible'];
								update_post_meta( $order->ID, 'order_data', $data );

								$output_data = $data;
								$output_data['reset'] = true;
								$output_data['timestamp'] = time();
								$to_output = array();
								if ( $nonce ) {
									$output_data['nonce'] = $nonce;
									$to_output['nonce'] = 'nonce';
								}
								$to_output['reset'] = 'reset';
								$to_output['timestamp'] = 'timestamp';
								$json = $this->prepare_output( $to_output, $output_data );
							} else {
								$no_match_license_key++;
							}
						} else {
							$no_match_product_id++;
						}
					}
					if ( !isset( $json ) ) {
						if ( $no_match_license_key > 0 ) {
							$this->error( '101', __( 'No purchase orders match this license key', 'jigoshop-software' ), null, array( 'reset' => false ) );
						} elseif ( $no_match_product_id > 0 ) {
							$this->error( '100', __( 'No purchase orders match this productid', 'jigoshop-software' ), null, array( 'reset' => false ) );
						} else {
							$this->error( '100', __( 'An undisclosed error occurred', 'jigoshop-software' ), null, array( 'reset' => false ) );
						}
					}
				} else {
					$this->error( '101', __( 'No purchase orders match this email', 'jigoshop-software' ), null, array( 'reset' => false ) );
				}

				break;

				case 'generate_key' :

				$key = parent::generate_license_key();
				$json = array( 'key' => $key );

				if ( isset($_REQUEST['format'] ) && $_REQUEST['format'] == 'plain' ) {
					die( $key );
				}

				break;

				case 'deactivation' :

				$required = array( 'email', 'instanceid', 'licensekey' );
				$i = 0;
				$missing = '';
				foreach ( $required as $req ) {
					if ( !isset( $_REQUEST[$req] ) || $req == '' ) {
						$i++;
						if ($i > 1) $missing .= ', ';
						$missing .= $req;
					}
				}

				if ( $missing != '' ) {
					$this->error( '100', __( 'The following required information is missing', 'jigoshop-software' ) . ': ' . $missing, null, array( 'reset' => false ) );
				}

				$email = ( isset( $_REQUEST['email'] ) ) ? $_REQUEST['email'] : null;
				$license_key = ( isset( $_REQUEST['licensekey'] ) ) ? $_REQUEST['licensekey'] : null;
				$instanceid = ( isset( $_REQUEST['instanceid'] ) )  ? $_REQUEST['instanceid'] : null;

				if (  !is_email( $email ) ) $this->error( '100', __( 'The email provided is invalid', 'jigoshop-software' ), null, array( 'reset' => false ) );

				$_orders = get_posts(
								array(
									'post_type' => 'shop_order',
									'posts_per_page' => -1,
									'meta_query' => array(
										array(
											'key' => 'activation_email',
											'value' => $email,
										),
									)
								)
				);

				if ( !is_wp_error( $_orders ) && !empty( $_orders )  ) {
					$no_match_license_key = 0;
					foreach ( $_orders as $order ) {
						$data = get_post_meta( $order->ID, 'order_data', true );
						if ( isset( $data['license_key'] ) && $data['license_key'] == $license_key ) {
							$activations = get_post_meta( $order->ID, 'activations', true );

							// find the instance & deactivate it
							if ( isset( $activations[$instanceid] ) ) {
								if ( $activations[$instanceid]['active'] ) {
									$activations[$instanceid]['active'] = false;
								} else {
									$this->error( '102', __( 'The instance ID provided is already deactivated', 'jigoshop-software' ), null, array( 'reset' => false ) );
								}
							} else {
								$this->error( '104', __( 'The instance ID provided is invalid', 'jigoshop-software' ), null, array( 'reset' => false ) );
							}

							update_post_meta( $order->ID, 'activations', $activations );

							// reset number of activations
							$data['remaining_activations'] = (int) $data['remaining_activations'] + 1;
							update_post_meta( $order->ID, 'order_data', $data );

							$output_data = $data;
							$output_data['reset'] = true;
							$output_data['timestamp'] = time();
							$to_output = array();
							if ( $nonce ) {
								$output_data['nonce'] = $nonce;
								$to_output['nonce'] = 'nonce';
							}
							$to_output['reset'] = 'reset';
							$to_output['timestamp'] = 'timestamp';
							$json = $this->prepare_output( $to_output, $output_data );
						} else {
							$no_match_license_key++;
						}
					}

					if ( !isset( $json ) ) {
						if ( $no_match_license_key > 0 ) {
							$this->error( '101', __( 'No purchase orders match this license key', 'jigoshop-software' ) , null, array( 'reset' => false ) );
						} else {
							$this->error( '100', __( 'An undisclosed error occurred', 'jigoshop-software' ) , null, array( 'reset' => false ) );
						}
					}
				} else {
					$this->error( '101', __( 'No purchase orders match this email', 'jigoshop-software' ), null, array( 'reset' => false ) );
				}

				break;

			endswitch;

			if ( !isset( $json ) ) $this->error( '100', __( 'Invalid API Request', 'jigoshop-software' ) );
		} else {
			$this->error( '100', __( 'No API Request Made', 'jigoshop-software' ) );
		}
		header( 'Cache-Control: no-store' );
		if ( function_exists( 'header_remove' ) ) {
			header_remove( 'Cache-Control' );
			header_remove( 'Pragma' );
			header_remove( 'Expires' );
			header_remove( 'Last-Modified' );
			header_remove( 'X-Pingback' );
			header_remove( 'X-Powered-By' );
			header_remove( 'Set-Cookie' );
		} else {
			header( 'Cache-Control: ' );
			header( 'Pragma: ' );
			header( 'Expires: ' );
			header( 'X-Pingback: ' );
			header( 'X-Powered-By: ' );
			header( 'Set-Cookie: ' );
		}
		header( 'Content-Type: application/json' );
		die( json_encode( $json ) );
	}

	/**
	 	* prepare the array which will be used for the json response. does all the magic for the sig to work
	 	*
		* @param array $to_output the output to include
		* @param array $data the data from which to pull the secret product key
		* @return array $output the data ready for json including the md5 sig
		*/
	function prepare_output( $to_output = array(), $data = array() ) {
		$secret = ( isset( $data['secret_product_key'] ) ) ? $data['secret_product_key'] : 'null';
		$sig_array = array( 'secret' => $secret );

		foreach ( $to_output as $k => $v ) {
			if ( is_string( $k ) ) $output[$k] = $data[$v];
			else $output[$v] = $data[$v];
		}

		$sig_out = $output;
		$sig_array = array_merge( $sig_array, $sig_out );
		foreach ( $sig_array as $k => $v ) {
			if ( $v === false ) $v = 'false';
			if ( $v === true ) $v = 'true';
			$sigjoined[] = "$k=$v";
		}
		$sig = implode( '&', $sigjoined );
		if ( !$this->debug ) $sig = md5( $sig );
		$output['sig'] = $sig;
		return $output;
	}

	/**
	 	* spits out an error in json
	 	*
		* @param string $code the error code/number
		* @param string $code the debug message to include if debug mode is on
		* @return void
		*/
	function error( $code = 100, $debug_message = null, $secret = null, $addtl_data = array() ) {
		switch ($code) :
			case '101' :
				$error = array( 'error' => __( 'Invalid License Key', 'jigoshop-software' ), 'code' => '101' );
			break;
			case '102' :
				$error = array( 'error' => __( 'Software has been deactivated', 'jigoshop-software' ), 'code' => '102' );
			break;
			case '103' :
				$error = array( 'error' => __( 'Exceeded maximum number of activations', 'jigoshop-software' ), 'code' => '103' );
			break;
			case '104' :
				$error = array( 'error' => __( 'Invalid Instance ID', 'jigoshop-software' ), 'code' => '104' );
			break;
			case '105' :
				$error = array( 'error' => __( 'Purchase has been upgraded', 'jigoshop-software' ), 'code' => '105' );
			break;
			case '106' :
				$error = array( 'error' => __( 'License key for different product. Please check the product for this license key, then download and install the correct product.', 'jigoshop-software' ), 'code' => '106' );
			break;
			default :
				$error = array( 'error' => __( 'Invalid Request', 'jigoshop-software' ), 'code' => '100' );
			break;
		endswitch;
		if ( isset($this->debug) && $this->debug ) {
			if ( !isset( $debug_message ) || !$debug_message) $debug_message = __( 'No debug information available', 'jigoshop-software' );
			$error['additional info'] = $debug_message;
		}
		if ( isset( $addtl_data['secret'] ) ) {
			$secret = $addtl_data['secret'];
			unset( $addtl_data['secret'] );
		}
		foreach ( $addtl_data as $k => $v ) {
			$error[$k] = $v;
		}
		$secret = ( $secret ) ? $secret : 'null';
		$error['timestamp'] = time();
		foreach ( $error as $k => $v ) {
			if ( $v === false ) $v = 'false';
			if ( $v === true ) $v = 'true';
			$sigjoined[] = "$k=$v";
		}
		$sig = implode( '&', $sigjoined );
		$sig = 'secret=' . $secret . '&' . $sig;
		if ( !$this->debug ) $sig = md5( $sig );
		$error['sig'] = $sig;
		$json = $error;
		header( 'Cache-Control: no-store' );
		if ( function_exists( 'header_remove' ) ) {
			header_remove( 'Cache-Control' );
			header_remove( 'Pragma' );
			header_remove( 'Expires' );
			header_remove( 'Last-Modified' );
			header_remove( 'X-Pingback' );
			header_remove( 'X-Powered-By' );
			header_remove( 'Set-Cookie' );
		} else {
			header( 'Cache-Control: ' );
			header( 'Pragma: ' );
			header( 'Expires: ' );
			header( 'X-Pingback: ' );
			header( 'X-Powered-By: ' );
			header( 'Set-Cookie: ' );
		}
		header( 'Content-Type: application/json' );
		die( json_encode( $json ) );
		exit;
	}

}

$jigoshop_api = new Jigoshop_Software_Api(); // run the API