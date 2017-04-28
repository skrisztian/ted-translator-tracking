<?php
# file name: update_names.php
# version: 1.0
# date: 12 Feb 2014
# author: Krisztian Stancz
#
# Updates first_name, last_name, amara_pic_link
# in the otp_translators table.
# Should be run as a cron job.

include "otputils.php";

otputils_prepare();
otputils_set_msg_log("data_update");
otputils_set_msg_log_continuous();

$amara_users = array();
$sql_list = array();
$p = otputils_db_params();

# Get list of amara_ids to update with data in DB
$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
if($db->connect_errno > 0) {
	otp_error($db->connect_error.' line '.__LINE__);
} else {
	$db->query("set names 'utf8'");
	$sql = 'SELECT amara_id FROM otp_translators WHERE first_name is NULL OR last_name is NULL';
	if($result = $db->query($sql)) {
		while($row = $result->fetch_assoc()) {
			$amara_users[] = $row['amara_id'];
		}
		otp_msg('Number of users needing update: '. $result->num_rows);
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}
	$db->close();	
}

# Get user details through Amara API
if (count($amara_users) > 0) {
	foreach ($amara_users as $num => $amara_id) {
		$api_dir = '/users/'.urlencode($amara_id);
		$api_return = call_amara_api($api_dir);
		if (is_string($api_return)) {
			# There was an error using the API
			# Show the HTTP/CURL error code
			otp_error("Amara API connection error $api_return table: users, record: $amara_id");
		} else {
			# var_dump($api_return);
			$sql_list[] = '("'. $amara_id .'", "'. $api_return['first_name'] .'", "'. $api_return['last_name']
			.'", "'. $api_return['avatar'] .'")';
			otp_msg('Connected to Amara API. HTTP 200 table: users');
		}
	}
}

# Update DB with the links
if (count($sql_list) > 0) {
	$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
	if($db->connect_errno > 0){
		otp_error($db->connect_error.' line '.__LINE__);
	} else {
		$db->query("set names 'utf8'");
		$sql = 'INSERT INTO otp_translators (amara_id, first_name, last_name, amara_pic_link) VALUES ' .implode(',', $sql_list)
              .' ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), 
		      amara_pic_link=VALUES(amara_pic_link)';
		if($db->query($sql)) {
			otp_msg("Rows updated in otp_translators table: ". $db->affected_rows/2); # counts failed inserts too
		} else {
			otp_error($db->error.' line '.__LINE__);
		}
		$db->close();
	}
} else {
	otp_msg("There was nothing to update in otp_translators table");
}
otputils_cleanup();
?>