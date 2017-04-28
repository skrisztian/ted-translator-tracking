<?php
# file name: update_ted_pic_link.php
# version: 1.0
# date: 12 Feb 2014
# author: Krisztian Stancz
#
# Updates ted_pic_link
# in the otp_translators table.
# Should be run as a cron job.

include "otputils.php";

otputils_prepare();
otputils_set_msg_log("data_update");

$amara_users = array();
$sql_list = array(); 
$p = otputils_db_params();

# Get list of TED IDs to update with pic link from DB
$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
if($db->connect_errno > 0){
	otp_error($db->connect_error.' line '.__LINE__);
}
else {
	$db->query("set names 'utf8'");
	$sql = 'SELECT amara_id, ted_id FROM otp_translators WHERE ted_id is NOT NULL AND ted_pic_link IS NULL';
	if ($result = $db->query($sql)) {
		while ($row = $result->fetch_assoc()) {
			$amara_users[$row['amara_id']] = $row['ted_id'];
		}
		otp_msg('TED pic link update needed for '. $result->num_rows . ' rows');
		$result->free();
	}
	else {
		otp_error($db->error.' line '.__LINE__);
	}
	$db->close();
}

if (count($amara_users) > 0) {
	foreach ($amara_users as $amara_id => $ted_id) {
		# Construct URL for TED profile page, where the pic link is stored
		$url = 'http://www.ted.com/profiles/'.$ted_id;

		# Download the page, extract the link
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$response_body = curl_exec($ch);
		$response_info = curl_getinfo($ch);
		curl_close ($ch); 

		if (curl_errno($ch) == 0) {
			if (intval($response_info['http_code']) == 200) {
				$response_body = mb_convert_encoding($response_body, 'html-entities', 'utf-8'); 
				$dom = new DOMDocument;
				$dom->validateOnParse = true;
				@$dom->loadHTML($response_body);
				$dom->preserveWhiteSpace = false;
				foreach($dom->getElementById('photos')->getElementsByTagName('img') as $img) {
					$pic_link = $img->getAttribute('src');
				}
				# Update query array with the link
				if (isset($pic_link)) {
					$sql_list[] = '("'. $amara_id .'", "'. $pic_link .'")';
				}
				else {
					$sql_list[] = '("'. $amara_id .'", "")';
				}
				otp_msg('Connected to TED webpage HTTP 200 page: profile');
			}
			else {
				otp_error('Connection failed to TED webpage HTTP '.$response_info['http_code'].' page: profile');
			}	
		}
	}
}
else {
	otp_error('Connection failed to TED webpage CURL '. curl_errno($ch) .' page: profile');
}

# Update DB with the links
if (count($sql_list) > 0) {
	$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
	if($db->connect_errno > 0){
		otp_error($db->connect_error.' line '.__LINE__);
	}
	else {
		$sql = 'INSERT INTO otp_translators (amara_id, ted_pic_link) VALUES ' .implode(',', $sql_list)
               .' ON DUPLICATE KEY UPDATE ted_pic_link=VALUES(ted_pic_link)';
        if($db->query($sql)) {
			otp_msg('Rows updated in otp_translators table: ' . $db->affected_rows);
		}
		else {
			otp_error($db->error.' line '.__LINE__);
		}
		$db->close();
	}
}

otputils_cleanup();
?>