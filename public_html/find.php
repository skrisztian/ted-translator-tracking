<?php

require_once "pageutils.php";
require_once "htmlutils.php";


if (empty($_GET['find'])) {
	pageutils_clean_die(); 
} else {
	$findstring = $_GET['find'];
}

# Print document headers for the web page
html_print_header("Search results");

# Document body starts here
body();

echo '<h1>Search results</h1>';
echo '<p id="hits">Searching...</p>';

echo '<div id="results_all">';

echo '<div id = "jumper">';
echo '<ul>';
echo '<li>Jump to:</li>';
echo '<li><a href="#Translators">Translators</a></li>';
echo '<li><a href="#Pre-Amara Translators">Pre-Amara Translators</a></li>';
echo '<li><a href="#Subtitles">Subtitles</a></li>';
echo '<li><a href="#Tasks">Tasks</a></li>';
echo '<li><a href="#Activity">Activity</a></li>';
echo '<li><a href="#LC Comments">LC Comments</a></li>';
echo '</ul>';
echo '</div>';

$table_count = 0;
$db = pageutils_open_db();
pageutils_prepare_for_sql($db, $_GET['find'], $find);
if (is_numeric($find)) {
	# For numbers pageutils_prepare_for_sql returns integer or float types, without quotes
	$find = "'%". $find ."%'";
} else {
	# For strings it returns the string surrounded with single quotes
	$find = preg_replace('/^\'/', '\'%', $find);
	$find = preg_replace('/\'$/', '%\'', $find);
}
$hits = 0;

# Search in translator table
$header = 'Translators';
$sql = "SELECT amara_id, ted_id, full_name, first_name, last_name, amara_role 
		FROM otp_translators 
		WHERE amara_id LIKE $find 
			OR ted_id LIKE $find 
			OR full_name LIKE $find  
			OR first_name LIKE $find 
			OR last_name  LIKE $find 
		ORDER BY full_name ASC"; 
$hits += print_table($db, $sql, $header, 'translator');

# Search in TED Translator table
$header = 'Pre-Amara Translators';
$sql = "SELECT ted_id, ted_full_name 
		FROM ted_translators 
		WHERE ted_id LIKE $find 
			OR ted_full_name LIKE $find 
		ORDER BY ted_full_name ASC"; 
$hits += print_table($db, $sql, $header, 'ted_translator');

# Search in subtitles table
$header = 'Subtitles';
$sql = "SELECT video_id, id, speaker_name, reviewer, approver, title_orig, title_hu, ted_link, ted_translator, ted_reviewer  
		FROM otp_subtitles 
		WHERE video_id LIKE $find 
			OR id LIKE $find 
			OR speaker_name LIKE $find 
			OR reviewer LIKE $find 
			OR approver LIKE $find 			
			OR title_hu LIKE $find 			
			OR title_orig LIKE $find 			
			OR ted_link LIKE $find 			
			OR ted_translator LIKE $find 			
			OR ted_reviewer LIKE $find 			
		ORDER BY video_id DESC"; 
$hits += print_table($db, $sql, $header, 'video');

# Search in tasks table
$header = 'Tasks';
$sql = "SELECT id, assignee, video_id 
		FROM otp_tasks 
		WHERE id LIKE $find 
			OR assignee LIKE $find 
			OR video_id LIKE $find 
		ORDER BY id DESC"; 
$hits += print_table($db, $sql, $header, 'task');

# Search in activity table
$header = 'Activity';
$sql = "SELECT id, user, video_id, language_url 
		FROM otp_activity 
		WHERE id LIKE $find 
			OR user LIKE $find 
			OR video_id LIKE $find 
			OR language_url LIKE $find 
		ORDER BY id DESC"; 
$hits += print_table($db, $sql, $header, 'activity');

# Search in comments table
$header = 'LC Comments';
$sql = "SELECT c.comment_id, c.amara_id, c.video_id, u.amara_id, c.user , c.comment 
		FROM otp_comments c
		LEFT OUTER JOIN otp_users u ON c.user = u.username
		WHERE c.comment_id LIKE $find 
			OR c.user LIKE $find 
			OR c.video_id LIKE $find 
			OR c.amara_id LIKE $find 
			OR c.comment LIKE $find 			
		ORDER BY c.comment_id DESC"; 
$hits += print_table($db, $sql, $header, 'comment');

echo '</div>';
echo "<p>-- end of page --</p>";

?>
<!-- After counting all records update top of page with javascript -->
<script type="text/javascript">
	$(document).ready(function() {
		document.getElementById("hits").innerHTML="Found <b><?php echo $hits; ?></b> hits for <b><?php echo $_GET['find']; ?></b>. Matches are highlighted with yellow.";
		<?php
		for ($i=1; $i < $table_count+1 ; $i++) { 
		echo '$(\'#table_'. $i .'\').tablesorter();';
		}
		?>
	  	document.getElementById("results_all").style.display="block";
	  	$('td').removeHighlight().highlight('<?php echo $_GET['find']; ?>');
	});
</script>

<?php 

pbody();
phtml();

$db->close();
pageutils_cleanup(); 

# End of page

// --------------------------- Functions ---------------------------------

function print_table($db, $sql, $header, $linktype) {
	global $table_count;

	$table_count++;

	echo '<a name="'. $header .'"></a>'; 
	echo "<h2>$header</h2>";
	echo '<div class="results">';
	$i = 0;
	if ($result = $db->query($sql)) {
		echo '<table id="table_'. $table_count .'" class="tablesort">';	
		while ($row = $result->fetch_assoc()) {
			# Print table header too for first run
			if ($i == 0) {
				echo '<thead>';
				echo '<tr>';		
				foreach ($row as $key => $value) {
					if (!($linktype == 'comment' && $key == 3)) {
						echo "<th>$key</th>";
					}
				}
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
			}
			# Print the rest of the info
			# Print link in every first cell
			$j = 0;
			$user_amara_id = null;
			echo '<tr>';
			foreach ($row as $key => $value) {
				echo '<td>';
				if ($j == 0) {
					if ($linktype == 'task' || $linktype == 'activity' || $linktype == 'comment') {
						echo pageutils_view_link($linktype, $value, 'record_link');
					} else {
						echo pageutils_view_link($linktype, $value);
					}
				} elseif ($j == 1 && ($linktype == 'task' || $linktype == 'activity' || $linktype == 'comment')) {
					echo pageutils_view_link('translator', $value);
				} elseif ($j == 2 && ($linktype == 'task' || $linktype == 'activity' || $linktype == 'comment')) {
					echo pageutils_view_link('video', $value);
				} elseif ($j == 3 && $linktype == 'comment') {
					$user_amara_id = $value;
				} elseif ($j == 4 && $linktype == 'comment') {
					echo pageutils_view_link('translator', $user_amara_id, null, $value);
					$user_amara_id = null;
				} else {
					echo pageutils_check_empty($value);
				}
				echo '</td>';
				$j++;	
			}
			echo '</tr>';
			$i++;
		}
		echo '</tbody>';
		echo '</table>';	
		echo '</div>';
		$result->free();
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}
	return $i;
}

?>