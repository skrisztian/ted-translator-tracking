<?php
# file name: otp_utils.php
# version: 1.0
# date: 12 Feb 2014
# author: Krisztian Stancz
#
# Collection of functions called
# from the project files.

#
# Prepare script maintenance jobs
#
function otputils_prepare() {
	global $otputils_script_start_time;
	global $otputils_prepapre;
	global $otputils_script_id;
	global $otputils_error_count;
	global $otp_ini;
	global $otp_msg;
	$otputils_script_start_time = microtime(true);
	$otputils_script_id = uniqid();
	$otputils_prepapre = true;
	$otputils_error_count = 0;
	$otp_ini = parse_ini_file("c:/wamp/www/otp/config/otp.ini", true);
	otputils_run_type();
	otp_msg(">>> started " . $_SERVER['SCRIPT_FILENAME']);
}

#
# Sets the name of the log file based on otp.ini, for script messages
#
function otputils_set_msg_log($log_name="general") {
	global $otputils_msg_log;
	global $otp_ini;
	if (isset($otp_ini)){
		$log_dir = $otp_ini['dir']['log_dir'];
		$log_file = $otp_ini['log'][$log_name];
		$otputils_msg_log = $log_dir . $log_file;
		
	}
	else {
		unset($otputils_msg_log);
	}
}

#
# Finish and clean-up script maintenance jobs
#
function otputils_cleanup() {
	global $otputils_script_start_time;
	global $otputils_prepapre;
	global $otputils_script_id;
	global $otputils_error_count;
	global $otp_ini;
	global $otputils_msg_log;
	global $otputils_run_type;
	
	# write script close and all other messages to msg log file
	if (isset($otputils_msg_log)) {
		otp_msg('--- finished', 'flush');
	}
		
	# write runtime data to the scripts log file
	$errors = 'errors: '. $otputils_error_count;
	$memory = otputils_memory();
	$time_taken = round(microtime(true) - $otputils_script_start_time, 3) . ' sec';
	$message = date("Y/m/d H:i:s").", $otputils_run_type, ". $_SERVER['SCRIPT_FILENAME'] .", $otputils_script_id, $time_taken, $memory, $errors";
	otputils_msg_to_log($message, 'script');
}

#
# Returns the peak memory utilization 
#
function otputils_memory() {
	$memory = memory_get_peak_usage(true) / 1024;
		if ($memory >= 1024) {
			return $memory / 1024 . ' MB';
		}
		else {
			return $memory . ' kB';
		}
}

#
# Returns the run type of the current script (web or cli)
# 
function otputils_run_type() {
	global $otputils_run_type;
	global $argv;
	if (isset($argv[0])) {
		$otputils_run_type = 'cli';
	}
	else {
		$otputils_run_type = 'web';
	}
}
			
# 
# Counts error messages
#
function otputils_increase_error_count() {
	global $otputils_error_count;
	if (isset($otputils_error_count)) {
		$otputils_error_count++;
	}
	else {
		$otputils_error_count = 1;
	}
}
#
# Format message string
#
function otp_msg($message, $command='') {
	global $otp_msg;
	global $otputils_script_id;
	global $otputils_msg_log_cont;
	$message = date("Y/m/d H:i:s") . ' ' . $message;
	if (isset($otputils_script_id)) {
		$message = $otputils_script_id . ' ' . $message;
	}
	$otp_msg[] = $message;
	
	# If we want on-line logging rather than batch at the end
	# of the script execution, call write to log immediately
	if (isset($otputils_msg_log_cont)) {
		otputils_msg_to_log($otp_msg);
	}
	# flush content when needed
	elseif ($command == 'flush') {
		otputils_msg_to_log($otp_msg);
	}
}

#
# Write to log file
#
function otputils_msg_to_log(&$message, $level='msg') {
	global $otputils_msg_log;
	global $otp_ini;
	# define the log file type
	if ($level == 'msg') {
		if (isset($otputils_msg_log)) {
			$logfile = $otputils_msg_log;
		}
	}
	elseif ($level == 'script') {
		if (isset($otp_ini['log']['scripts'])) {
			$logfile = $otp_ini['dir']['log_dir'] . $otp_ini['log']['scripts'];
		}
	}
	
	# write to the selected log file
	if (isset($logfile)) {
		if ($logfp = fopen($logfile, 'a')) {
			# write message content to file, then
			# delete msg medium content, so we do
			# not duplicate it in the log at next write
			if (is_array($message)) {
				foreach ($message as $num => $line) {
					fwrite($logfp, $line . PHP_EOL);
				}
				$message = array();
			}
			else {
				fwrite($logfp, $message . PHP_EOL);
				$message = null;
			}
			fclose($logfp);
		}
		else {
			otputils_increase_error_count();
		}
	}
}

#
# Amara API caller
#
# queries the amara API
# returns the json object converted to array
# or in case of connection error 
# returns the http error code as integer
# expects:
#    $api_dir - as string
#    $query_params - as array
function call_amara_api($api_dir, $query_params=array()) {
	global $otp_ini;
	$baseurl = "https://www.amara.org/api2/partners/";
	if (isset($otp_ini['amara-api'])) {
		foreach ($otp_ini['amara-api'] as $num => $header) {
			$headers[] = $header;
		}
	}
	
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
	
	if($curl_error == 0) {
		if (intval($response_info['http_code']) == 200) {
			# success, return the json converted to array
			return json_decode($response_body, true);
		}
		else {
			# there was a connection error on the way
			# return the http error code as string
			return "HTTP ". $response_info['http_code'];
		}
	}
	else {
		# curl failed, we return the error number
		# as string
		return "CURL ". $curl_error;
	}
}

#
# Checks the HTTP return code for a given url
#
function check_http_return_code($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	$response_body = curl_exec($ch);
	$response_info = curl_getinfo($ch);
	$curl_error = curl_errno($ch);
	curl_close ($ch); 

	if($curl_error == 0) {
		return intval($response_info['http_code']);
	}
	else { 
		return $curl_error;
	}
}

function cleanchars($string) {
	$chars = array(
		"á"=>"a","é"=>"e","í"=>"i",
		"ü"=>"u","ű"=>"u","ú"=>"u",
		"ő"=>"o","ö"=>"o","ó"=>"o",
		"Á"=>"A","É"=>"E","Í"=>"I",
		"Ü"=>"U","Ű"=>"U","Ú"=>"U",
		"Ő"=>"O","Ö"=>"O","Ó"=>"O",);
	return str_replace(array_keys($chars), $chars, $string);
}

#
# Returns parameters for db connection
#
function otputils_db_params() {
	global $otp_ini;
	if (isset($otp_ini['db'])) {
		return array($otp_ini['db']['host'], $otp_ini['db']['user'], $otp_ini['db']['password'], $otp_ini['db']['db']);
	}
}

#
# Sets message logging to continuous
#
function otputils_set_msg_log_continuous() {
	global $otputils_msg_log_cont;
	$otputils_msg_log_cont = true;
}

#
# Logs error to message stream. Calls msg function, prepends msg with "Error",
# and increases the message counter.
function otp_error($message, $command='') {
	$message = 'Error: ' . $message;
	otp_msg($message, $command);
	otputils_increase_error_count();
}
?>