<?php
# file name: update_translators.php
# version: 1.0
# date: 12 Feb 2014
# author: Krisztian Stancz
#
# Fills in amara_id and updates amara_role in the otp_translators table,
# updates languages selected by the translators in the translator_languages
# table.
# Should be run as a cron job.

include "otputils.php";

otputils_prepare();
otputils_set_msg_log("data_update");

$page = 1;
$last_page = 1;
$page_nos = array();
$translator_data = array();
$amara_ids_db = array();
$user_langs_web = array();
$user_langs_db = array();
$unique_web_langs = array();
$sql_list = array(); 

$insert_lang_count = 0;
$delete_lang_count = 0;
$update_user_count = 0;

# Download Amara TED Team members page filtered for Hungarian speakers.
# Iterate through all pages, in the first run, we will first figure out how many
# pages there are actually
while ($page <= $last_page){
	$url = "http://www.amara.org/en/teams/ted/members/?lang=hu&page=$page";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$response_body = curl_exec($ch);
	$response_info = curl_getinfo($ch);
	curl_close ($ch); 
	
	if ((curl_errno($ch) == 0) && (intval($response_info['http_code']) == 200)) {
		$response_body = mb_convert_encoding($response_body, 'html-entities', 'utf-8'); 
		$dom = new DOMDocument;
		$dom->validateOnParse = true;
		if (@$dom->loadHTML($response_body)) {
			$dom->preserveWhiteSpace = false;
			otp_msg('Connected to Amara webpage HTTP 200 page: hu translator team');
			
			if ($page == 1) {
				# Find the last page number
				foreach ($dom->getElementsByTagName('div') as $div) {
					if (preg_match('/pagination/', $div->getAttribute('class'))) {
						foreach ($div->getElementsByTagName('a') as $link) {
							$page_no = $link->nodeValue;
							settype($page_no, "integer");
							array_push($page_nos, $page_no);
						}
					}
				}
				# This is the last page number:
				$last_page = max($page_nos);
				otp_msg('Last page: '.$last_page);
			}

			# Get the data we need from the DOM tree
			foreach ($dom->getElementsByTagName('ul') as $ul) {
				# These <UL> tags denote list of translators
				if (preg_match('/members listing/', $ul->getAttribute('class'))) {
					# Inside the <UL> there are 3 <LI tags>. We only need the 1st one,
					# so with a counter we ignore the rest.
					$counter = 1;
					foreach ($ul->getElementsByTagName('li') as $li) {
						if (($counter + 2) % 3 == 0) {
							foreach ($li->getElementsByTagName('a') as $link) {
								preg_match("/^.*\/profile\/(.*)\/$/", $link->getAttribute('href'), $match_array);
								if ((count($match_array) > 1) && !($link->hasAttribute('class'))) {
									# Amara ID of translator
									$amara_id = trim(urldecode($match_array[1]));
									# Full name of translator
									$full_name = trim(urldecode($link->nodeValue));
								}
							}
							$languages = array();
							foreach ($li->getElementsByTagName('span') as $span) {
								if (preg_match('/descriptor/', $span->getAttribute('class'))) {
									# List of languages
									array_push($languages, trim($span->nodeValue));
								}
							}
							foreach ($li->getElementsByTagName('p') as $p) {
								$string = $p->nodeValue;
								if (preg_match('/contributor/i', $p->nodeValue)) {
									$role = "contributor";
								}
								elseif (preg_match('/owner/i', $p->nodeValue)) {
									$role = "owner";
								}
								# There is some weird formatting in html which makes 
								# preg_match('/manager.*hungarian/i', $string) not working.
								elseif (preg_match('/manager/i', $string) && preg_match('/hungarian/i', $string)) {
									$role = "manager";
								}
								else {
									$role = "contributor";
								}
							}
							$translator_data[$amara_id] = array($full_name, $role, $languages);
						}
					$counter++;
					
					}
				
				}
			}
			$page++;
		}
	}
	else {
		if (curl_errno($ch) > 0) {
			otp_error('Amara webpage connection error CURL '.curl_errno($ch). ' page: hu translator team');
		}
		elseif (intval($response_info['http_code']) != 200) {
			otp_error('Amara webpage connection error HTTP '.$response_info['http_code']. ' page: hu translator team');
		}
		else {
			otp_error('Amara webpage connection error. Unknown error source. page: hu translator team');
		}
	}
}

################### TRANSLATORS ##################
$p = otputils_db_params();
if (count($translator_data) > 0) {
	$db = new mysqli($p[0], $p[1], $p[2], $p[3]);
	if($db->connect_errno > 0){
		otp_error($db->connect_error.' line '.__LINE__);
	}
	else {
		$db->query("set names 'utf8'");
		$sql_list = array(); 
		foreach ($translator_data as $amara_id_web => $web_data) {
			# Upload amara_id, full_name, amara_role
			$sql_list[] = '("'. $amara_id_web .'", "'. $web_data[0] .'", "'. $web_data[1] .'")';
		}
		$sql = 'INSERT INTO otp_translators (amara_id, full_name, amara_role) VALUES ' .implode(',', $sql_list)
               .' ON DUPLICATE KEY UPDATE full_name=VALUES(full_name),amara_role=VALUES(amara_role)';
		if ($db->query($sql)) {
			otp_msg('Updated '. $db->affected_rows .' rows in otp_translators table');
		}
		else {
			otp_error$db->error.' line '.__LINE__);
		}
			
		################### TRANSLATOR LANGUAGES #######################

		# Get the current languages of translators from the database
		# columns: amara_id, language_id

		$sql = 'SELECT * FROM otp_translator_languages';
		if ($result = $db->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				# Filter those names out, which are not in the web list
				# to prevent deleting entries accidentally in case of a html connection error
				if(array_key_exists($row['amara_id'], $translator_data)) {
					$user_langs_db[] = $row['amara_id'].'=>'.$row['language_id'];
				}
			}
			$result->free();
		}
		else {
			otp_error($db->error.' line '.__LINE__);
		}		

		# Get a list of unique languages we just
		# harvested from the web.
		$sql_list = array();
		foreach ($translator_data as $amara_id_web => $web_data) {
			foreach($web_data[2] as $num => $lang_name) {
				if (!array_key_exists($lang_name, $unique_web_langs)) {
					$unique_web_langs[$lang_name] = NULL;
					$sql_list[] = '"'.$lang_name.'"';
				}
			}
		}
			
		# Get the language id for the long language name into $unique_web_langs
		# columns: language_id, language_name
		$sql = "SELECT * FROM otp.otp_languages WHERE language_name IN (" .implode(',', $sql_list) .")";
		if ($result = $db->query($sql)) {
			while ($row_lang = $result ->fetch_assoc()) {
				$unique_web_langs[$row_lang['language_name']] = $row_lang['language_id'];
			}
			$result->free();
		}
		else {
			otp_error($db->error.' line '.__LINE__);
		}
	
		# Prepare the list of translators->languages from the web
		# To transform the long name of the language to its ID, 
		# we'll look it up in $unique_web_langs['language_name'] = 'language_id'
		foreach ($translator_data as $amara_id_web => $web_data) {
			foreach($web_data[2] as $num => $lang_name) {
				$user_langs_web[] = $amara_id_web.'=>'.$unique_web_langs[$lang_name];
			}
		}

		# INSERT translator languages into DB 
		$insert_langs = array_diff($user_langs_web, $user_langs_db);
		if (count($insert_langs) > 0) {
			$sql_list = array();
			foreach ($insert_langs as $num => $value) {
				# $value: 'amara_user=>lang_id'
				preg_match('/(.*)=>(.*)$/', $value, $match);
				$sql_list[] = '("'.$match[1].'","'.$match[2].'")';
			}
			$sql = 'INSERT INTO otp_translator_languages (amara_id, language_id) VALUES ' .implode(',', $sql_list);
			if($db->query($sql)) {
				otp_msg('Uploaded ' . $db->affected_rows .' rows into translator_languages table');
			}
			else {
				otp_error($db->error.' line '.__LINE__);
			}
		}

		# DELETE translator languages from DB
		$delete_langs = array_diff($user_langs_db, $user_langs_web);
		if (count($delete_langs) > 0) {
			$sql_list = array();
			foreach ($delete_langs as $num => $value) {
				# $value: 'amara_user=>lang_id'
				preg_match('/(.*)=>(.*)$/', $value, $match);
				$sql_list[] = '("'.$match[1].'","'.$match[2].'")';
			}
			$sql = 'DELETE FROM otp_translator_languages WHERE (amara_id, language_id) IN (' .implode(',', $sql_list) .')';
			if ($db->query($sql)) {
				otp_msg('Deleted ' . $db->affected_rows .' rows from translator_languages table');
			}
			else {
				otp_error($db->error.' line '.__LINE__);
			}
		}
	}
	$db->close();
}
else {
	otp_msg('Nothing to update');
}

otputils_cleanup();
?>