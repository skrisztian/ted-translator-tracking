<?php
# file name: pageutils.php
# version: 1.0
# date: 23 Mar 2014
# author: Krisztian Stancz
#
# Collection of functions called
# from the project web pages.


# These lines alwas run upon inlcude or reqire

# Find out file location
if (preg_match('/\/archifa\//', __FILE__)) {
	# We are on archifabrika.hu
	# Set ini file to:
	$otp_ini = parse_ini_file('/home/archifa/otp/conf/otp.ini', true);
} else {
	# We are somewhere else and we do not expect for a separate public_html directory
	# Set ini file to:
	preg_match('/(^.*\/otp\/)/', __FILE__, $dir_match);
	$otp_ini = parse_ini_file($dir_match[1].'conf/otp.ini', true);
}

# Set global script values
$pageutils_script_start_time = microtime(true);
$pageutils_script_id = uniqid();
$pageutils_page_name = basename($_SERVER['SCRIPT_NAME']);
$pageutils_must_authenticate = true;

if (isset($otp_ini['security']['state']) && ($otp_ini['security']['state']) == 'disable') {
	# echo '<font color="red"><b>Warning!</b>Authentication is disabled on this server!</font>';
	echo '<div id="site_auth_alert" style="width:100%;margin:0; padding:0; background-color:red; border:0; color:white; top:0; left:0;">';
	echo '<b>Warning!</b> Authentication is disabled on this server!</div>';
	$pageutils_must_authenticate = false; 
}

#
# General log function
#
function pageutils_log($log_name, $message, $site=null) {
	global $otp_ini;
	global $pageutils_script_id;
	global $pageutils_page_name;

	if ($site) {
		$pagename = $site;
	} else {
		$pagename = $pageutils_page_name;
	}

	if (isset($otp_ini) && isset($otp_ini['dir']['log_dir']) && isset($otp_ini['log'][$log_name])) {
		$log_dir = $otp_ini['dir']['log_dir'];
		$log_file = $otp_ini['log'][$log_name];
		$message = date("Y/m/d H:i:s") .' '. $pageutils_script_id .' '. $pagename .' '. $message . PHP_EOL;
		if ($logfp = fopen($log_dir . $log_file, 'a')) {
			fwrite($logfp, $message);
			fclose($logfp);
		}
	}
}

#
# Closing page and logging access data
#
function pageutils_cleanup() {
	global $pageutils_script_start_time;
	if (isset($_SESSION['otpweb_auth_username'])) {
		$user = $_SESSION['otpweb_auth_username'];	
	} else {
		$user ='';
	}
	$memory = pageutils_memory();
	$time_taken = round(microtime(true) - $pageutils_script_start_time, 3) . ' sec';
	$message = "$user $time_taken $memory";
	pageutils_log('page_access', $message, basename($_SERVER['REQUEST_URI']));
}

#
# Returns the peak memory utilization 
#
function pageutils_memory() {
	$memory = memory_get_peak_usage(true) / 1024;
		if ($memory >= 1024) {
			$memory = $memory / 1024 . ' MB';
		} else {
			$memory = $memory . ' kB';
		}
	return $memory;
}

#
# Checks if a logging in Facebook user is allowed to let through
#
function pageutils_verify_fb_user($username) {
	$db = pageutils_open_db();
	pageutils_prepare_for_sql($db, $username, $username_sql);
	$sql = "SELECT username, first_name, is_admin FROM otp_users WHERE username=$username_sql AND is_fb_user=1";
	if ($result = $db->query($sql)) {
		# we expect only one value
		$auth = $result->fetch_row();
		$result->free();
	} else {
		pageutils_log('page_error', '@pageutils_allowed_fb_user: '. $db->error .' line '.__LINE__);
	}
	$db->close();
	pageutils_log('site_auth', $username .' '. ($auth?"allowed":"denied"));
	return $auth;
}

#
# Updates user data in DB based on Facebook profile values
#
function pageutils_update_fb_user($user_profile) {
	$db = pageutils_open_db();
	pageutils_prepare_for_sql($db, $user_profile['username'], $username);
	pageutils_prepare_for_sql($db, $user_profile['name'], $name);
	pageutils_prepare_for_sql($db, $user_profile['first_name'], $first_name);
	pageutils_prepare_for_sql($db, $user_profile['last_name'], $last_name);
	pageutils_prepare_for_sql($db, $user_profile['email'], $email);
	pageutils_prepare_for_sql($db, $user_profile['id'], $fb_id);

	$sql = "UPDATE otp_users SET name=$name, first_name=$first_name, "
	      ."last_name=$last_name, email=$email, fb_id=$fb_id WHERE username=$username AND is_fb_user=1";
	if (!($result = $db->query($sql))) {
		pageutils_log('page_error', '@pageutils_update_fb_user: '. $db->error .' line '.__LINE__);
	}
	$db->close();
}

function pageutils_open_db() {
	global $otp_ini;
	if (isset($otp_ini['db'])) {
		$db = new mysqli($otp_ini['db']['host'], $otp_ini['db']['user'], $otp_ini['db']['password'], $otp_ini['db']['db']);
		if($db->connect_errno > 0){
			pageutils_log('page_error', '@pageutils_open_db: '. $db->error .' line '.__LINE__);
			pageutils_die();
		} else {
			$db->query("set names 'utf8'");
			return $db;
		}
	}
}

function pageutils_record_link($type, $id) {
	$link = '<a href="view_record.php?type='. $type .'&id='. urlencode($id) .'" class="raw_data_link" title="View raw record data for this '. $type .'">'. $id .'</a>';
	return $link;
}


function pageutils_view_link($type, $id, $record_page=null, $text=null) {
	if ($record_page) {
		$link = '<a href="view_record.php?type='. $type .'&id='. urlencode($id) .'" class="raw_data_link" title="View raw record data for this '. $type .'">'. $id .'</a>';
	} else {
		if ($text) {
			$link = '<a href="view_'. $type .'.php?id='. urlencode($id) .'" class="view_link" title="View details for this '. $type .'">'. $text .'</a>';
		} else {
			$link = '<a href="view_'. $type .'.php?id='. urlencode($id) .'" class="view_link" title="View details for this '. $type .'">'. $id .'</a>';
		}
	}
	return $link;
}


function pageutils_name_link($db, $amara_id) {
	pageutils_prepare_for_sql($db, $amara_id, $amara_id_sql);
	$sql = "SELECT full_name FROM otp_translators WHERE amara_id=$amara_id_sql";
	if ($result = $db->query($sql)) {
		# we expect only one value
		$full_name = $result->fetch_row()[0];
		$result->free();
	} else {
		pageutils_log('page_error', '@pageutils_get_name: '. $db->error .' line '.__LINE__);
	}
	$link = '<a href="view_translator.php?id='. urlencode($amara_id) .'" title="View statistics about this translator">'. $full_name .'</a>';
	return $link;
}

function pageutils_ted_name_link($db, $ted_id) {
	pageutils_prepare_for_sql($db, $ted_id, $ted_id_sql);
	# First let's try in the otp_translators table
	$sql = "SELECT amara_id, full_name FROM otp_translators WHERE ted_id=$ted_id_sql";
	if ($result = $db->query($sql)) {
		# we expect only one value
		$row = $result->fetch_row();
		$result->free();
	} else {
		pageutils_log('page_error', '@pageutils_get_name: '. $db->error .' line '.__LINE__);
	}

	if ($row) {
		# we have found it
		$link = '<a href="view_translator.php?id='. urlencode($row[0]) .'" title="View statistics about this translator">'. $row[1] .'</a>';
	} else {
		# let's check in ted_translators 
		$sql = "SELECT ted_full_name FROM ted_translators WHERE ted_id=$ted_id_sql";
		if ($result = $db->query($sql)) {
			# we expect only one value
			$row = $result->fetch_row();
			$result->free();
		} else {
			pageutils_log('page_error', '@pageutils_get_name: '. $db->error .' line '.__LINE__);
		}
		if ($row) {
			# we've found it
			$link = '<a href="http://www.ted.com/profiles/'. $ted_id .'/translator" target="_blank" title="Open translator profile on TED.com" class="foreign_link">'. $row[0] .'</a>';
		} else {
			# we didn't find in any table, just give back the TED ID with the link
			$link = '<a href="http://www.ted.com/profiles/'. $ted_id .'/translator" target="_blank" title="Open translator profile on TED.com" class="foreign_link">'. $ted_id .'</a>';
		}
	}
	return $link;
}


function pageutils_die($error_code=1) {
	# Redirect user to a generic error page

	$error_page = $_SERVER["HTTP_HOST"].dirname($_SERVER['PHP_SELF']).'/error.php';
	$error_page = preg_replace('/\/+/', '/', $error_page);
	echo '<script type="text/javascript">'. PHP_EOL;
	echo 'window.location.replace("http://'.$error_page. '")' .PHP_EOL;
	echo '</script>' .PHP_EOL;
	# Exit from running script
	exit($error_code);
}

function pageutils_clean_die($error_code=1) {
	pageutils_cleanup();
	pageutils_die($error_code);
}

function pageutils_time_diff($created, $now=null) {
	if (empty($now)) {
		$now = date("Y-m-d H:i:s");
	}

	$created = strtotime($created);
	$now = strtotime($now);
	$time_diff = $now - $created;
 
	if ($time_diff < 60) {
	    $time_diff_value = $time_diff;
	    $time_diff_unit = 'second';
	} elseif (($time_diff >= 60) && ($time_diff < 3600)) {
	    $time_diff_value = round($time_diff / 60);
	    $time_diff_unit = 'minute';
	} elseif (($time_diff >= 3600) && ($time_diff < 86400)) {
	    $time_diff_value = round($time_diff / 3600);
	    $time_diff_unit = 'hour';
	} elseif (($time_diff >= 86400) && ($time_diff < 86400*7)) {
	    $time_diff_value = round($time_diff / 86400);
	    $time_diff_unit = 'day';
	} elseif (($time_diff >= 86400*7) && ($time_diff < 86400*30)){
	    $time_diff_value = round($time_diff / (86400*7));
	    $time_diff_unit = 'week';
	} elseif (($time_diff >= 86400*30) && ($time_diff < 86400*365)){
	    $time_diff_value = round($time_diff / (86400*30));
	    $time_diff_unit = 'month';
	} else {
	    $time_diff_value = round($time_diff / (86400*365));
	    $time_diff_unit = 'year';		
	}

	if ($time_diff_value > 1) {
		$time_diff_unit .= 's';
	}

	return $time_diff_value .' '.$time_diff_unit;
}


function pageutils_prepare_for_sql($db, $source, &$target) {
	# $db: the mysqli DB object handle
	# $source: variable that contins the value to work with
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
			$msg = "@pageutils_prepare_for_sql encountered a varialble type it cannot handle. "
			      ."Variable value: $value Function halted, script is still runing.";
			pageutils_log('page_error', $msg);
			return;
			break;
	}

	# Handle the value to target
	$target = $value;
}

function pageutils_check_empty($value_in, $message=null) {
	@$value = $value_in; 
	if (is_null($message)) {
		$message = '<i>not set</i>';
	}
	if (!isset($value) || is_null($value) || $value == '') {
		return $message;
	} else {
		return $value;
	}
}

function pageutils_check_bool($value_in) {
	@$value = $value_in; 
	if (!isset($value) || is_null($value) || $value == '') {
		$message = '<i>not set</i>';
		return $message;
	} elseif ($value == 0) {
		return 'no';
	} elseif ($value == 1) {
		return 'yes';
	} else {
		return $value;
	}
}

function pageutils_amara_api($api_dir, $query_params=array()) {
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


session_start();

//********************** Logout code *****************************//

if (isset($_GET['logout'])) {
	
	# Destroy session values
	session_destroy();

	pageutils_log('site_auth', $_SESSION['otpweb_auth_username'] .' logout', $pageutils_page_name);
	pageutils_cleanup();

	# Reload the page, wit
	header('Location: http://'. $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));
	exit;
}

//*********************** Authentication code *************************//
# This script checks if the user is authenticated to see the page.
# If yes, it lets the user through and provides a log out link.
# If not, it serves the login page instead.

if ($pageutils_must_authenticate) {
	if (!isset($_SESSION['otpweb_auth_username']) || ($_SESSION['otpweb_auth_username']) == false) {

		# User is not logged into OTP Webpage
		# Try Facebook creditentials

		// DEBUG
		// echo 'Auth: step 1 - user is NOT authenticated locally<br>';

		require 'src/facebook.php';
		
		# Create FB Application instance 
		# FIXME replace with otp.ini

		$facebook = new Facebook($otp_ini['facebook-api']);

		# Get User ID
		$user = $facebook->getUser();

		// We may or may not have this data based on whether the user is logged in.
		//
		// If we have a $user id here, it means we know the user is logged into
		// Facebook, but we don't know if the access token is valid. An access
		// token is invalid if the user logged out of Facebook.

		if ($user) {
		  try {
		    // Proceed knowing you have a logged in user who's authenticated.
		    $user_profile = $facebook->api('/me');
		  } catch (FacebookApiException $e) {
		    pageutils_log('page_error', $e);
		    $user = null;
		  }
		}

		if ($user) {
			# Now we have a user who is logged in to Facebook
			# Check if he is allowed to use OTP Web
			$otp_user = pageutils_verify_fb_user($user_profile['username']);
			if ($otp_user) {
				// DEBUG
				// echo 'Auth: step 2 - user authenticated through Facebook and is allowed in DB<br>';

				# Update user data
				pageutils_update_fb_user($user_profile);
				# Create local session for user
				$_SESSION['otpweb_auth_username'] = $user_profile['username'];
				$_SESSION['otpweb_auth_firstname'] = $user_profile['first_name'];
				$_SESSION['otpweb_auth_level'] = $otp_user[2];
				# We don't need the Facebook session anymore
				$facebook->destroySession();
			} else {
				// DEBUG
				// echo 'Auth: step 3 - user authenticated through Facebook but is NOT allowed in DB<br>';
				# The user is authenticated through Facebook, but not allowed to use
				# OTP Web. Show him the login page with an auth_error message.
				$facebook->destroySession();
				session_destroy();
				pageutils_cleanup();
				$auth_alert = 'Sorry, only language coordinators are allowed to log in.';
				include 'login.php';
				exit;
			}
		} else {
			// DEBUG
			// echo 'Auth: step 4 - user NOT authenticated through Facebook yet<br>';

			# The user is not logged into Facebook
			# Serve the OTP Web login page, with the Facebook login link
			# We request email as extra permission
			$loginUrl = $facebook->getLoginUrl(array('scope' => 'email'));
			# Do not show the original page to unauthorized user
			# session_destroy();
			pageutils_cleanup();
			include 'login.php';
			exit;
		}
	} 
}
// DEBUG
// echo 'Auth: step 5 - user locally authenticated<br>';

# By this point OTP Web authentication has been set already.
# Continue to the requested page

# We also skip here if pageutils_must_authenticate is set to false.


?>