<?php
# file name: update_db_translator_no_hun.php
# version: 1.0
# date: 16 Mar 2014
# author: Krisztian Stancz
#
# Finds amara_ids which did not show up
# while looking for translators with Hungarian as set language
# Should be run as a cron job.

include "otputils.php";
include "htmlutils.php";

otputils_prepare();
otputils_set_msg_log("data_update");
otputils_set_msg_log_continuous();

$amara_users = array();
$sql_list = array();
$language_table = array();
$left_ted_list = array();
$p = otputils_db_params();

# Get list of amara_ids from various tables
$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
if($db->connect_errno > 0) {
	otp_error($db->connect_error.' line '.__LINE__);
} else {
	$db->query("set names 'utf8'");

	$sql = 'SELECT user FROM otp_activity ORDER BY user';
	if($result = $db->query($sql)) {
		while($row = $result->fetch_assoc()) {
			$amara_users[] = $row['user'];
		}
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}

	$sql = 'SELECT assignee FROM otp_tasks ORDER BY assignee';
	if($result = $db->query($sql)) {
		while($row = $result->fetch_assoc()) {
			if (!in_array($row['assignee'], $amara_users)) {
				$amara_users[] = $row['assignee'];	
			}
		}
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}

	$sql = 'SELECT reviewer, approver FROM otp_subtitles ORDER BY reviewer';
	if($result = $db->query($sql)) {
		while($row = $result->fetch_assoc()) {
			if (!in_array($row['reviewer'], $amara_users)) {
				$amara_users[] = $row['reviewer'];	
			}
			if (!in_array($row['approver'], $amara_users)) {
				$amara_users[] = $row['approver'];	
			}	
		}
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}

	$sql = 'SELECT author FROM otp_subtitle_versions ORDER BY author';
	if($result = $db->query($sql)) {
		while($row = $result->fetch_assoc()) {
			if (!in_array($row['author'], $amara_users)) {
				$amara_users[] = $row['author'];	
			}
		}
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}

	# Get the list where we have languages
	$sql = 'SELECT amara_id FROM otp_translators ORDER BY amara_id';
	if($result = $db->query($sql)) {
		while($row = $result->fetch_assoc()) {
			$amara_valid_users[] = $row['amara_id'];	
		}
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}

	$db->close();	
}

# The difference of the two arrays will be the list we look for
$amara_users_update = array_diff($amara_users, $amara_valid_users);
$amara_users_update = array_unique($amara_users_update);

# Remove the user "" and NULL
if ($key = array_search('', $amara_users_update)) {
	unset($amara_users_update[$key]);
}
if ($key = array_search(NULL, $amara_users_update)) {
	unset($amara_users_update[$key]);
}

otp_msg('Number of users to be added: '.count($amara_users_update));

# Get user details through Amara API
if (count($amara_users_update) > 0) {
	foreach ($amara_users_update as $num => $amara_id) {
		$api_dir = '/users/'.urlencode($amara_id);
		$api_return = call_amara_api($api_dir);
		if (is_string($api_return)) {
			# There was an error using the API
			# Show the HTTP/CURL error code
			otp_error("Amara API connection error $api_return table: users, record: $amara_id");
		} else {
			otp_msg("Connected to Amara API. HTTP 200 table: users id: $amara_id");
			# Get additional user data from API
			$api_dir = '/teams/ted/members/'.urlencode($amara_id);
			$api_return2 = call_amara_api($api_dir);
			if (is_string($api_return2)) {
			# There was an error using the API
			# Show the HTTP/CURL error code
				otp_error("Amara API connection error $api_return2 table: members, record: $amara_id");
			} else {
				otp_msg("Connected to Amara API. HTTP 200 table: members, id: $amara_id");
			}
			if (!isset($api_return2['role'])) {
				$api_return['role'] = 'contributor';
				$left_ted_list[] = $amara_id;
			} else {
				$api_return['role'] = $api_return2['role'];
			}
			$sql_list[] = '("'. $amara_id .'", "'. $api_return['first_name'] .'", "'. $api_return['last_name']
			.'", "'. $api_return['full_name'] .'", "'. $api_return['avatar'].'", "'. $api_return['role'] .'")';
		}
	}
}

otp_msg('Number of users outside TED team: '.count($left_ted_list));


# Update otp_translators table in DB
if (count($sql_list) > 0) {
	$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
	if($db->connect_errno > 0){
		otp_error($db->connect_error.' line '.__LINE__);
	} else {
		$db->query("set names 'utf8'");
		# Update user basic data
		$sql = 'INSERT INTO otp_translators (amara_id, first_name, last_name, full_name, amara_pic_link, amara_role) VALUES ' .implode(',', $sql_list)
              .' ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), full_name=VALUES(full_name), 
		      amara_pic_link=VALUES(amara_pic_link), amara_role=VALUES(amara_role)';
		if($db->query($sql)) {
			otp_msg("Rows updated in otp_translators table: ". $db->affected_rows ." user data"); 
		} else {
			otp_error($db->error.' line '.__LINE__);
		}

		# Update TED team membership for added users
		if (count($left_ted_list) > 0) {
			$sql = 'UPDATE otp_translators SET amara_ted_member=0 WHERE amara_id IN (' .implode(',', $left_ted_list) .')';
			if($db->query($sql)) {
				otp_msg("Rows updated in otp_translators table: ". $db->affected_rows ." TED membership"); 
			} else {
				otp_error($db->error.' line '.__LINE__);
			}
		}

		$db->close();
	}
} else {
	otp_msg("There was nothing to update in otp_translators table");
}

# Update translator languages table
$sql_list = array();
if (count($amara_users_update) > 0) {

	# Prepare array to translate language name to language code
	$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
	if($db->connect_errno > 0){
		otp_error($db->connect_error.' line '.__LINE__);
	} else {
		$db->query("set names 'utf8'");
		$sql = 'SELECT language_id, language_name FROM otp_languages ORDER BY language_name';
		if($result = $db->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$language_table[$row['language_name']] = $row['language_id'];	
			}
		$result->free();
		} else {
			otp_error($db->error.' line '.__LINE__);
		}
		$db->close();
	}

	# Get languages from Amara member page for each user
	foreach ($amara_users_update as $key => $amara_id) {
		$url = 'http://amara.org/en/profiles/profile/' . $amara_id;
				
		# Download the page, extract the link
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$response_body = curl_exec($ch);
		$response_info = curl_getinfo($ch);
		$curl_error = curl_errno($ch);
		curl_close ($ch); 

		if ($curl_error == 0) {
			if (intval($response_info['http_code']) == 200) {
				otp_msg("Connected to Amara webpage HTTP 200 page: member, id: $amara_id");
				$response_body = mb_convert_encoding($response_body, 'html-entities', 'utf-8'); 
				$dom = new DOMDocument;
				$dom->validateOnParse = true;
				@$dom->loadHTML($response_body);
				$dom->preserveWhiteSpace = false;

				$xpath = new DOMXPath($dom);
				$classname = 'language';
				$results = $xpath->query("//*[@class='" . $classname . "']");
				$num_results = $results->length;

				for ($i=0; $i < $num_results; $i++) { 
					$language_name = $results->item($i)->nodeValue;
					$sql_list[] = '("'. $amara_id .'", "'. $language_table[$language_name] .'")';
				}
			} else {
				otp_error('Connection failed to Amara webpage HTTP '.$response_info['http_code'].' page: member, id: ' .$amara_id);
			}	
		} else {
			otp_error('Connection failed to Amara webpage CURL '. curl_errno($ch) .' page: member, id: ' .$amara_id);
		}
	}
	otp_msg('Found '. count($sql_list) . ' languages');
} else {
	otp_msg("No need to update languages");
}
		
if (count($sql_list) > 0) {
	$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
	if($db->connect_errno > 0){
		otp_error($db->connect_error.' line '.__LINE__);
	} else {
		$db->query("set names 'utf8'");
		$sql = 'INSERT INTO otp_translator_languages (amara_id, language_id) VALUES ' .implode(',', $sql_list)
		      .' ON DUPLICATE KEY UPDATE amara_id=VALUES(amara_id), language_id=VALUES(language_id)';
		if($db->query($sql)) {
			otp_msg("Rows updated in otp_translator_languages table: ". $db->affected_rows); 
		} else {
			otp_error($db->error.' line '.__LINE__);
		}
		$db->close();
	}
}

otputils_cleanup();
?>