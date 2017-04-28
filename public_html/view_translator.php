<?php 

require_once "pageutils.php";
require_once "htmlutils.php";

if(empty($_GET['id'])) {
	pageutils_clean_die();
} else {
	$amara_id = $_GET['id'];
}

$db = pageutils_open_db();

pageutils_prepare_for_sql($db, $amara_id, $amara_id_sql);
$sql = "SELECT * FROM otp_translators WHERE amara_id=$amara_id_sql";

if ($result = $db->query($sql)) {
	$row = $result->fetch_assoc();
	$result->free();
} else {
	pageutils_log('page_error', $db->error .' line '.__LINE__);
}

# Print document headers for the web page
html_print_header($row['full_name']);

# Document body starts here
body();

echo "<h1>".pageutils_check_empty($row['full_name'], 'Noname Translator')."</h1>"; 
?>

<h2>Translator data</h2>
<table border="1">
	<tr>
		<th>Full name:</th>
		<td><?php echo pageutils_check_empty($row['full_name']); ?></td>
	</tr>
	<tr>
		<th>First name:</th>
		<td><?php echo pageutils_check_empty($row['first_name']); ?></td>
	</tr>
	<tr>
		<th>Last name:</th>
		<td><?php echo pageutils_check_empty($row['last_name']); ?></td>
	</tr>
	<tr>
		<th>Role:</th>
		<td><?php echo pageutils_check_empty($row['amara_role']); ?></td>
	</tr>
	<tr>
		<th>Member of TED team on Amara:</th>
		<td><?php echo (pageutils_check_empty($row['amara_ted_member'])?'yes':'no'); ?></td>
	</tr>
	<tr>
		<th>Amara ID:</th>
		<td><a href="http://amara.org/en/profiles/profile/<?php echo urlencode($amara_id);?>" target="_blank" title="Open this translator's profile on Amara.org" class="foreign_link">
			<?php echo $amara_id; ?></a>
		</td>
	</tr>
	<tr>
		<th>TED ID:</th>
		<td>
			<?php 
			if(isset($row['ted_id'])) {
				echo '<a href="http://ted.com/profiles/'.$row['ted_id'].'/translator" target="_blank" title="Open this translator\'s profile on TED.com" class="foreign_link">';
				echo $row['ted_id'];
				echo '</a>';
			} else {
				echo '<i>not set</i>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th>Languages:</th>
		<td>
			<ul>
				<?php
				$sql = "SELECT otp_languages.language_name FROM otp_languages INNER JOIN otp_translator_languages ON "
				     . "otp_languages.language_id=otp_translator_languages.language_id WHERE otp_translator_languages.amara_id=$amara_id_sql";

				if ($result = $db->query($sql)) {
					if ($result->num_rows == 0) {
						echo '<i>not set</i>'; 
					} else {
						while ($row1 = $result->fetch_assoc()) {
							echo '<li>'.$row1['language_name'];
						}
					}
					$result->free();
				} else {
					pageutils_log('page_error', $db->error .' line '.__LINE__);
				}
				?>
			</ul>
		</td>
	</tr>
	<tr>
		<th>Last completed work:</th>
		<td>
			<?php
			$sql = "SELECT MAX(completed) FROM otp_tasks 
					WHERE assignee=$amara_id_sql 
					AND completed IS NOT NULL";
			if ($result = $db->query($sql)) {
				$last_work = $result->fetch_row()[0];
				$result->free();
			} else {
				pageutils_log('page_error', $db->error .' line '.__LINE__);
			}

			if ($last_work) {
				echo pageutils_time_diff($last_work), ' ago';
			} else {
				echo '<i>Never</i>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th>User data last updated:</th>
		<td><?php echo pageutils_check_empty($row['last_update']); ?></td>
	</tr>
</table>

<h2>Tools</h2>
<table>
	<tr>
		<th>View current tasks on Amara</th>
		<td>
			<a href="http://amara.org/en/teams/ted/tasks/?project=any&lang=all&assignee=<?php echo urlencode($amara_id);?>" target="_blank", title="Open current task list of this translator on Amara.org" class="foreign_link">
			amara.org/en/teams/ted/tasks/?project=any&lang=all&assignee=<?php echo urlencode($amara_id);?></a>
		</td>
	</tr>
	<tr>
		<th>Send message on Amara</th>
		<td>
			<a href="http://amara.org/en/messages/new/?user=<?php echo urlencode($amara_id);?>" target="_blank" title="Send message on Amara.org to this translator" class="foreign_link">
			amara.org/en/messages/new/?user=<?php echo urlencode($amara_id);?></a>
		</td>
	</tr>
	</tr>
</table>


<h2>Currently working on</h2>

<?php
$sql = "SELECT last_update FROM otp_api_meta WHERE total_count_name='task_hu'";
if ($result = $db->query($sql)) {
	$task_last_updated = $result->fetch_row()[0];
	$result->free();
} else {
	pageutils_log('page_error', $db->error .' line '.__LINE__);
}
?>

<p>Last updated <?php echo pageutils_time_diff($task_last_updated); ?> ago. (Updates normally run hourly.)</p>

<table id="table_1" class="tablesorter">
	<thead>
	<tr>
		<th>Id</th>
		<th>Completed</th>
		<th>Type</th>
		<th>Language</th>
		<th>Status</th>
		<th>Video id</th>
		<th>Video title</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$sql = "SELECT t.*, c.name, s.title_orig  FROM otp_tasks t LEFT OUTER JOIN otp_task_codes c "
		 . "ON t.approved=c.id LEFT OUTER JOIN otp_subtitles s ON t.video_id=s.video_id "
		 . "WHERE t.assignee=$amara_id_sql AND t.completed IS NULL ORDER BY t.id DESC";
	if ($result = $db->query($sql)) {
		while ($row = $result->fetch_assoc()) {
		echo '<tr>';
		echo '<td>'. pageutils_record_link('task', $row['id']) .'</td>';
		echo '<td>'. pageutils_check_empty($row['completed']) .'</td>';
		echo '<td>'. $row['type'] .'</td>';
		echo '<td>'. pageutils_check_empty($row['language']) .'</td>';
		echo '<td>'. pageutils_check_empty($row['name']) .'</td>';
		if (isset($row['video_id'])) {
			echo '<td>' .pageutils_view_link('video', $row['video_id']) .'</td>';
		} else {
			echo '<td>'. pageutils_check_empty($row['video_id']) .'</td>';
		}
		echo '<td>'. pageutils_check_empty($row['title_orig']) .'</td>';
		echo '</tr>';
	}
		$result->free();
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}
	?>
	</tbody>
</table>

<h2>Tasks history</h2>
<table id="table_2" class="tablesorter">
	<thead>
	<tr>
		<th>Id</th>
		<th>Completed</th>
		<th>Type</th>
		<th>Language</th>
		<th>Status</th>
		<th>Video id</th>
		<th>Video title</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$sql = "SELECT t.*, c.name, s.title_orig  FROM otp_tasks t LEFT OUTER JOIN otp_task_codes c "
		 . "ON t.approved=c.id LEFT OUTER JOIN otp_subtitles s ON t.video_id=s.video_id "
		 . "WHERE t.assignee=$amara_id_sql AND t.completed IS NOT NULL ORDER BY t.completed DESC";
	if ($result = $db->query($sql)) {
		while ($row = $result->fetch_assoc()) {
		echo '<tr>';
		echo '<td>'. pageutils_view_link('task', $row['id'], 'record') .'</td>';
		echo '<td>'. pageutils_check_bool($row['completed']) .'</td>';
		echo '<td>'. $row['type'] .'</td>';
		echo '<td>'. pageutils_check_empty($row['language']) .'</td>';
		echo '<td>'. pageutils_check_empty($row['name']) .'</td>';
		if (isset($row['video_id'])) {
			echo '<td>'. pageutils_view_link('video', $row['video_id']) .'</td>';
		} else {
			echo '<td>'. pageutils_check_empty($row['video_id']) .'</td>';
		}
		echo '<td>'. pageutils_check_empty($row['title_orig']) .'</td>';
		echo '</tr>';
	}
		$result->free();
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}
	?>
	</tbody>
</table>

<h2>Activity history</h2>
<table id="table_3" class="tablesorter">
	<thead>
	<tr>
		<th>id</th>
		<th>date</th>		
		<th>activity</th>
		<th>language</th>
		<th>video id</th>
		<th>subtitle id</th>		
		<th>video title</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$sql = "SELECT a.*, c.name, s.title_orig FROM otp_activity a LEFT OUTER JOIN otp_activity_codes c "
		 . "ON a.type=c.id LEFT OUTER JOIN otp_subtitles s ON a.video_id=s.video_id "
 		 . "WHERE a.user=$amara_id_sql ORDER BY a.created DESC";
	if ($result = $db->query($sql)) {
		while ($row = $result->fetch_assoc()) {
		echo '<tr>';
		echo '<td>'. pageutils_view_link('activity', $row['id'], 'record') .'</td>';
		echo '<td>'. $row['created'] .'</td>';
		echo '<td>'. pageutils_check_empty($row['name']) .'</td>';
		echo '<td>'. pageutils_check_empty($row['language']) .'</td>';
		if (isset($row['video_id'])) {
			echo '<td>'. pageutils_view_link('video', $row['video_id']) .'</td>';
		} else {
			echo '<td>'. pageutils_check_empty($row['video_id']) .'</td>';
		}
		if (isset($row['language_url'])) {
			preg_match('/^.*\/hu\/(\d*)\//', $row['language_url'], $match_r3);
			echo '<td><a href="http://amara.org'. $row['language_url'] .'?tab=revisions" target="_blank" title="Open revision list on Amara.org for this subtitle" class="foreign_link">'. $match_r3[1] .'</a></td>';
		} else {
			echo '<td>'. pageutils_check_empty($row['language_url']) .'</td>';
		}
		echo '<td>'. pageutils_check_empty($row['title_orig']) .'</td>';
		echo '</tr>';
	}
		$result->free();
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}
	?>
	</tbody>
</table>
<p>-- end of page --</p>

<script type="text/javascript">
	$(document).ready(function() {
		<?php for ($i=1; $i < 4 ; $i++) { 
			echo '$(\'#table_'. $i .'\').tablesorter();';
		}?>
	});
</script>

<?php

$db->close();

# End of document
pbody();
phtml();
pageutils_cleanup();

?>