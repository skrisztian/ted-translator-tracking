<?php
# file name: update_db_activity.php
# version: 1.0
# date: 20 Feb 2014
# author: Krisztian Stancz
#
# Fills the otp_subtitles table by downloading
# all specified task entries through the Amara API.
# Should be run as a cron job.
# ---------------------------------------
# Usage: update_db_subtitles.php video_id
# ---------------------------------------

# test video id
# gz45APvPa9xJ
# BfCUWIEXN7Bc

include "otputils.php";
include "htmlutils.php";

otputils_prepare();
otputils_set_msg_log("data_update");
otputils_set_msg_log_continuous();

$sql_list = array();
$p = otputils_db_params();
$video_id = '7cmWKcdeloZx';

# Get video ID from the command line parameter
if ((isset($argc)) && ($argc > 1)) {
	$video_id = $argv[1];
}

# First, run two API queries for the given video ID. 
if (isset($video_id)) {

	# Connect to  Amara API, to get subtitle data
	$api_dir = "/videos/$video_id/languages/hu/";
	$api_return = call_amara_api($api_dir);
	if (is_string($api_return)) {
		# There was an error using the API
		# Show the HTTP or CURL error code
		otp_error("Amara API connection error ". $api_return . " table: subtitles, video_id: $video_id");
	}
	else {
		otp_msg("Connected to Amara API. HTTP 200 table: subtitles, video_id: $video_id");
		$subs_data = $api_return;

		# Run second Amara API query to get basic video data

		$api_dir = "/videos/$video_id/";
		$api_return = call_amara_api($api_dir);
		if (is_string($api_return)) {
			# There was an error using the API
			# Show the HTTP or CURL error code
			otp_error("Amara API connection error ". $api_return . " table: videos, video_id: $video_id");
		}
		else {
			otp_msg("Connected to Amara API. HTTP 200 table: videos, video_id: $video_id");
			
			# Insert some items into $subs_data
			$subs_data['title_orig'] = $api_return['title'];
   			$subs_data['duration'] = $api_return['duration'];
			$subs_data['project'] = $api_return['project'];
			$subs_data['created_orig'] = $api_return['created'];

			# certain fields might be missing 
			# we need to add them, to escape sql errors
			if (!array_key_exists('reviewer', $subs_data)) {
				$subs_data['reviewer'] = null;
			} 
			elseif (!array_key_exists('approver', $subs_data)) {
				$subs_data['approver'] = null;
			}
			elseif (!array_key_exists('title', $subs_data)) {
				$subs_data['title'] = null;
			}
			elseif (!array_key_exists('speaker-name', $subs_data['metadata'])) {
				$subs_data['metadata']['speaker-name'] = null;
			}

			if ($subs_data['project'] == 'tedtalks') {
				# Find out the video link on TED by searching for the title 
				$title = urlencode($subs_data['title_orig']);
				$title = '"'.$title.'"';
				$url = 'http://www.ted.com/search?cat=ss_talks&q=' . $title;
				
				# Download the page, extract the link
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				$response_body = curl_exec($ch);
				$response_info = curl_getinfo($ch);
				$curl_error = curl_errno($ch);
				curl_close ($ch); 

				if ($curl_error == 0) {
					if (intval($response_info['http_code']) == 200) {
						otp_msg('Connected to TED webpage HTTP 200 page: search');
						$response_body = mb_convert_encoding($response_body, 'html-entities', 'utf-8'); 
						$dom = new DOMDocument;
						$dom->validateOnParse = true;
						@$dom->loadHTML($response_body);
						$dom->preserveWhiteSpace = false;
						$ted_link_list = array();
						foreach($dom->getElementById('content')->getElementsByTagName('a') as $a) {
							$href = $a->getAttribute('href');
							if (!in_array($href, $ted_link_list)) {
								$ted_link_list[] = $href;
							}
						}
						# Update query array with the link
						if (count($ted_link_list) == 1) {
							preg_match('/^.*\/(.*)$/', $ted_link_list[0], $match);
							$ted_link = $match[1];
							otp_msg('Found 1 match for TED video link');
						}
						else {
							$ted_link = '';
							otp_error('Found '. count($ted_link_list) .' matches for TED video link');
						}
					} 
					else {
						otp_error('Connection failed to connect to TED webpage HTTP '.$response_info['http_code'].' page: search');
					}	
				}
				else {
					$ted_link = '';
					otp_error('Connection failed to TED webpage CURL '. curl_errno($ch) .' page: search');
				}
			}
			else {
				$ted_link = null;
			}
			
			# Generate sql statements

			$sql = 'INSERT INTO otp_subtitles (video_id, id, created_hu, is_original, is_translation, language_code, speaker_name, '
			     . 'num_versions, official_signoff_count, original_language_code, reviewer, approver, subtitle_count, subtitles_complete, '
				 . 'title_hu, title_orig, duration, project, created_orig, ted_link) '
				 . 'VALUES ('
				 . '"' . $video_id  .'", '
				 . $subs_data['id'] .', '
				 . '"' . $subs_data['created'] .'", '
				 . ($subs_data['is_original']?1:0) .', '
				 . ($subs_data['is_translation']?1:0) .', '
 				 . '"' . $subs_data['language_code'] .'", '
				 . '"' . $subs_data['metadata']['speaker-name'] .'", '
				 . $subs_data['num_versions'] .', '
				 . $subs_data['official_signoff_count'] .', '
				 . '"' . $subs_data['original_language_code'] .'", '
				 . '"' . $subs_data['reviewer'] .'", '
				 . '"' . $subs_data['approver'] .'", '
				 . $subs_data['subtitle_count'] .', '
				 . ($subs_data['subtitles_complete']?1:0) .', '
				 . '"' . $subs_data['title'] .'", ' 
				 . '"' . $subs_data['title_orig'] .'", '
				 . $subs_data['duration'] .', '
				 . '"' . $subs_data['project'] .'", '
				 . '"' . $subs_data['created_orig'] .'", '
 				 . '"' . $ted_link .'") '
				 . 'ON DUPLICATE KEY UPDATE id=VALUES(id), created_hu=VALUES(created_hu), num_versions=VALUES(num_versions), '
				 . 'official_signoff_count=VALUES(official_signoff_count), reviewer=VALUES(reviewer), approver=VALUES(approver), ' 
				 . 'subtitle_count=VALUES(subtitle_count), subtitles_complete=VALUES(subtitles_complete), title_hu=VALUES(title_hu), '
				 . 'ted_link=VALUES(ted_link)';
			
			foreach ($subs_data['versions'] as $num => $data) {
				$sql_list[] = '("'. $video_id .'", "'. $data['author'] .'", "'. ($data['published']?1:0) .'", '.
						      $data['text_change'] .', '. $data['time_change'] .', '. $data['version_no'] .')';
			}			

			if (count($sql_list) > 0) {
				$sql2 = 'INSERT INTO otp_subtitle_versions (video_id, author, published, text_change, time_change, version_no) VALUES ' .implode(',', $sql_list) .' ON DUPLICATE KEY UPDATE published=VALUES(published)';
			}

			# Update DB

			$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
			if($db->connect_errno > 0){
				otp_error($db->connect_error.' line '.__LINE__);
			}
			else {
				$db->query("set names 'utf8'");
				if($db->query($sql)) {
					otp_msg("Rows uploaded/updated in otp_subtitles table: ". $db->affected_rows); 
				}
				else {
					otp_error($db->error.' line '.__LINE__);
				}
				
				if (isset($sql2)) {
					if  ($db->query($sql2)) {
						otp_msg("Rows uploaded/updated in otp_subtitle_versions table: ". $db->affected_rows); 
					}
					else {
						otp_error($db->error.' line '.__LINE__);
					}
				}
				$db->close();
			}
		}
	}	
}
else
{
	otp_msg("There was nothing to update in otp_subtitles table: video_id: $video_id");			
}

otputils_cleanup();
?>