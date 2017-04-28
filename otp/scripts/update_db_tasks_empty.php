<?php
# file name: update_db_activity.php
# version: 1.0
# date: 12 Feb 2014
# author: Krisztian Stancz
#
# Fills the otp_activity table by downloading
# all specified activity entries through the Amara API.
# Should be run as a cron job.

include "otputils.php";
include "htmlutils.php";

otputils_prepare();
otputils_set_msg_log("data_update");
otputils_set_msg_log_continuous();

$sql_list = array();
$p = otputils_db_params();
$videos_to_update = array();
$tasks_to_update = array();
$count = 1;
$limit = 20;
$offset = 0;

# Open database. We do not know how many lines in total will come back from the API,
# so we will do the inserts in batches after each API call.
$db = otputils_open_db();

# Get list of tasks to update
$sql = "SELECT id FROM otp_tasks WHERE completed IS NULL";
if ($result = $db->query($sql)) {
	while ($row = $result->fetch_row()[0]) {
		$tasks_to_update[] = $row;
	}
	$result->free();
} else {
	otp_error($db->error.' line '.__LINE__);
}

otp_msg("Tasks to check based on empty completion date: ". count($tasks_to_update));

$i =0;
if (count($tasks_to_update) > 0) {
	foreach ($tasks_to_update as $key => $task_id) {
		$i++;
		# Connect to  Amara API, to get tasks filtered for 'language' = 'hu'
		$api_dir = "/teams/ted/tasks/$task_id";
		# $api_params = array('limit' => $limit, 'offset' => $offset, 'language' => 'hu');

		$api_return = call_amara_api($api_dir);
		if (is_string($api_return)) {
			# There was an error using the API
			# Show the HTTP error code
			otp_error("$i: Amara API connection error ". $api_return . " table: tasks, id: ". $task_id);
		} else {
			otp_msg("$i: Connected to Amara API. HTTP 200 table: tasks, id: ". $task_id);
			# var_dump($api_return);
			
			# Get task values
			# 'id' 'approved', 'assignee', 'completed' 'language' 'priority' 'type' 'video_id' 
			@otputils_prepare_for_sql($db, $api_return['id'], $tasks['id']);
			@otputils_prepare_for_sql($db, $api_return['approved'], $tasks['approved']);
			@otputils_prepare_for_sql($db, $api_return['assignee'], $tasks['assignee']);
			@otputils_prepare_for_sql($db, $api_return['completed'], $tasks['completed']);

			# Update DB with task data 
			$sql = "UPDATE otp_tasks SET approved=". $tasks['approved'] .", assignee=". $tasks['assignee'] .", completed="
				  . $tasks['completed'] ." WHERE id=". $tasks['id'];

			if($db->query($sql)) {
				otp_msg("$i: Row updated in otp_tasks table for: $task_id"); 
			} else {
				otp_error($db->error.' line '.__LINE__);
			}
		}
	}
}

$db->close();

otputils_cleanup();
?>