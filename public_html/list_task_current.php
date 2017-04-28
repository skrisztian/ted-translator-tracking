<?php

require_once "pageutils.php";
require_once "htmlutils.php";

# Print document headers for the web page
html_print_header("Task overview");

# Document body starts here
body();

$db = pageutils_open_db();
$table_count = 0;

echo '<h1>What is going on?</h1>';

$header = 'Ongoing approvals';
$sql = "SELECT t.*, tr.full_name, s.title_orig, c.name FROM otp_tasks t 
		LEFT OUTER JOIN otp_translators tr ON t.assignee=tr.amara_id 
		LEFT OUTER JOIN otp_subtitles s ON t.video_id=s.video_id 
		LEFT OUTER JOIN otp_task_codes c ON t.approved=c.id 
		WHERE t.type='Approve' AND t.completed IS NULL AND t.assignee IS NOT NULL 
		ORDER BY t.id DESC";
print_table($db, $sql, $header);

$header = 'Finished review, waiting for approval';
$sql = "SELECT t.*, tr.full_name, s.title_orig, c.name FROM otp_tasks t 
		LEFT OUTER JOIN otp_translators tr ON t.assignee=tr.amara_id 
		LEFT OUTER JOIN otp_subtitles s ON t.video_id=s.video_id 
		LEFT OUTER JOIN otp_task_codes c ON t.approved=c.id 
		WHERE t.type='Approve' AND t.completed IS NULL AND t.assignee IS NULL 
		ORDER BY t.id DESC";
print_table($db, $sql, $header);
echo '<p><a href="http://amara.org/en/teams/ted/tasks/?project=any&type=Approve&lang=hu" target="_blank" 
	  class="foreign_link" title="Open approval queue on Amara">Pick up an approval task on Amara</a></p>';

$header = 'Ongoing review';
$sql = "SELECT t.*, tr.full_name, s.title_orig, c.name FROM otp_tasks t 
		LEFT OUTER JOIN otp_translators tr ON t.assignee=tr.amara_id 
		LEFT OUTER JOIN otp_subtitles s ON t.video_id=s.video_id 
		LEFT OUTER JOIN otp_task_codes c ON t.approved=c.id 
		WHERE t.type='Review' AND t.completed IS NULL AND t.assignee IS NOT NULL 
		ORDER BY t.id DESC";
print_table($db, $sql, $header);

$header = 'Finished translation/transcription, waiting for review';
$sql = "SELECT t.*, tr.full_name, s.title_orig, c.name FROM otp_tasks t 
		LEFT OUTER JOIN otp_translators tr ON t.assignee=tr.amara_id 
		LEFT OUTER JOIN otp_subtitles s ON t.video_id=s.video_id 
		LEFT OUTER JOIN otp_task_codes c ON t.approved=c.id 
		WHERE t.type='Review' AND t.completed IS NULL AND t.assignee IS NULL 
		ORDER BY t.id DESC";
print_table($db, $sql, $header);
echo '<p><a href="http://amara.org/en/teams/ted/tasks/?project=any&type=Review&lang=hu" target="_blank" 
	  class="foreign_link" title="Open review queue on Amara">Pick up a review task on Amara</a></p>';

$header = 'Ongoing translation';
$sql = "SELECT t.*, tr.full_name, s.title_orig, c.name FROM otp_tasks t 
		LEFT OUTER JOIN otp_translators tr ON t.assignee=tr.amara_id 
		LEFT OUTER JOIN otp_subtitles s ON t.video_id=s.video_id 
		LEFT OUTER JOIN otp_task_codes c ON t.approved=c.id 
		WHERE t.type='Translate' AND t.completed IS NULL AND t.assignee IS NOT NULL 
		ORDER BY t.id DESC";
print_table($db, $sql, $header);

$header = 'Ongoing subtitling (transcribing)';
$sql = "SELECT t.*, tr.full_name, s.title_orig, c.name FROM otp_tasks t 
		LEFT OUTER JOIN otp_translators tr ON t.assignee=tr.amara_id 
		LEFT OUTER JOIN otp_subtitles s ON t.video_id=s.video_id 
		LEFT OUTER JOIN otp_task_codes c ON t.approved=c.id 
		WHERE t.type='Subtitle' AND t.completed IS NULL AND t.assignee IS NOT NULL 
		ORDER BY t.id DESC";
print_table($db, $sql, $header);

$header = 'Published in the last 1 month';
$sql = "SELECT t.*, tr.full_name, s.title_orig, c.name 
		FROM otp_tasks t 
		LEFT OUTER JOIN otp_translators tr ON t.assignee=tr.amara_id 
		LEFT OUTER JOIN otp_subtitles s ON t.video_id=s.video_id 
		LEFT OUTER JOIN otp_task_codes c ON t.approved=c.id 
		WHERE t.type='Approve' 
			AND t.completed IS NOT NULL 
			AND t.assignee IS NOT NULL 
			AND t.completed > DATE_SUB(NOW(), INTERVAL 1 MONTH) 
		ORDER BY t.completed DESC";
print_table($db, $sql, $header);

echo '<p>-- end of page --</p>';

$db->close();

# Sorting java script
echo '<script type="text/javascript">';
echo '$(document).ready(function() { ';
for ($i=1; $i < $table_count+1 ; $i++) { 
	echo '$(\'#table_'. $i .'\').tablesorter();';
}
echo '});';
echo '</script>';

# End of document body
pbody();
phtml();
pageutils_cleanup();

# End of main

//------------------ FUNCTION DEFINITIONS -----------------------//

function print_table($db, $sql, $header) {
	global $table_count;

	$table_count++;

	echo "<h2>$header</h2>";
	echo '<table id="table_'. $table_count .'" class="tablesorter">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>Task id</th>';
	echo '<th>Completed date</th>';
	echo '<th>Type</th>';	
	echo '<th>Language</th>';
	echo '<th>Status</th>';
	echo '<th>Worked by</th>';
	echo '<th>Video id</th>';
	echo '<th>Video title</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';

	if ($result = $db->query($sql)) {
		while($row = $result->fetch_assoc()) {
			echo '<tr>';
			echo '<td>';
			echo pageutils_view_link('task', $row['id'], 'record');
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row['completed']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row['type']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row['language']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row['name']);
			echo '</td>';
			echo '<td>';
			if (isset($row['assignee'])) {
				echo pageutils_view_link('translator', $row['assignee'], null, $row['full_name']);
			} else {
				echo '<i>not set</i>';
			}
			echo '</td>';
			echo '<td>';
			echo pageutils_view_link('video', $row['video_id']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row['title_orig']);
			echo '</td>';
			echo '</tr>';
		}
		$result->free();
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}
	echo '</tbody>';
	echo '</table>';
}


?>