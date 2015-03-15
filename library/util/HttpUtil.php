<?php

class HttpUtil {
	
	/*
	 * Description: Function used to check if the given functions are available with the given php version Parameters: $functionList - comma separated function names
	 */
	public function _checkBasicFunctions($functionList) {
		$functions = split(",", $functionList);
		foreach ($functions as $key => $val) {
			$function = trim($val);
			if (!function_exists($function)) {
				return false;
			}
		}
		return true;
	} // end _checkBasicFunctions
	function getMultipleDocuments($nodes, $referer) {
		if (!$referer) {
			$referer = $nodes[0];
		}
		$node_count = count($nodes);
		$curl_arr = array ();
		$master = curl_multi_init();
		for ($i = 0; $i < $node_count; $i++) {
			$curl_arr[$i] = curl_init($nodes[$i]);
			curl_setopt($curl_arr[$i], CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl_arr[$i], CURLOPT_FRESH_CONNECT, true);
			curl_setopt($curl_arr[$i], CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt($curl_arr[$i], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_arr[$i], CURLOPT_REFERER, $referer);
			curl_setopt($curl_arr[$i], CURLOPT_TIMEOUT, 1);
			curl_multi_add_handle($master, $curl_arr[$i]);
		}
		do {
			curl_multi_exec($master, $running);
		} while ($running > 0);
		curl_multi_close($master);
	}
	
	/*
	 * Description: This Function is used to POST values Parameters: $URL - Requested script url $HOST - server host
	 */
	public function doHTTPCall($URL, $HOST) {
		$this->getMultipleDocuments(array (
			"http://" . $HOST . $URL 
		));
	}
	
	// do Http Post
	public function doHTTPPOST($URL, $fields) {
		$ch = curl_init($URL);
		curl_setopt($ch, CURLOPT_REFERER, $URL);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		$headers = curl_getinfo($ch);
		curl_close($ch);
		return $headers;
	}

}