<?php

require_once "pageutils.php";
require_once "htmlutils.php";

if(empty($_GET['type'])) {
	pageutils_clean_die();
} else {
	$type = $_GET['type'];
}

switch ($type) {
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
		# check auth level, only admin should see this
		if (!empty($_SESSION['otpweb_auth_level']) && $_SESSION['otpweb_auth_level']) {
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


# Print document headers for the web page
$title = 'List of all '. $_GET['type'] .' records';
html_print_header($title);

# Document body starts here
body();
echo "<h1>$title</h1>", PHP_EOL;


$db = pageutils_open_db();
$sql = "SELECT * FROM $table ORDER BY $column DESC";
$i = 0;
if ($result = $db->query($sql)) {
	echo '<table id="table_1" class="tablesorter">';	
	while ($row = $result->fetch_assoc()) {
		# Print table header too on first run
		if ($i == 0) {
			echo '<thead>';
			echo '<tr>';		
			foreach ($row as $key => $value) {
				echo "<th>$key</th>";
			}
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
		}
		# Print the rest of the info
		# Print link in every first cell
		echo '<tr>';
		foreach ($row as $key => $value) {
			echo '<td>';
			if ($key == $column) {
				echo pageutils_view_link($type, $value, 'record');
			} else {
				echo pageutils_check_empty($value);
			}
			echo '</td>';
		}
		echo '</tr>';
		$i++;
	}
	echo '</tbody>';
	echo '</table>';
	$result->free();
} else {
	pageutils_log('page_error', $db->error .' line '.__LINE__);
}

$db->close();

if ($i > 0) {
	$i--;
}

echo "<p>Total records: $i</p>";
echo "<p>-- end of page --</p>";

# Sorting java script
echo '<script type="text/javascript">';
echo '$(document).ready(function() { ';
echo '$(\'#table_1\').tablesorter();';
echo '});';
echo '</script>';

pbody();
phtml();
pageutils_cleanup(); 

?>
