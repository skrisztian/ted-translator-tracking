<?php
# file name: otp_utils.php
# version: 1.0
# date: 19 Mar 2014
# author: Krisztian Stancz
#
# Collection of functions called
# from the project files.


# These lines alwas run upon inlcude or reqire

# Find out file location
if (preg_match('/\/archifa\//', __FILE__)) {
	# We are on the production server
	# Set ini file to:
	$otp_ini = parse_ini_file('/home/archifa/otp/conf/otp.ini', true);
} else {
	# We are somewhere else and we do not expect to separate public_html from scripts
	# Set ini file to:
	preg_match('/(^.*\/otp\/)/', __FILE__, $dir_match);
	$otp_ini = parse_ini_file($dir_match[1].'conf/otp.ini', true);
}

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
	global $script_file_name;
	$otputils_script_start_time = microtime(true);
	$otputils_script_id = uniqid();
	$otputils_prepapre = true;
	$otputils_error_count = 0;
	otputils_run_type();
	otp_msg(">>> started ". basename($script_file_name));
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
	global $script_file_name;
	
	# write script close and all other messages to msg log file
	if (isset($otputils_msg_log)) {
		otp_msg('--- finished', 'flush');
	}
		
	# write runtime data to the scripts log file
	$errors = 'errors: '. $otputils_error_count;
	$memory = otputils_memory();
	$time_taken = round(microtime(true) - $otputils_script_start_time, 3) . ' sec';
	$message = date("Y/m/d H:i:s").", $otputils_run_type, ". basename($script_file_name) .", $otputils_script_id, $time_taken, $memory, $errors";
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
	global $script_file_name;
	if (isset($argv[0])) {
		$otputils_run_type = 'cli';
		$script_file_name = $argv[0];
	}
	else {
		$otputils_run_type = 'web';
		$script_file_name = $_SERVER['SCRIPT_NAME'];
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

function otputils_set_bulk_limit() {
	global $otp_ini;
	if (isset($otp_ini['general']['bulk_limit'])) {
		return $otp_ini['general']['bulk_limit'];
	} else {
		return 100;
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

function otputils_prepare_for_sql($db, $source, &$target) {
	# $db: the mysqli DB object handle
	# $source: variable that contains the value to work with
	# $target: the variable we will put the prepared data
	# NOTE: &$target is a handle of a variable existing outside the function! 

	# Check if the variable we got exits at all. If not, create it empty
	if (!isset($source)) {
		$source = null;
	}

	# Figure out what type of data we got
	# NOTE: does not handle array, object, resource, unknown type
	switch (gettype($source)) {
		case "NULL":
			$value = 'NULL';
			break;
		case "boolean":
			# Set to 1 if true, 0 if false
			$value = $source?1:0;
			break;
		case "integer":
			$value = $source;
			break;
		case "double":
			$value = $source;
			break;
		case "string":
			# The string may be a number
			if (is_numeric($source)) {
				# Integer or float? 
				if ((int) $source == $source) {
					$value = intval($source);
				}
				elseif ((float) $source == $source) {
					$value = floatval($source);
				# It's something else, leave it as string
				} else {
					$value = $source;
				}
			} else {
				# Also for non numeric strings
				$value = $source;
			}

			# Continue with real strings only
			if (is_string($value)) {
				# Escape special chars
				$value = $db->real_escape_string($value);
				# Enclose in quotes
				$value = "'". $value ."'";
			}
			break;
		default:
			$msg = "otputils_prepare_for_sql function encountered a varialble type it cannot handle. 
					Variable value: $source, set to NULL.";
			otp_error($msg);
			$value = null;
			break;
	}

	# Handle the value to target
	$target = $value;
}

function otputils_get_dom($url) {

	$return['dom'] = false;
	$return['status'] = false;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$response_body = curl_exec($ch);
	$response_info = curl_getinfo($ch);
	$curl_error = curl_errno($ch);
	curl_close ($ch); 
		
	if ($curl_error <> 0) {
		# CURL error, stop here
		$return['status'] = "CURL $curl_error";
	} else {
		# Curl OK, check HTTP
		$http_status = intval($response_info['http_code']);
		$return['status'] = "HTTP $http_status";
		if ($http_status == 200) {
			# HTTP OK, proceed
			$response_body = mb_convert_encoding($response_body, 'html-entities', 'utf-8'); 
			$dom = new DOMDocument;
			$dom->validateOnParse = true;
			@$dom->loadHTML($response_body);
			$dom->preserveWhiteSpace = false;

			# Return DOM document
			$return['dom'] = $dom;
		}
	}
	return $return;
}

function otputils_get_subtitle_data($db, $video_id) {

	$sql_list = array();

	# First, run two API queries for the given video ID.
	# Connect to  Amara API, to get generic subtitle data
	$api_dir = "/videos/$video_id/";
	$api_return = call_amara_api($api_dir);
	if (is_string($api_return)) {
		# There was an error using the API
		# Show the HTTP or CURL error code
		otp_error("Amara API connection error ". $api_return . " table: subtitles, video_id: $video_id");
	}
	else {
		otp_msg("Connected to Amara API. HTTP 200 table: subtitles, video_id: $video_id");

		# Get data from API call, and make sure all sql values are covered
		@otputils_prepare_for_sql($db, $api_return['created'], $subs_data['created_orig']);
		@otputils_prepare_for_sql($db, $api_return['duration'], $subs_data['duration']);
		@otputils_prepare_for_sql($db, $api_return['metadata']['speaker-name'], $subs_data['speaker-name']);
		@otputils_prepare_for_sql($db, $api_return['original_language'], $subs_data['original_language_code']);
		@otputils_prepare_for_sql($db, $api_return['project'], $subs_data['project']);
		@otputils_prepare_for_sql($db, $api_return['title'], $subs_data['title_orig']);
		@otputils_prepare_for_sql($db, null, $subs_data['visible']);
		@otputils_prepare_for_sql($db, null, $subs_data['ted_link']);
		@otputils_prepare_for_sql($db, $video_id, $subs_data['video_id']);

		# Check if there is a Hungarian translation
		$version_hu = false;
		if (array_key_exists('languages', $api_return)) {
			foreach ($api_return['languages'] as $key => $translation) {
				if ($translation['code'] == 'hu') {
				 	$version_hu = true;
				 	@otputils_prepare_for_sql($db, $api_return['languages'][$key]['visible'], $subs_data['visible']);
				 	break;
				 } 
			}

		}

		# If there is a Hungaian translation, call API for subtitle data
		if ($version_hu) {
			$api_dir = "/videos/$video_id/languages/hu/";
			$api_return_hu = call_amara_api($api_dir);
			if (is_string($api_return_hu)) {
				# There was an error using the API
				# Show the HTTP or CURL error code
				otp_error("Amara API connection error ". $api_return . " table: subtitles_hu, video_id: $video_id");
			}
			else {
				otp_msg("Connected to Amara API. HTTP 200 table: subtitles_hu, video_id: $video_id");

				foreach ($api_return_hu['versions'] as $num => $data) {
					@otputils_prepare_for_sql($db, $data['author'], $subs_ver_data['author']);
					@otputils_prepare_for_sql($db, $data['published'], $subs_ver_data['published']);
					@otputils_prepare_for_sql($db, $data['text_change'], $subs_ver_data['text_change']);
					@otputils_prepare_for_sql($db, $data['time_change'], $subs_ver_data['time_change']);										
					@otputils_prepare_for_sql($db, $data['version_no'], $subs_ver_data['version_no']);

					$sql_list[] = '('. $subs_data['video_id'] .', '. $subs_ver_data['author'] .', '. $subs_ver_data['published']
							    .', '. $subs_ver_data['text_change'] .', '. $subs_ver_data['time_change']
							    .', '. $subs_ver_data['version_no'] .')';
				}			
			}
		}

		# Insert the returned items into $subs_data, or if nothing return insert NULL
		@otputils_prepare_for_sql($db, $api_return_hu['reviewer'], $subs_data['reviewer']);
		@otputils_prepare_for_sql($db, $api_return_hu['approver'], $subs_data['approver']);
		@otputils_prepare_for_sql($db, $api_return_hu['created'], $subs_data['created_hu']);
		@otputils_prepare_for_sql($db, $api_return_hu['id'], $subs_data['id']);
		@otputils_prepare_for_sql($db, $api_return_hu['is_original'], $subs_data['is_original']);
		@otputils_prepare_for_sql($db, $api_return_hu['is_translation'], $subs_data['is_translation']);
		@otputils_prepare_for_sql($db, $api_return_hu['language_code'], $subs_data['language_code']);				
		@otputils_prepare_for_sql($db, $api_return_hu['num_versions'], $subs_data['num_versions']);				
		@otputils_prepare_for_sql($db, $api_return_hu['official_signoff_count'], $subs_data['official_signoff_count']);				
		@otputils_prepare_for_sql($db, $api_return_hu['subtitle_count'], $subs_data['subtitle_count']);				
		@otputils_prepare_for_sql($db, $api_return_hu['subtitles_complete'], $subs_data['subtitles_complete']);				
		@otputils_prepare_for_sql($db, $api_return_hu['title'], $subs_data['title_hu']);												

		# If the project is tedtalk, find the video link on the TED website
		if (isset($api_return['project']) && ($api_return['project'] == 'tedtalks')) {
			# Find out the video link on TED by searching for the title 
			$ted_link = otputils_get_ted_link($db, $video_id, $api_return['title']);
			@otputils_prepare_for_sql($db, $ted_link, $subs_data['ted_link']);
		}
		
		# Generate sql statements
		$sql = 'INSERT INTO otp_subtitles (video_id, id, created_hu, is_original, is_translation, language_code, speaker_name, '
		     . 'num_versions, official_signoff_count, original_language_code, reviewer, approver, subtitle_count, subtitles_complete, '
			 . 'title_hu, title_orig, duration, project, created_orig, visible, ted_link) '
			 . 'VALUES ('
			 . $subs_data['video_id'] .', '
			 . $subs_data['id'] .', '
			 . $subs_data['created_hu'] .', '
			 . $subs_data['is_original'] .', '
			 . $subs_data['is_translation'] .', '
			 . $subs_data['language_code'] .', '
			 . $subs_data['speaker-name'] .', '
			 . $subs_data['num_versions'] .', '
			 . $subs_data['official_signoff_count'] .', '
			 . $subs_data['original_language_code'] .', '
			 . $subs_data['reviewer'] .', '
			 . $subs_data['approver'] .', '
			 . $subs_data['subtitle_count'] .', '
			 . $subs_data['subtitles_complete'] .', '
			 . $subs_data['title_hu'] .', ' 
			 . $subs_data['title_orig'] .', '
			 . $subs_data['duration'] .', '
			 . $subs_data['project'] .', '
			 . $subs_data['created_orig'] .', '
			 . $subs_data['visible'] .', '			 
			 . $subs_data['ted_link'] .') '
			 . 'ON DUPLICATE KEY UPDATE id=VALUES(id), created_hu=VALUES(created_hu), num_versions=VALUES(num_versions), '
			 . 'official_signoff_count=VALUES(official_signoff_count), reviewer=VALUES(reviewer), approver=VALUES(approver), ' 
			 . 'subtitle_count=VALUES(subtitle_count), subtitles_complete=VALUES(subtitles_complete), title_hu=VALUES(title_hu), '
			 . 'visible=VALUES(visible), ted_link=VALUES(ted_link)';

		# Update DB otp_subtitles table
		if($db->query($sql)) {
			otp_msg("Rows uploaded/updated in otp_subtitles table: ". $db->affected_rows); 
		} else {
			otp_error($db->error.' @otputils_get_subtitle_data: line '.__LINE__);
		}		

		# Update otp_subtutitle_versions if necessary
		if (count($sql_list) > 0) {
			$sql2 = 'INSERT INTO otp_subtitle_versions (video_id, author, published, text_change, time_change, version_no) ' 
			       . 'VALUES ' .implode(', ', $sql_list) .' ON DUPLICATE KEY UPDATE published=VALUES(published)';

			if  ($db->query($sql2)) {
				otp_msg("Rows uploaded/updated in otp_subtitle_versions table: ". $db->affected_rows); 
			} else {
				otp_error($db->error.' @otputils_get_subtitle_data: line '.__LINE__);
			}
		}
	}
}

function otputils_die($error_code=1) {
	global $otputils_run_type;

	if ($otputils_run_type == 'web') {
		# Redirect user to a generic error page
		echo '<script>';
		echo '<!-- location.replace("http://otp.archifabrika.hu/error.html" -->';
		echo '</script>';
	}

	# Exit from running script
	exit($error_code);
}

function otputils_log_error($db, $user, $object_id_type, $object_id, $error_code, $error_text=null) {
	@otputils_prepare_for_sql($db, $user, $error['user']);
	@otputils_prepare_for_sql($db, $object_id_type, $error['object_id_type']);	
	@otputils_prepare_for_sql($db, $object_id, $error['object_id']);
	@otputils_prepare_for_sql($db, $error_code, $error['error_code']);	
	@otputils_prepare_for_sql($db, $error_text, $error['error_text']);

	$sql = 'INSERT INTO otp_errors (user, object_id_type, object_id, error_code, error_text) '
		. 'VALUES('. $error['user'] .', '. $error['object_id_type'] .', '. $error['object_id'] .', '
		. $error['error_code'] .', '. $error['error_text'] .') ON DUPLICATE KEY UPDATE user=VALUES(user), '
        . 'object_id_type=VALUES(object_id_type), object_id=VALUES(object_id), error_code=VALUES(error_code), '
        . 'error_text=VALUES(error_text)';

	if  ($db->query($sql)) {
			otp_msg("Logged error $error_code for $object_id_type: $object_id"); 
	} else {
		otp_error($db->error.'otputils_log_error: line '.__LINE__);
	}
}

function otputils_get_ted_link($db, $video_id, $title) {

	$title = urlencode($title);
	$title = '"'.$title.'"';
	$url = 'http://www.ted.com/search?cat=talks&q=' . $title;
	$ted_link = null;

	# Download the page, extract the link
	$getpage = otputils_get_dom($url);

	if ($getpage['dom']) {
		otp_msg('Connected to TED webpage. '. $getpage['status'] .' page: search, video_id: '. $video_id);
		$xpath = new DOMXPath($getpage['dom']);
		$classname = 'visible-url-link';
		$results = $xpath->query("//*[@class='" . $classname . "']");
		$num_results = $results->length;

		if ($num_results == 0) {
			otp_error("@otputils_get_ted_link: Found $num_results matches for TED video link, video_id: $video_id");
			otputils_log_error($db, 'robot', 'video_id', $video_id, 601);
		} elseif ($num_results == 1) {
			preg_match('/^.*\/(.*)$/', $results->item(0)->nodeValue, $match);
			$ted_link = $match[1];
			otp_msg("@otputils_get_ted_link: Found 1 match for TED video link, video_id: $video_id");
		} else {
			otp_error("@otputils_get_ted_link: Found $num_results matches for TED video link, video_id: $video_id");
			otputils_log_error($db, 'robot', 'video_id', $video_id, 602, $num_results . ' links found');
		}
	} else {
		otp_error('Failed to connect to TED webpage. '. $getpage['status'] .' page: search, video_id: '. $video_id);
		otputils_log_error($db, 'robot', 'video_id', $video_id, 601, 'Could not connect to TED search page');
	}
	return $ted_link;
}

function otputils_open_db() {
	$p = otputils_db_params();
	$db = new mysqli($p[0], $p[1], $p[2], $p[3]);

	if($db->connect_errno > 0){
		otp_error($db->connect_error.' @otputils_open_db line '.__LINE__);
		otputils_die();
	} else {
	$db->query("set names 'utf8'");
	return $db;
	}
}

?>