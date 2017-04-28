<?php
# file name: update_db_subtitles.php
# version: 1.0
# date: 19 Mar 2014
# author: Krisztian Stancz
#
# Fills the otp_subtitles table by downloading
# all specified task entries through the Amara API.
# Should be run as a cron job.
# ---------------------------------------
# Usage: update_db_subtitles.php video_id
# ---------------------------------------

# test video_id
# gz45APvPa9xJ
# BfCUWIEXN7Bc

include_once "otputils.php";
include_once "htmlutils.php";

otputils_prepare();
otputils_set_msg_log("data_update");
otputils_set_msg_log_continuous();

$video_list_tables = array();
$video_list_subtitles = array();
$video_list_download = array();
$sql_list = array();
$p = otputils_db_params();

$limit = 100;

# Get video ID from the command line parameter
if ((isset($argc)) && ($argc > 1)) {
	$video_id = $argv[1];
	$bulk_get = false;
	otp_msg('Running subtitle update in single get mode');
} else {
	$bulk_get = true;
	otp_msg('Running subtitle update in bulk get mode');
}

# Open db connection
$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
if($db->connect_errno > 0){
	otp_error($db->connect_error.' line '.__LINE__);
	exit(1);
} else {
	$db->query("set names 'utf8'");
}

# Decide operational mode and do DB queries
if ($bulk_get) {
	# Running in bulk get mode
	# Find all video IDs in the task and activity tables
	$tables = array(0 => 'otp_tasks', 1 => 'otp_activity', 2 => 'otp_subtitles');

	foreach ($tables as $key => $table) {

		$sql = "SELECT $table.video_id FROM $table LEFT OUTER JOIN otp_deleted_videos "
	     . "ON $table.video_id = otp_deleted_videos.video_id WHERE "
	     . "otp_deleted_videos.video_id IS NULL ORDER BY $table.video_id";

		if ($result = $db->query($sql)) {
			while($row = $result->fetch_assoc()) {
				if ($table == 'otp_subtitles') {
					$video_list_subtitles[] = $row['video_id'];
				} else {
					$video_list_tables[] = $row['video_id'];
				}
			}
			$result->free();
		} else {
			otp_error($db->error. ' '. $table .': line '.__LINE__);
		}
	}
	
	# Clean up duplicates in arrays
	$video_list_tables = array_unique($video_list_tables);
	$video_list_subtitles =	array_unique($video_list_subtitles);
	$video_list_download = array_diff($video_list_tables, $video_list_subtitles);
	$video_list_download = array_values($video_list_download);
	
	# Report what we have found
	otp_msg('Video IDs in tables: '.count($video_list_tables));
	otp_msg('Video IDs downloaded: '.count($video_list_subtitles));
	otp_msg('video IDs to get: '.count($video_list_download));
 	otp_msg("Limit: $limit");


 	if (count($video_list_download) < $limit) {
 		$limit = count($video_list_download);
 	}

	for ($i=0; $i <$limit ; $i++) { 
		$run = $i + 1;
		otp_msg("Run $run of $limit");
		otputils_get_subtitle_data($db, $video_list_download[$i]);
	}
} else {
	# Running in individual get mode
	otputils_get_subtitle_data($db, $video_id);
}

# End of script, close DB
$db->close();
otputils_cleanup();

?>