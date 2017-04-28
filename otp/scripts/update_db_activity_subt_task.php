<?php
# file name: update_db_activity_subt_task.php
# version: 1.0
# date: 28 Mar 2014
# author: Krisztian Stancz
#
# Fills the otp_activity, otp_subtitles and otp_tasks tables by downloading
# all specified activity entries through the Amara API.
# Should be run as a cron job.

include "otputils.php";

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
	
	# get total_count for activity
	$sql = "SELECT COUNT(*) FROM otp_activity";
	if ($result = $db->query($sql)) {
		# we expect only one value
		$record_count_db = intval($result->fetch_row()[0]);
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}

	# get last id for activities
	$sql = "SELECT id FROM otp_activity ORDER BY id DESC LIMIT 1";
	if ($result = $db->query($sql)) {
		# we expect only one value
		$last_activity_id = intval($result->fetch_row()[0]);
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}
}

# Loop API calls, until activity count in the db and in the API equals, or there are no more updates.
if (isset($record_count_db)) {
	# If the DB is empty, speed up the process by increasing limit to 80
	if ($record_count_db == 0) {
		$limit = 80;
	}
	do {
		# Connect to  Amara API, to get activity filtered for 'language' = 'hu', 'team'='ted'
		$api_dir = '/activity';
		$api_params = array('limit' => $limit, 'offset' => $offset, 'language' => 'hu', 'team' => 'ted');

		$api_return = call_amara_api($api_dir, $api_params);
		if (is_string($api_return)) {
			# There was an error using the API
			# Show the HTTP or CURL error code
			otp_error("Amara API connection error ". $api_return . " table: activity, run: $count, limit: $limit, offset: $offset");
		} else {
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
			
			# Get activity values
			# 'id' 'comment' 'created' 'language' 'language_url' 'type' 'user' 'video_id'
			foreach ($api_return['objects'] as $row => $data) {
				# in certain types the comment field is missing,
				# we need to add, to escape sql errors

				@otputils_prepare_for_sql($db, $data['id'], $activities['id']);
				@otputils_prepare_for_sql($db, $data['comment'], $activities['comment']);
				@otputils_prepare_for_sql($db, $data['created'], $activities['created']);
				@otputils_prepare_for_sql($db, $data['language'], $activities['language']);
				@otputils_prepare_for_sql($db, $data['language_url'], $activities['language_url']);
				@otputils_prepare_for_sql($db, $data['type'], $activities['type']);
				@otputils_prepare_for_sql($db, $data['user'], $activities['user']);
				@otputils_prepare_for_sql($db, $data['video'], $activities['video_id']);
				
				$sql_list[] = '('. $activities['id'] .', '. $activities['comment'] .', '. $activities['created']
							.', '. $activities['language'] .', '. $activities['language_url'] .', '. $activities['type']
							.', '. $activities['user'] .', '. $activities['video_id'] .')';

				# Update later video and task data for non comment activities, which have video_id
				if ((intval($data['id']) > $last_activity_id) && ($data['type'] <> 3) && isset($data['video'])){
					$videos_to_update[] = $data['video'];
				}
			}
					
			# Update DB with activity data 
			if (count($sql_list) > 0) {
				$sql = 'INSERT INTO otp_activity (id, comment, created, language, language_url, type, user, video_id) VALUES '
				   .implode(',', $sql_list) .' ON DUPLICATE KEY UPDATE comment=VALUES(comment), created=VALUES(created), '
				   .'language=VALUES(language), language_url=VALUES(language_url), type=VALUES(type), user=VALUES(user), '
				   .'video_id=VALUES(video_id)';

				# Update db   
				if($db->query($sql)) {
					otp_msg("Rows uploaded/updated in otp_activity table: ". $db->affected_rows); 
				} else {
					otp_error($db->error.' line '.__LINE__);
				}
				# after update count the total number of rows
				$sql = "SELECT COUNT(*) FROM otp_activity";
				if ($result = $db->query($sql)) {
					# we expect only one value
					$record_count_db = intval($result->fetch_row()[0]);
					$result->free();
				} else {
					otp_error($db->error.' line '.__LINE__);
				}
			} else {
			otp_msg("There was nothing to update in otp_activity table");
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
	} else {
		otp_error($db->error.' line '.__LINE__);
	}
} else {
	otp_msg("No total count to update in otp_api_meta table");
}

# Check for data consistency
if ($record_count_db != $total_count) {
		otp_error("Inconsistency in otp_activity data! DB: $record_count_db API: $total_count record counts do not match.");
} else {
	otp_msg("DB: $record_count_db API: $total_count record counts are in sync for otp_activity");
}

# Update subtitle status for videos refered in the new activities
otp_msg("Subtitles to update based on new activity: ". count($videos_to_update));

if (count($videos_to_update) > 0) {

	# Clean up duplicates in arrays
	$videos_to_update = array_unique($videos_to_update);
	$videos_to_update = array_values($videos_to_update);

	# Update subtitle data
	foreach ($videos_to_update as $key => $video_id) {
		otputils_get_subtitle_data($db, $video_id);
	}

	foreach ($videos_to_update as $key => $video_id) {
		$videos_to_update[$key] = "'". $video_id . "'";
	}	

	# Prepare the list from activities for task sql 
	$sql_activity_list = "OR video_id IN (". implode(', ', $videos_to_update) .")";
} else {
	$sql_activity_list = null;
}

# Update task data
# We need to update tasks which are:
# - Approve with no completion date
# - Review with no completion date
# - Anything with no completion date that has a video_id which is referred in the downloaded tasks
# We do not download Translate incomplete tasks, because that would be way too many

$sql = "SELECT id FROM otp_tasks 
		WHERE completed IS NULL 
			AND (type='Approve' OR type='Review' $sql_activity_list)";
if ($result = $db->query($sql)) {
	while ($row = $result->fetch_row()[0]) {
		$tasks_to_update[] = $row;
	}
	$result->free();
} else {
	otp_error($db->error.' line '.__LINE__);
}


otp_msg("Tasks to update (Approve/Review/Activity): ". count($tasks_to_update));

if (count($tasks_to_update) > 0) {
	$i = 0;
	foreach ($tasks_to_update as $key => $task_id) {
		$i++;

		# Connect to  Amara API, to get tasks filtered for 'language' = 'hu'
		$api_dir = "/teams/ted/tasks/$task_id";
		# $api_params = array('limit' => $limit, 'offset' => $offset, 'language' => 'hu');

		$data = call_amara_api($api_dir);
		if (is_string($api_return)) {
			# There was an error using the API
			# Show the HTTP error code
			otp_error("$i: Amara API connection error ". $api_return . " table: tasks, id: ". $task_id);
		} else {
			otp_msg("$i: Connected to Amara API. HTTP 200 table: tasks, id: ". $task_id);
			# var_dump($api_return);
			
			# Get task values
			# 'id' 'approved', 'assignee', 'completed' 'language' 'priority' 'type' 'video_id' 
			@otputils_prepare_for_sql($db, $data['id'], $tasks['id']);
			@otputils_prepare_for_sql($db, $data['approved'], $tasks['approved']);
			@otputils_prepare_for_sql($db, $data['assignee'], $tasks['assignee']);
			@otputils_prepare_for_sql($db, $data['completed'], $tasks['completed']);
			@otputils_prepare_for_sql($db, $data['language'], $tasks['language']);
			@otputils_prepare_for_sql($db, $data['priority'], $tasks['priority']);
			@otputils_prepare_for_sql($db, $data['type'], $tasks['type']);
			@otputils_prepare_for_sql($db, $data['video_id'], $tasks['video_id']);

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

# Force an update on the task api timestamp
$sql = "UPDATE otp_api_meta SET last_update=NOW() WHERE total_count_name='task_hu'";
if($db->query($sql)) {
	otp_msg("Updated timestamp in otp_api_meta for task_hu"); 
} else {
	otp_error($db->error.' line '.__LINE__);
}

$db->close();

otputils_cleanup();
?>