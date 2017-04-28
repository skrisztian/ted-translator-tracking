<?php
require_once 'pageutils.php';

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


$filename = "otpdata_$type.csv";
$db = pageutils_open_db();
$sql = "SELECT * FROM $table ORDER BY $column";
$header = true;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment;filename="'. $filename .'"');
header('Cache-Control: max-age=0');

$out = fopen('php://output', 'w');

if ($result = $db->query($sql)) {
	while ($row = $result -> fetch_assoc()) {
	    if ($header) {
			$titles = array();
			foreach ($row as $key=>$val) {
				$titles[] = $key;
		    }
			fputcsv($out, $titles, ';');
			$header = false;
		}
		fputcsv($out, $row, ';');
	}
	$result->free();
} else {
	pageutils_log('page_error', $db->error .' line '.__LINE__);
}

fclose($out);
pageutils_cleanup();

?>