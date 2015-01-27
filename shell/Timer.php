#!/opt/local/bin/php

<?php
/*
 * Define parameters below
 */
$parameters = "";
$HOST = "115.28.40.165";
$URL = "/timer";

/* 
 * Description: Function used to check if the given functions are available with the given php version
 * Parameters: $functionList - comma separated function names
 */ 
function _checkBasicFunctions($functionList) {
	$functions = split(",", $functionList);
	foreach ($functions as $key => $val) {
		$function = trim($val);
		if (!function_exists($function)) {
			return false;
		}
	}
	return true;
}

/*
 * Description: This Function is used to POST values
 * Parameters: $URL - Requested script url
 * 	$HOST - server host
 */
function doHTTPCall($URL, $HOST) {
	if (_checkBasicFunctions("curl_init,curl_setopt,curl_exec,curl_close")) {
		$ch = curl_init("http://" . $HOST . $URL);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		curl_close($ch);
	} else if (_checkBasicFunctions("fsockopen,fputs,feof,fread,fgets,fclose")) {
		$fsock = fsockopen($HOST, 80, $errno, $errstr, 30);
		if (!$fsock) {
			echo "Error! $errno - $errstr";
		} else {
			$headers .= "POST $URL HTTP/1.1\r\n";
			$headers .= "HOST: $HOST\r\n";
			$headers .= "Connection: close\r\n\r\n";
			fputs($fsock, $headers);
			// Needed to omit extra initial information
			$get_info = false;
			while (!feof($fsock)) {
				if ($get_info) {
					$response .= fread($fsock, 1024);
				} else {
					if (fgets($fsock, 1024) == "\r\n") {
						$get_info = true;
					}
				}
			}
			fclose($fsock);
		}
	}
	echo $response;
}

//doHTTPCall($URL, $HOST);
echo 123;
?>
