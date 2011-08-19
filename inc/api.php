<?php
/*
	TODO more inline documentation
*/

class jigoshop_software_api extends jigoshop_software {
	
	public $debug;
	
	function __construct($debug = false) {
		
		$this->debug = $debug;
		if (isset($_GET['request'])) { 

			$request = $_GET['request'];

			switch ($request) :
				case 'trial' :
					if (!isset($_GET['productid'])) $this->error('100', 'No product ID given');
					
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
	
	function error($code = 100, $debug_message = null) {
		switch ($code) :
			case '101' :
				$error = array('101', 'Invalid LicenseKkey');
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

new jigoshop_software_api($debug = true);