<?php
# file name: update_db_subtitles_ted_credit.php
# version: 1.0
# date: 18 Mar 2014
# author: Krisztian Stancz
#
# Updates the otp_subtitles table with the 
# translator and reviewer name retreived from the TED
# talk/transcript page.
# Should be run as a cron job.
# ----------------------------------------------------
# Usage: update_db_subtitles_ted_credit.php [video_id]
# ----------------------------------------------------

# test video_id
# gz45APvPa9xJ
# BfCUWIEXN7Bc

include "otputils.php";
include "htmlutils.php";

otputils_prepare();
otputils_set_msg_log("data_update");
otputils_set_msg_log_continuous();

$video_list_table = array();
$video_list_subtitles = array();
$video_list_download = array();
$sql_list = array();
$p = otputils_db_params();

$limit = otputils_set_bulk_limit();

# Get video ID from the command line parameter
if ((isset($argc)) && ($argc > 1)) {
	$video_id = $argv[1];
	$bulk_get = false;
	otp_msg('Running TED credit update in single get mode');
} else {
	$bulk_get = true;
	otp_msg('Running TED credit update in bulk get mode');
}

# Get data for the update from otp_subtitles table
$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
if($db->connect_errno > 0){
	otp_error($db->connect_error.' line '.__LINE__);
	otputils_die();
} else {
	$db->query("set names 'utf8'");

	if ($bulk_get) {
	# Running in bulk get mode
		$sql = "SELECT video_id, ted_link FROM otp_subtitles WHERE (ted_translator IS NULL OR ted_reviewer IS NULL)
		        AND ted_link IS NOT NULL AND ted_link <> '' AND approver IS NOT NULL AND approver <> '' ORDER BY video_id";
	} else {
		# Running in individual get mode
		$sql = "SELECT video_id, ted_link FROM otp_subtitles WHERE video_id='$video_id'";
	}

	if ($result = $db->query($sql)) {
		while($row = $result->fetch_assoc()) {
			$video_list_table[] = array('video_id' => $row['video_id'], 'ted_link' => $row['ted_link']);
		}
		$record_count = $result->num_rows;
		$result->free();
	} else {
		otp_error($db->error.' line '.__LINE__);
	}
}

if (count($video_list_table) > 0) {
	if ($bulk_get) {
		# Running in bulk mode
		if ($record_count < $limit) {
			$limit = $record_count;
		}
		otp_msg('Video IDs to update TED credits: '.count($video_list_table));
		otp_msg("Limit: $limit");
		for ($i=0; $i<$limit; $i++) { 
			$run = $i + 1;
			otp_msg("Run $run of $limit");
			get_ted_credit($db, $video_list_table[$i]);
		}
	} else {
		# Running in individual get mode
		get_ted_credit($db, $video_list_table[0]);
	}
} else {
	if ($bulk_get) {
		otp_msg('DB query returned no video_ids to update');
	} else {
		otp_error("DB query could not find data in otp_subtitles for video id: $video_id");
	}
}

$db->close();
otputils_cleanup();

#########################
#  Function definition  #
#########################
 
function get_ted_credit($db, $video_params) {

	$video_id = $video_params['video_id'];
	$ted_link = $video_params['ted_link'];

	$url = 'http://www.ted.com/talks/';
	$url .= $ted_link;
	$url .= '/transcript?lang=hu';

	$getpage = otputils_get_dom($url);

	if ($getpage['dom']) {
		otp_msg('Connected to TED website: '. $getpage['status'] .' page: transcript, video_id: '. $video_id . ', link: ' . $url);

		# The translator/reviewr names are inside a <div class ="talk-article__header module"> ->
		# <p> -> a[1] and a[2] href attributes. If this changes on the page, this code needs update
		$xpath = new DOMXPath($getpage['dom']);
		$classname = 'talk-article__header module';

		# Check if we have all the credits
		$p_text = $xpath->query("//*[@class='" . $classname . "']/p")->item(0)->nodeValue;;
		$num_links = $xpath->query("//*[@class='" . $classname . "']/p/a")->length;

		if ($num_links == 0) {
			# No credits at all
			otp_error('No credits on TED page: transcript, video_id: ' .$video_id . ', link: ' . $url);
			otputils_log_error($db, 'robot', 'video_id', $video_id, 702);
			otputils_log_error($db, 'robot', 'video_id', $video_id, 703);
		} elseif ($num_links == 1) {
			# One credit only instead of two
			if (preg_match('/translated/i', $p_text)) {
				# Reviwer missing
				otp_error('No reviwer on TED page: transcript, video_id: ' .$video_id . ', link: ' . $url);
				otputils_log_error($db, 'robot', 'video_id', $video_id, 703);
				$person = 'translator';
			} elseif (preg_match('/reviewed/i', $p_text)) {
				otp_error('No translator on TED page: transcript, video_id: ' .$video_id . ', link: ' . $url);
				otputils_log_error($db, 'robot', 'video_id', $video_id, 702);
				$person = 'reviwer';
			} else {
				# This is weird. There is a link, but nor translator, nor reviewer
				otp_error('No credits on TED page: transcript, video_id: ' .$video_id . ', link: ' . $url);
				otputils_log_error($db, 'robot', 'video_id', $video_id, 702);
				otputils_log_error($db, 'robot', 'video_id', $video_id, 703);
			}
			$results_a = $xpath->query("//*[@class='" . $classname . "']/p/a");
			preg_match("/^.*\/profiles\/(\d*).*$/", $results_a->item(0)->getAttribute('href'), $match_array);
			$c_person[$person] = $match_array[1];
		} elseif ($num_links == 2) {
			foreach (array(1=>'translator', 2=>'reviewer') as $key => $value) {
				$results_a = $xpath->query("//*[@class='" . $classname . "']/p/a[$key]");
				preg_match("/^.*\/profiles\/(\d*).*$/", $results_a->item(0)->getAttribute('href'), $match_array);
				$c_person[$value] = $match_array[1];
			}
		} else {
			# Too many links
			otp_error('Too many credit links on TED page: transcript, video_id: ' .$video_id . ', link: ' . $url);
			otputils_log_error($db, 'robot', 'video_id', $video_id, 704);
		}
	} else {
		otp_error('Connection failed to TED webpage '. $getpage['status'] .' page: transcript, video_id: ' .$video_id . ', link: ' . $url);
		if ($getpage['status'] == 'HTTP 404') {
			# The Hungarian translation is not appearing on TED, although it seems to be published. Log an error.
			otputils_log_error($db, 'robot', 'video_id', $video_id, 701);
		}
	}

	# Update the otp_subtitle tables with the TED credit data
	@otputils_prepare_for_sql($db, $c_person['translator'], $credit['ted_translator']);
	@otputils_prepare_for_sql($db, $c_person['reviewer'], $credit['ted_reviewer']);
	@otputils_prepare_for_sql($db, $video_id, $credit['video_id']);

	# Generate sql statement
	$sql = "UPDATE otp_subtitles SET ted_translator=". $credit['ted_translator'] .", ted_reviewer=" 
	     . $credit['ted_reviewer'] ." WHERE video_id=" . $credit['video_id'];

	if($db->query($sql)) {
		otp_msg("Rows updated in otp_subtitles table for ted_credit: ". $db->affected_rows); 
	} else {
		otp_error($db->error.' line '.__LINE__);
	}
}

?>