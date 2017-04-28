<?php

require_once "pageutils.php";
require_once "htmlutils.php";

if(empty($_GET['type']) || empty($_GET['id'])) {
	pageutils_clean_die();
} else {
	switch ($_GET['type']) {
		case 'translator':
			$table = 'otp_translators';
			$column = 'amara_id';
			break;
		case 'video':
			$table = 'otp_subtitles';
			$column = 'video_id';
			break;
		case 'task':
			$table = 'otp_tasks';
			$column = 'id';
			break;
		case 'activity':
			$table = 'otp_activity';
			$column = 'id';
			break;
		case 'comment':
			$table = 'otp_comments';
			$column = 'comment_id';
			break;
		case 'error':
			$table = 'otp_errors';
			$column = 'id';
			break;
		case 'language':
			$table = 'otp_languages';
			$column = 'language_id';
			break;
		case 'otpuser':
			# check auth level, only admin should see this for others
			# but everyone can see their own data
			if ((!empty($_SESSION['otpweb_auth_level']) && $_SESSION['otpweb_auth_level']) || ($_GET['id'] == $_SESSION['otpweb_auth_username'])) {
				$table = 'otp_users';
				$column = 'username';
			} else {
				echo 'Sorry, you are not authorized to view this.';
				pageutils_cleanup();
				exit;
			}
			break;
		case 'ted_translator':
			$table = 'ted_translators';
			$column = 'ted_id';
			break;
		case 'task_code':
			$table = 'otp_task_codes';
			$column = 'id';
			break;
		case 'activity_code':
			$table = 'otp_activity_codes';
			$column = 'id';
			break;
		case 'error_code':
			$table = 'otp_error_types';
			$column = 'error_code';
			break;
		case 'subtitle_version':
			$table = 'otp_subtitle_versions';
			$column = 'video_id';
			break;
		default:
			pageutils_clean_die();
			break;
		}
}

$db = pageutils_open_db();
pageutils_prepare_for_sql($db, $_GET['id'], $id_sql);
$sql = "SELECT * FROM $table WHERE $column=$id_sql";

if ($result = $db->query($sql)) {
	$row = $result->fetch_assoc();
	$result->free();
} else {
	pageutils_log('page_error', $db->error .'line '.__LINE__);
}

$title = 'Record data for '. $_GET['type'] .' '. $_GET['id'];

# Print document headers for the web page
html_print_header($title);

# Document body starts here
body();

if ($row) {
	echo "<h1>$title</h1>"; 
	echo '<table>';
	foreach ($row as $key => $value) {
		echo '<tr>';
		echo '<th>';
		echo $key;
		echo '</th>';
		echo '<td>';
		echo pageutils_check_empty($value);
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
}

if ($_GET['type'] == 'task' || $_GET['type'] == 'activity') {
	echo '<p></p>';
	echo '<button type="button" id="amara_button" onclick="callAmaraApi()">Check values on Amara</button>';
	echo '<div id="api_return"></div>';


	# jQuery script
	echo '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js">';
	echo '</script>';

	echo '<script>';
	echo 'function callAmaraApi() {';
	echo '$.get("call_amara_api.php", { type: "'. $_GET['type'] .'", id: "'. $_GET['id'] .'" }, function( data ) {
		  document.getElementById("api_return").innerHTML = data; document.getElementById("amara_button").style.display = "none";} );';
	echo '}';
	echo '</script>';
}

pbody();
phtml();

$db->close();
pageutils_cleanup(); 

?>
