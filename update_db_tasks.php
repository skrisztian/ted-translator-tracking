<?php
# file name: update_db_tasks.php
# version: 1.0
# date: 12 Feb 2014
# author: Krisztian Stancz
#
# Fills and updates the otp_tasks table by downloading
# all specified task entries through the Amara API.
# Should be run as a cron job.

include "otputils.php";

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
	# get total_count for tasks
	$sql = "SELECT COUNT(*) FROM otp_tasks";
	if ($result = $db->query($sql)) {
		# we expect only one value
		$record_count_db = intval($result->fetch_row()[0]);
		$result->free();
	}
	else {
		otp_error($db->error.' line '.__LINE__);
	}
}

# Loop API calls, until next=null in the API meta data.
if (isset($record_count_db)) {
	do {
		# Connect to  Amara API, to get tasks filtered for 'language' = 'hu'
		$api_dir = '/teams/ted/tasks';
		$api_params = array('limit' => $limit, 'offset' => $offset, 'language' => 'hu');

		$api_return = call_amara_api($api_dir, $api_params);
		if (is_string($api_return)) {
			# There was an error using the API
			# Show the HTTP error code
			otp_error("Amara API connection error ". $api_return . " table: tasks, run: $count, limit: $limit, offset: $offset");
		}
		else {
			otp_msg("Connected to Amara API. HTTP 200 table: tasks, run: $count, limit: $limit, offset: $offset");
			# var_dump($api_return);
			
			# Get meta data
			$total_count = $api_return['meta']['total_count'];
			$to_download = $total_count-$record_count_db;
			otp_msg("Number of records needed to be downloaded: $to_download");
			$next = $api_return['meta']['next'];
			if (($count == 1) && ($record_count_db > 0)) {
				$offset = $record_count_db-2;
			}
			else {
				if ($next != null) {
					preg_match('/(.*offset=)(\d*)(\D*.*)$/', $next, $match);
					$offset = $match[2];
				}
			}
			
			# Get task values
			# 'id' 'approved', 'assignee', 'completed' 'language' 'priority' 'type' 'video_id' 
			foreach ($api_return['objects'] as $row => $data) {
				$sql_list[] = '("'. $data['id'] .'", "'. $data['approved'] .'", "'. $data['assignee']
				.'", "'. $data['completed'] .'", "'. $data['language'] .'", "'. $data['priority'] .'", "'. $data['type'] .'", "'. $data['video_id'] .'")';
			}
			
			# Update DB with task data 
			if (count($sql_list) > 0) {
				$sql = 'INSERT INTO otp_tasks (id, approved, assignee, completed, language, priority, type, video_id) VALUES '
					  .implode(',', $sql_list) .' ON DUPLICATE KEY UPDATE approved=VALUES(approved), assignee=VALUES(assignee),
					   completed=VALUES(completed)';
				if($db->query($sql)) {
					otp_msg("Rows uploaded/updated in otp_tasks table: ". $db->affected_rows); # counts failed inserts too
				}
				else {
					otp_error($db->error.' line '.__LINE__);
				}
				# after update count the total number of rows
				$sql = "SELECT COUNT(*) FROM otp_tasks";
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
	} while (($next != null) && ($record_count_db < $total_count));
}
	
# Update DB with total count value 
if (isset($total_count)) {
	$sql = "UPDATE otp_api_meta SET total_count_value=". $total_count ." WHERE total_count_name='task_hu'";
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
		otp_error("Inconsistency in otp_tasks data! DB: $record_count_db API: $total_count record counts do not match.");
}
else {
	otp_msg("DB: $record_count_db API: $total_count record counts are in sync for otp_tasks");
}



$db->close();

otputils_cleanup();
?>