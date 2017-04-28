<?php
# file name: update_languages.php
# version: 1.0
# date: 12 Feb 2014
# author: Krisztian Stancz
#
# This script keeps the list of Amara languages updated.
# It first queries the Amara API, gets a list of current languages
# then writes it into the database. 
# Any changes in the language description field is updated. 
# Newly added languages are inserted into the database.

include "otputils.php";

otputils_prepare();
otputils_set_msg_log("data_update");

$api_dir = "/languages/";
$sql_list = array(); 
$p = otputils_db_params();

#Issue API query
$api_return = call_amara_api($api_dir);

# Get user details through Amara API
$api_return = call_amara_api($api_dir);
if (is_string($api_return)) {
	# There was an error using the API
	# Show the HTTP error code
	otp_error('Amara API connection error '. $api_return . ' table: languages');
}
else {
	# Successful API call, we have an array with languages in the form of:
	# $api_return = array('languages' => array('language_id' => 'language_name'))
	otp_msg('Connected to Amara API HTTP 200 table: languages');
	foreach ($api_return['languages'] as $lang_id => $lang_name) {
		$sql_list[] = '("'. $lang_id .'", "'. $lang_name .'")';
	}
	if (count($sql_list) > 0) {
		$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
		if($db->connect_errno > 0){
			otp_error($db->connect_error.' line '.__LINE__);
		}
		else {
			$db->query("set names 'utf8'");
			$sql = 'INSERT INTO otp_languages (language_id, language_name) VALUES ' .implode(',', $sql_list)
              .' ON DUPLICATE KEY UPDATE language_name=VALUES(language_name)';
		}
		if($db->query($sql)) {
			otp_msg("Languages loaded/updated in otp_languages table: ". $db->affected_rows);
		}
		else {
			otp_error($db->error.' line '.__LINE__);
		}
		$db->close();
	}
	else {
		otp_msg("There was nothing to update in otp_languages table");
	}
}

otputils_cleanup();
?>