<?php
# file name: update_db_activity.php
# version: 1.0
# date: 12 Feb 2014
# author: Krisztian Stancz
#
# Fills the otp_tasks table by downloading
# all specified task entries through the Amara API.
# Should be run as a cron job.

include "otputils.php";
include "htmlutils.php";

otputils_prepare();
otputils_set_msg_log("data_update");
otputils_set_msg_log_continuous();

$sql_list = array();
$p = otputils_db_params();
$count = 1;
$limit = 20;
$offset = 0;

# Open database. We do not know how many lines in total will come back from the API,
# so we will do the inserts in batches after each API call.
$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
if($db->connect_errno > 0){
	otp_error($db->connect_error.' line '.__LINE__);
}
else {
	$db->query("set names 'utf8'");
	# get total_count for activity
	$sql = "SELECT COUNT(*) FROM otp_activity";
	if ($result = $db->query($sql)) {
		# we expect only one value
		$record_count_db = intval($result->fetch_row()[0]);
		$result->free();
	}
	else {
		otp_error($db->error.' line '.__LINE__);
	}
}

# Loop API calls, until activity count in the db and in the API equals, or there are no more updates.
if (isset($record_count_db)) {
	do {
		# Connect to  Amara API, to get tasks filtered for 'language' = 'hu', 'team'='ted'
		$api_dir = '/activity';
		$api_params = array('limit' => $limit, 'offset' => $offset, 'language' => 'hu', 'team' => 'ted');

		$api_return = call_amara_api($api_dir, $api_params);
		if (is_string($api_return)) {
			# There was an error using the API
			# Show the HTTP or CURL error code
			otp_error("Amara API connection error ". $api_return . " table: activity, run: $count, limit: $limit, offset: $offset");
		}
		else {
			otp_msg("Connected to Amara API. HTTP 200 table: activity, run: $count, limit: $limit, offset: $offset");
			# var_dump($api_return);
		
			# Get meta data
			$next = $api_return['meta']['next'];
			if ($next != null) {			
				preg_match('/(.*offset=)(\d*)(\D*.*)$/', $next, $match);
				$offset = $match[2];
			}
			$total_count = $api_return['meta']['total_count'];
			$to_download = $total_count-$record_count_db;
			otp_msg("Number of records needed to be downloaded: $to_download");
			
			# Get task values
			# 'id' 'comment' 'created' 'language' 'language_url' 'type' 'user' 'video_id'
			foreach ($api_return['objects'] as $row => $data) {
				# in certain types the comment field is missing,
				# we need to add, to escape sql errors
				if (!array_key_exists('comment', $data)) {
					$data['comment'] = null; # the key itself should be enough
				}
				$sql_list[] = '("'. $data['id'] .'", "'. $data['comment'] .'", "'. $data['created']
				.'", "'. $data['language'] .'", "'. $data['language_url'] .'", "'. $data['type'] .'", "'
				. $data['user'] .'", "'. $data['video'] .'")';
			}
					
			# Update DB with activity data 
			if (count($sql_list) > 0) {
				$sql = 'INSERT INTO otp_activity (id, comment, created, language, language_url, type, user, video_id) VALUES '
				   .implode(',', $sql_list) .' ON DUPLICATE KEY UPDATE comment=VALUES(comment), created=VALUES(created), language=VALUES(language), language_url=VALUES(language_url), type=VALUES(type), user=VALUES(user), video_id=VALUES(video_id)';
				if($db->query($sql)) {
					otp_msg("Rows uploaded/updated in otp_activity table: ". $db->affected_rows); 
				}
				else {
					otp_error($db->error.' line '.__LINE__);
				}
				# after update count the total number of rows
				$sql = "SELECT COUNT(*) FROM otp_activity";
				if ($result = $db->query($sql)) {
					# we expect only one value
					$record_count_db = intval($result->fetch_row()[0]);
					$result->free();
				}
				else {
					otp_error($db->error.' line '.__LINE__);
				}
			}
			else {
			otp_msg("There was nothing to update in otp_tasks table");
			}
		}	
		$count++;
		if (!isset($total_count)) {
			$total_count = -1;
		}
	} while (($record_count_db < $total_count) && ($next != null));
}

# Update DB with total count value 
if (isset($total_count)) {
	$sql = "UPDATE otp_api_meta SET total_count_value = ". $total_count ." WHERE total_count_name = 'activity_hu'";
	if($db->query($sql)) {
		otp_msg("Rows updated in otp_api_meta table: ". $db->affected_rows);
	}
	else {
		otp_error($db->error.' line '.__LINE__);
	}
}
else {
	otp_msg("No total count to update in otp_api_meta table");
}

# final check for data consistency
if ($record_count_db != $total_count) {
		otp_error("Inconsistency in otp_activity data! DB: $record_count_db API: $total_count record counts do not match.");
}
else {
	otp_msg("DB: $record_count_db API: $total_count record counts are in sync for otp_activity");
}

$db->close();

otputils_cleanup();
?>