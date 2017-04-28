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
$videos_to_update = array();
$count = 1;
$limit = 20;
$offset = 0;

# Print script parameters for logfile
if (isset($argv[0])) {
	echo '['. date('D M d H:i:s T Y') ."] $argv[0]". PHP_EOL;
}

# Open database. We do not know how many lines in total will come back from the API,
# so we will do the inserts in batches after each API call.
$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
if($db->connect_errno > 0){
	otp_error($db->connect_error.' line '.__LINE__);
	exit(1);
} else {
	$db->query("set names 'utf8'");

	# get total_count for tasks
	$sql = "SELECT COUNT(*) FROM otp_tasks";
	if ($result = $db->query($sql)) {
		# we expect only one value
		$record_count_db = intval($result->fetch_row()[0]);
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}

	# get last id for tasks
	$sql = "SELECT id FROM otp_tasks ORDER BY id DESC LIMIT 1";
	if ($result = $db->query($sql)) {
		# we expect only one value
		$last_task_id = intval($result->fetch_row()[0]);
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}
}

# Loop API calls, until next=null in the API meta data.
if (isset($record_count_db)) {
	# If the DB is empty, speed up the process by increasing limit to 80
	if ($record_count_db == 0) {
		$limit = 80;
	}
	do {
		# Connect to  Amara API, to get tasks filtered for 'language' = 'hu'
		$api_dir = '/teams/ted/tasks';
		$api_params = array('limit' => $limit, 'offset' => $offset, 'language' => 'hu');

		$api_return = call_amara_api($api_dir, $api_params);
		if (is_string($api_return)) {
			# There was an error using the API
			# Show the HTTP error code
			otp_error("Amara API connection error ". $api_return . " table: tasks, run: $count, limit: $limit, offset: $offset");
		} else {
			otp_msg("Connected to Amara API. HTTP 200 table: tasks, run: $count, limit: $limit, offset: $offset");
			# var_dump($api_return);
			
			# Get meta data
			$total_count = $api_return['meta']['total_count'];
			$to_download = $total_count-$record_count_db;
			otp_msg("Number of records needed to be downloaded: $to_download");
			$next = $api_return['meta']['next'];
			if (($count == 1) && ($record_count_db > 0)) {
				$offset = $record_count_db-2;
			} else {
				if ($next != null) {
					preg_match('/(.*offset=)(\d*)(\D*.*)$/', $next, $match);
					$offset = $match[2];
				}
			}
			
			# Get task values
			# 'id' 'approved', 'assignee', 'completed' 'language' 'priority' 'type' 'video_id' 
			foreach ($api_return['objects'] as $row => $data) {
				@otputils_prepare_for_sql($db, $data['id'], $tasks['id']);
				@otputils_prepare_for_sql($db, $data['approved'], $tasks['approved']);
				@otputils_prepare_for_sql($db, $data['assignee'], $tasks['assignee']);
				@otputils_prepare_for_sql($db, $data['completed'], $tasks['completed']);
				@otputils_prepare_for_sql($db, $data['language'], $tasks['language']);
				@otputils_prepare_for_sql($db, $data['priority'], $tasks['priority']);
				@otputils_prepare_for_sql($db, $data['type'], $tasks['type']);
				@otputils_prepare_for_sql($db, $data['video_id'], $tasks['video_id']);

				$sql_list[] = '('. $tasks['id'] .', '. $tasks['approved'] .', '. $tasks['assignee']
							.', '. $tasks['completed'] .', '. $tasks['language'] .', '. $tasks['priority'] 
							.', '. $tasks['type'] .', '. $tasks['video_id'] .')';

				if (intval($data['id']) > $last_task_id) {
					$videos_to_update[] = $data['video_id'];
				}
			}
			
			# Update DB with task data 
			if (count($sql_list) > 0) {
				$sql = 'INSERT INTO otp_tasks (id, approved, assignee, completed, language, priority, type, video_id) VALUES '
					  .implode(',', $sql_list) .' ON DUPLICATE KEY UPDATE approved=VALUES(approved), assignee=VALUES(assignee),
					   completed=VALUES(completed)';
				if($db->query($sql)) {
					otp_msg("Rows uploaded/updated in otp_tasks table: ". $db->affected_rows); # counts failed inserts too
				} else {
					otp_error($db->error.' line '.__LINE__);
				}
				# after update count the total number of rows
				$sql = "SELECT COUNT(*) FROM otp_tasks";
				if ($result = $db->query($sql)) {
					# we expect only one value
					$record_count_db = intval($result->fetch_row()[0]);
					$result->free();
				} else {
					otp_error($db->error.' line '.__LINE__);
				}
			} else {
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
	} else {
		otp_error($db->error.' line '.__LINE__);
	}
} else {
	otp_msg("No total count to update in otp_api_meta table");
}

# Update subtitle status for videos refered in the new tasks
if (count($videos_to_update) > 0) {

	# Clean up duplicates in arrays
	$videos_to_update = array_unique($videos_to_update);
	$videos_to_update = array_values($videos_to_update);

	# Update subtitle data
	foreach ($videos_to_update as $key => $video_id) {
		otputils_get_subtitle_data($db, $video_id);
	}
}
otp_msg("Subtitle data updated based on new tasks: ". count($videos_to_update));

# Final check for data consistency
if ($record_count_db != $total_count) {
		otp_error("Inconsistency in otp_tasks data! DB: $record_count_db API: $total_count record counts do not match.");
} else {
	otp_msg("DB: $record_count_db API: $total_count record counts are in sync for otp_tasks");
}

$db->close();

otputils_cleanup();
?>