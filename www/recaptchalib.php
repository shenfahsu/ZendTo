<?php 

/* Google Recaptcha version 2 */

function recaptcha_get_html ($pubkey)
{
        if ($pubkey == null || $pubkey == '') {
                die ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin'>https://www.google.com/recaptcha/admin</a>");
        }

        return '<div class="g-recaptcha" data-sitekey="' . $pubkey . '"></div>';
}


/**
 * @return boolean
 */
function recaptcha_check_answer($secret, $remoteip, $response) {
	$result = false;
	if ($response) {
		$fields = array(
			'secret' => urlencode($secret),
			'response' => urlencode($response),
			'remoteip' => urlencode($remoteip)
		);
		$resp = doHttpsPostReturnJSONArray('www.google.com', "/recaptcha/api/siteverify", $fields);
		if ($resp) {
			$result = $resp->success;
		}
	}
	return $result;
}

/**
 * Do a HTTPS POST, return some JSON decoded as array
 * @param $host hostname
 * @param $path path
 * @param $fields associative array of fields
 * return JSON decoded data structure or empty data structure
 */
function doHttpsPostReturnJSONArray($hostname, $path, $fields) {
	$result = doHttpsPost($hostname, $path, $fields);

	if ($result) {
		$result = doJSONArrayDecode($result);
	} else {
		// Failed, returned nothing
		$result = array();
	}

	return $result;
}

// HTTPS post
function doHttpsPost($hostname, $path, $fields) {
	$result = "";
	// URLencode the post string
	$fields_string = "";
	foreach($fields as $key=>$value) {
		if (is_array($value)) {
			if ( ! empty($value)) {
				foreach ($value as $k => $v) {
					$fields_string .= $key . '['. $k .']=' . $v . '&';
				}
			} else {
				$fields_string .= $key . '=&';
			}
		} else {
			$fields_string .= $key.'='.$value.'&';
		}
	}
	rtrim($fields_string,'&');

	// Use cURL?
	if (__use_curl())
	{
		// Build the cURL url.
		$curl_url = "https://" . $hostname . $path;

		// Initialize cURL session.
		if ($ch = curl_init($curl_url))
		{
			// Set the cURL options.
			curl_setopt($ch, CURLOPT_POST, count($fields));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			// Execute the cURL request.
			$result = curl_exec($ch);

			// Close the curl session.
			curl_close($ch);
		}
	}
	else
	{
		// Build a header
		$http_request  = "POST $path HTTP/1.1\r\n";
		$http_request .= "Host: $hostname\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
		$http_request .= "Content-Length: " . strlen($fields_string) . "\r\n";
		$http_request .= "Connection: Close\r\n";
		$http_request .= "\r\n";
		$http_request .= $fields_string ."\r\n";

		$result = '';
		$errno = $errstr = "";
		$fs = fsockopen("ssl://" . $hostname, 443, $errno, $errstr, 10);
		if( false == $fs ) {
			// fsockopen failed
		} else {
			fwrite($fs, $http_request);
			while (!feof($fs)) {
				$result .= fgets($fs, 4096);
			}

			$result = explode("\r\n\r\n", $result, 2);
			$result = $result[1];
		}
	}

	// Return the result.
	return $result;
}

// Internal function: does a JSON decode of the string
function doJSONArrayDecode($string) {
	$result = array();

	if (function_exists("json_decode")) {
		try {
			$result = json_decode( $string);
		} catch (Exception $e) {
			$result = null;
		}
	} elseif (file_Exists("json.php")) {
		require_once('json.php');
		$json = new Services_JSON();
		$result = $json->decode($string);

		if (!is_array($result)) {
			$result = array();
		}
	}

	return $result;
}

function __use_curl()
{
	if (FALSE === $recaptcha_use_curl)
	{
		return FALSE;
	}
	elseif (function_exists('curl_init') and function_exists('curl_exec'))
	{
		return TRUE;
	}
	return FALSE;
}

?>
