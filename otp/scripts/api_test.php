<?php

// This is a test script to verify if 
// the Amara API is working as expected

// Find out file location
if (preg_match('/\/archifa\//', __FILE__)) {
	// We are on the production server
	// Set ini file to:
	$otp_ini = parse_ini_file('/home/archifa/otp/conf/otp.ini', true);
} else {
	// We are somewhere else and we do not expect to separate public_html from scripts
	// Set ini file to:
	preg_match('/(^.*\/otp\/)/', __FILE__, $dir_match);
	$otp_ini = parse_ini_file($dir_match[1].'conf/otp.ini', true);
}

// Print the HTML document header
echo '<html>';
echo '<head>';
echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
echo "<title>Testing the Amara API</title>";
echo '</head>';

// Start the HTML document body
echo '<body>';


// Perform an Amara API call
$baseurl = "https://www.amara.org/api2/partners/";
if (isset($otp_ini['amara-api'])) {
	foreach ($otp_ini['amara-api'] as $num => $header) {
		$headers[] = $header;
	}
}
$api_dir = "languages";
$query_params = array();

if (count($query_params) != 0) {
	$params = "?" . http_build_query($query_params);
}
else {
	$params = "";
}
	
$api_dir = preg_replace('/^\/*/', '', $api_dir); 
$api_dir = preg_replace('/\/*$/', '/', $api_dir, 1); 
$url = $baseurl . $api_dir . $params;	

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER,  $headers);
$response_body = curl_exec($ch);
$response_info = curl_getinfo($ch);
$curl_error = curl_errno($ch);
curl_close ($ch); 

// print call data
echo '<h2>API Call params</h2>';
echo '<p>';
echo "URL: $url</br>";
echo "headers:</br>";
foreach ($headers as $key => $value) {
	echo hide($value), "<br>";
}
echo '</p>';

// print call results
echo '<h2>API call results</h2>';
echo '<p>';
echo "CURL ERROR: $curl_error<br>";
echo "reponse info: <br>";
echo '<pre>';
var_dump($response_info);
echo '</pre>';
echo "reponse body: <br>";
echo '<pre>';
var_dump($response_body);
echo '</pre>';
echo '</p>';



// traceroute to Amara
echo "<h2>Traceroute to Amara</h2>";
$target = "amara.org";
$output = shell_exec("traceroute $target");
echo '<p>';
echo "<pre>$output</pre>";
echo '</p>';

// traceroute to google
echo "<h2>Traceroute to Google</h2>";
$target = "google.com";
$output = shell_exec("traceroute $target");
echo '<p>';
echo "<pre>$output</pre>";
echo '</p>';

// perform an html call to amara.org
$url = "http://amara.org/en/";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response_body = curl_exec($ch);
$response_info = curl_getinfo($ch);
$curl_error = curl_errno($ch);
curl_close ($ch); 

// print call data
echo '<h2>Amara HTTP call params</h2>';
echo '<p>';
echo "URL: $url</br>";
echo '</p>';

// print call results
echo '<h2>Amara HTTP call results</h2>';
echo '<p>';
echo "CURL ERROR: $curl_error<br>";
echo "reponse info: <br>";
echo '<pre>';
var_dump($response_info);
echo '</pre>';
echo "reponse body: <br>";
echo '<pre><code>';
var_dump($response_body);
echo '</code></pre>';
echo '</p>';

// perform an html call to google.com
$url = "http://google.com";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response_body = curl_exec($ch);
$response_info = curl_getinfo($ch);
$curl_error = curl_errno($ch);
curl_close ($ch); 

// print call data
echo '<h2>Google HTTP call params</h2>';
echo '<p>';
echo "URL: $url</br>";
echo '</p>';

// print call results
echo '<h2>Google HTTP call results</h2>';
echo '<p>';
echo "CURL ERROR: $curl_error<br>";
echo "reponse info: <br>";
echo '<pre>';
var_dump($response_info);
echo '</pre>';
echo "reponse body: <br>";
echo '<pre><code>';
var_dump($response_body);
echo '</code></pre>';
echo '</p>';

// perform an html call to ted.com
$url = "http://ted.com";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response_body = curl_exec($ch);
$response_info = curl_getinfo($ch);
$curl_error = curl_errno($ch);
curl_close ($ch); 

// print call data
echo '<h2>TED HTTP call params</h2>';
echo '<p>';
echo "URL: $url</br>";
echo '</p>';

// print call results
echo '<h2>TED HTTP call results</h2>';
echo '<p>';
echo "CURL ERROR: $curl_error<br>";
echo "reponse info: <br>";
echo '<pre>';
var_dump($response_info);
echo '</pre>';
echo "reponse body: <br>";
echo '<pre><code>';
var_dump($response_body);
echo '</code></pre>';
echo '</p>';



// close page
echo '</body>';
echo '</html>';

/********** FUCTIONS ***********/
// Hide login info during printing
function hide($string) {
	$new_string = substr_replace($string, "*****", 17);
	return $new_string;
}

?>