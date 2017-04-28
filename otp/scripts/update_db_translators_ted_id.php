<?php
# file name: update_ted_id.php
# version: 1.0
# date: 12 Feb 2014
# author: Krisztian Stancz
#
# Fills in ted_id to amara_id in the otp_translators table,
# by matching the full_name values from both sites.
# Unmatched ted_id-s are loaded into the ted_translators table.
# Should be run as a cron job.

include "otputils.php";

otputils_prepare();
otputils_set_msg_log("data_update");

function clean_accent(&$string, $key) {
	$chars = array(
		"á"=>"a","é"=>"e","í"=>"i",
		"ü"=>"u","ű"=>"u","ú"=>"u",
		"ő"=>"o","ö"=>"o","ó"=>"o",
		"Á"=>"A","É"=>"E","Í"=>"I",
		"Ü"=>"U","Ű"=>"U","Ú"=>"U",
		"Ő"=>"O","Ö"=>"O","Ó"=>"O",);
	$string = str_replace(array_keys($chars), $chars, $string);
}

function clean_whitespace(&$string, $key) {
	$string = trim($string);
	$string = preg_replace('/\s+/', ' ', $string);
}

function compare_arrays(&$in1_array, &$in2_array, &$out_array) {
# call: compare_arrays($ted_users, $amara_users, $amara_users_updated)
	$searched = 0;
	$found = 0;
	foreach ($in1_array as $in1_key => $in1_value) {
		$keys = array_keys($in2_array, $in1_value);
		$searched++;
		if (count($keys) == 1) {
			# println("* found TED name $in1_value in Amara");
			$out_array[$keys[0]] = array($in1_value, $in1_key);
			unset($in1_array[$in1_key]);
			unset($in2_array[$keys[0]]);
			$found++;
		}
		elseif (count($keys) > 1) {
			# println("*** TED name $in1_value matches more than one name in Amara")
		}
	}
	otp_msg('Searched ' . $searched . ' pairs, found '. $found . ' matches');
}

$ted_users = array();
$amara_users = array();
$amara_users_updated = array();
$p = otputils_db_params();
$url = "http://www.ted.com/translate/languages/hu/";

# Get ted_id and ted_full_name from the TED Translators HU page
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
		$response_body = mb_convert_encoding($response_body, 'html-entities', 'utf-8'); 
		$dom = new DOMDocument;
		$dom->validateOnParse = true;
		@$dom->loadHTML($response_body);
		$dom->preserveWhiteSpace = false;
		foreach ($dom->getElementsByTagName('div') as $div) {
			if (preg_match('/names/', $div->getAttribute('class'))) {
				foreach ($div->getElementsByTagName('a') as $link) {
					$ted_full_name = $link->nodeValue; # names between the two <a> tags
					preg_match("/.*\/profiles\/(\d+)\/.*/", $link->getAttribute('href'), $match);
					$ted_id = $match[1]; # TED user ID
					$ted_users_web[$ted_id] = $ted_full_name;
				}
			}		
		}
		$ted_users = $ted_users_web;
		otp_msg('Connected to TED webpage HTTP 200. page: hu translators Got '. count($ted_users) .' ids');
	}
	else {
		$otp_error('TED webpage connection error HTTP '.$response_info['http_code'].' page: hu translators');
	}
}
else {
	$otp_error("TED webpage connection error CURL $curl_error page: hu translators");
}	
	
# Get amara_id, amara_full_name from the DB
$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
if($db->connect_errno > 0){
	otp_msg($db->connect_error.' line '.__LINE__);
	otp_error();
}
else {
	$db->query("set names 'utf8'");
	$sql = 'SELECT amara_id, full_name FROM otp_translators';	
	if ($result = $db->query($sql)) {
		while ($row = $result->fetch_assoc()) {
			$amara_users[$row['amara_id']] = $row['full_name'];
		}
		otp_msg('DB query returned '. $result->num_rows . ' rows');
		$result->free();
	}
	else {
		otp_msg($db->error.' line '.__LINE__);
		otp_error();
	}

	# Compare two sources
	compare_arrays($ted_users, $amara_users, $amara_users_updated);

	# Remove accents and compare two sources
	array_walk($ted_users, 'clean_accent');
	array_walk($amara_users, 'clean_accent');
	compare_arrays($ted_users, $amara_users, $amara_users_updated);

	# Remove extra spaces and compare two sources
	array_walk($ted_users, 'clean_whitespace');
	array_walk($amara_users, 'clean_whitespace');
	compare_arrays($ted_users, $amara_users, $amara_users_updated);

	# Update TED ID in otp_translators table
	$sql_list = array(); 
	if (count($amara_users_updated) > 0) {
		foreach ($amara_users_updated as $amara_id => $data) {
			# Upload amara_id, ted_id
			$sql_list[] = '("'. $amara_id .'", "'. $data[1] .'")';
		}
		$sql = 'INSERT INTO otp_translators (amara_id, ted_id) VALUES ' .implode(',', $sql_list)
			   .' ON DUPLICATE KEY UPDATE ted_id=VALUES(ted_id)';
		if($db->query($sql)){
			otp_msg('Rows updated in otp_translators table: ' . $db->affected_rows); 
		}
		else {
			otp_msg($db->error.' line '.__LINE__);
			otp_error();
		}
	}

	# Load unmatched TED ID-s into ted_translators for further reference
	$sql_list = array(); 
	if (count($ted_users) > 0) {
		foreach ($ted_users as $ted_id => $value) {
			# Upload ted_id, ted_full_name
			# use ted_users_web, that still has the original accented names
			$sql_list[] = '("'. $ted_id .'", "'. $ted_users_web[$ted_id] .'")';
		}
		$sql = 'INSERT INTO ted_translators (ted_id, ted_full_name) VALUES ' .implode(',', $sql_list);
		$db->query('TRUNCATE TABLE ted_translators');
		if($db->query($sql)) {
			otp_msg('Rows uploaded into ted_translators table: ' . $db->affected_rows); # counts failed inserts too
		}
		else {
			otp_msg($db->error.' line '.__LINE__);
			otp_error();
		}
	}
	$db->close();
}

otputils_cleanup();
?>