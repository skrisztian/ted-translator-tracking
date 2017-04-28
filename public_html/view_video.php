<?php

require_once "pageutils.php";
require_once "htmlutils.php";

if(empty($_GET['id'])) {
	pageutils_clean_die();
} else {
	$video_id = $_GET['id'];
}

$db = pageutils_open_db();
pageutils_prepare_for_sql($db, $video_id, $video_id_sql);
$sql = "SELECT * FROM otp_subtitles WHERE video_id=$video_id_sql";

if ($result = $db->query($sql)) {
	$row = $result->fetch_assoc();
	$result->free();
} else {
	pageutils_log('page_error', $db->error .'line '.__LINE__);
}

# Print document headers for the web page
html_print_header($row['title_orig'] .' | Subtitle data');

# Document body starts here
body();

echo "<h1>".pageutils_check_empty($row['title_orig'], 'Noname video')."</h1>"; 

# Print document headers for the web page

?>
<table>
	<tr>
		<th>Video id</th>
		<td><?php echo pageutils_record_link('video', $video_id); ?></td>
	</tr>
	<tr>
		<th>Original title</th>
		<td><?php echo pageutils_check_empty($row['title_orig']); ?></td>
	</tr>
	<tr>
		<th>Original subtitling started</th>
		<td><?php echo pageutils_check_empty($row['created_orig']); ?></td>
	</tr>
	<tr>
		<th>Hungarian title</th>
		<td><?php echo pageutils_check_empty($row['title_hu']); ?></td>
	</tr>
	<tr>
		<th>Hungarian subtitling started</th>
		<td><?php echo pageutils_check_empty($row['created_hu']); ?></td>
	</tr>
	<tr>
		<th>Is this an original subtitle?</th>
		<td><?php echo pageutils_check_bool($row['is_original']); ?></td>
	</tr>
	<tr>
		<th>Is this a translated subtitle?</th>
		<td><?php echo pageutils_check_bool($row['is_translation']); ?></td>
	</tr>
	<tr>
		<th>Translated subtitle language</th>
		<td><?php echo pageutils_check_empty($row['language_code']); ?></td>
	</tr>
	<tr>
		<th>Original subtitle language</th>
		<td><?php echo pageutils_check_empty($row['original_language_code']); ?></td>
	</tr>
	<tr>
		<th>Speaker name</th>
		<td><?php echo pageutils_check_empty($row['speaker_name']); ?></td>
	</tr>
	<tr>
		<th>Number of Hungarian subtitle versions</th>
		<td><?php echo pageutils_check_empty($row['num_versions']); ?></td>
	</tr>
	<tr>
		<th>Number of subtitle lines</th>
		<td><?php echo pageutils_check_empty($row['subtitle_count']); ?></td>
	</tr>
	<tr>
		<th>Are Hungarian subtitles complete?</th>
		<td><?php echo pageutils_check_bool($row['subtitles_complete']); ?></td>
	</tr>
	<tr>
		<th>Are Hungarian subtitles visible?</th>
		<td><?php echo pageutils_check_bool($row['visible']); ?></td>
	</tr>
	<tr>
		<th>Video duration [hh:mm:ss]</th>
		<td><?php echo gmdate('H:i:s', pageutils_check_empty($row['duration'], 0)); ?></td>
	</tr>
	<tr>
		<th>Project</th>
		<td><?php echo pageutils_check_empty($row['project']); ?></td>
	</tr>
	<tr>
		<th>Translator</th>
		<td>
			<?php 
			if (!empty($row['translator'])) {
				echo pageutils_name_link($db, $row['translator']);
			} else {
				echo '<i>not set</i>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th>Credited as Translator on TED</th>
		<td>
			<?php 
			if (!empty($row['ted_translator'])) {
				echo pageutils_ted_name_link($db, $row['ted_translator']);
			} else {
				echo '<i>not set</i>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th>Reviewer</th>
		<td>
			<?php 
			if (!empty($row['reviewer'])) {
				echo pageutils_name_link($db, $row['reviewer']);
			} else {
				echo '<i>not set</i>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th>Credited as Reviewer on TED</th>
		<td>
			<?php 
			if (!empty($row['ted_reviewer'])) {
				echo pageutils_ted_name_link($db, $row['ted_translator']);
			} else {
				echo '<i>not set</i>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th>Approver</th>
		<td>
			<?php 
			if (!empty($row['approver'])) {
				echo pageutils_name_link($db, $row['approver']);
			} else {
				echo '<i>not set</i>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th>Translated video on TED</th>
		<td>
			<?php 
			if(!empty($row['ted_link'])) {
				echo '<a href="http://www.ted.com/talks/'. $row['ted_link'] .'?language=hu" target="_blank" class="foreign_link" title="Open video on TED.com">'
					.'www.ted.com/talks/'. $row['ted_link'] .'?language=hu</a>';
			} else {
				echo '<i>not set</i>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th>Translated transcript on TED</th>
		<td>
			<?php 
			if(!empty($row['ted_link'])) {
				echo '<a href="http://www.ted.com/talks/'. $row['ted_link'] .'/transcript?lang=hu" target="_blank" class="foreign_link" title="Open video on TED.com">'
					.'www.ted.com/talks/'. $row['ted_link'] .'/transcript?lang=hu</a>';
			} else {
				echo '<i>not set</i>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th>Revisions on Amara</th>
		<td>
		<?php 
		if(!empty($row['created_hu'])) {
			echo '<a href="http://amara.org/en/videos/'. $row['video_id'] .'/hu/'. $row['id'] .'/?tab=revisions" target="_blank" title="Open revision history on Amara.org" class="foreign_link">'
				.'amara.org/en/videos/'. $row['video_id'] .'/hu/'. $row['id'] .'/?tab=revisions</a>';
		} else {
			echo '<i>not set</i>';
		}
		?>
		</td>
	</tr>
	<tr>
		<th>Latest subtitles on Amara</th>
		<td>
		<?php 
		if(!empty($row['created_hu'])) {
			echo '<a href="http://amara.org/en/videos/'. $row['video_id'] .'/hu/'. $row['id'] .'" target="_blank" title="Open the latest subtitles on Amara.org" class="foreign_link">'
				.'amara.org/en/videos/'. $row['video_id'] .'/hu/'. $row['id'] .'</a>';
		} else {
			echo '<i>not set</i>';
		}
		?>
		</td>
	</tr>
	<tr>
		<th>Post-edit this subtitle on Amara</th>
		<td>
		<?php 
		if(!empty($row['created_hu'])) {
			echo '<a href="http://amara.org/en/subtitles/editor/'. $row['video_id'] .'/hu/" target="_blank" title="Post-edit this subtitle on Amara.org" class="foreign_link">'
				.'amara.org/en/subtitles/editor/'. $row['video_id'] .'/hu/</a>';
			# old editor link: href="http://amara.org/en/subtitles/old-editor/'. $row['video_id'] .'/hu/"
			# new editor link e.g.: http://amara.org/en/subtitles/editor/SoXjX8uxMDbY/hu/
		} else {
			echo '<i>not set</i>';
		}
		?>
		</td>
	</tr>
	<tr>
		<th>Subtitle Amara record ID</th>
		<td><?php echo pageutils_check_empty($row['id']); ?></td>
	</tr>
	<tr>
		<th>Subtitle data last updated</th>
		<td><?php echo pageutils_check_empty($row['last_update']); ?></td>
	</tr>
	
</table>

<h2>Subtitle history</h2>

<table id="table_1" class="tablesorter">
	<thead>
	<tr>
		<th>Version number</th>
		<th>Translator</th>
		<th>Is this the published version?</th>
		<th>Text change (ratio)</th>
		<th>Text change (number of lines)</th>
		<th>Time change (ratio)</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$sql = "SELECT * FROM otp_subtitle_versions WHERE video_id=$video_id_sql ORDER BY version_no DESC";
	if ($result = $db->query($sql)) {
		while ($row2 = $result->fetch_assoc()) {
			echo '<tr>';
			echo '<td>';
			echo pageutils_check_empty($row2['version_no']);
			echo '</td>';
			echo '<td>';
			echo pageutils_name_link($db, $row2['author']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_bool($row2['published']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row2['text_change']);
			echo '</td>';
			echo '<td>';
			echo round($row2['text_change']*$row['subtitle_count']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row2['time_change']);
			echo '</td>';
			echo '</tr>';
		}
		$result->free();
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}
	?>
	</tbody>
</table>

<h2>Task history</h2>

<table id="table_2">
	<thead>
	<tr>
		<th>Task id</th>
		<th>Completed date</th>
		<th>Task type</th>
		<th>Approved status</th>
		<th>Assignee</th>

	</tr>
	</thead>
	<tbody>
	<?php
	$sql = "SELECT t.*, c.name FROM otp_tasks t LEFT OUTER JOIN otp_task_codes c ON t.approved=c.id WHERE t.video_id=$video_id_sql ORDER BY (t.completed IS NULL) DESC, t.completed DESC";
	if ($result = $db->query($sql)) {
		while ($row3 = $result->fetch_assoc()) {
			echo '<tr>';
			echo '<td>';
			echo pageutils_record_link('task', $row3['id']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row3['completed']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row3['type']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row3['name']);
			echo '</td>';
			echo '<td>';
			echo pageutils_name_link($db, $row3['assignee']);
			echo '</td>';
			echo '</tr>';
		}
		$result->free();
	} else {
		pageutils_log('page_error', $db->error .'line '.__LINE__);
	}
	?>
	</tbody>
</table>

<h2>Activity history</h2>

<table id="table_3" class="tablesorter">
	<thead>
	<tr>
		<th>Activity id</th>
		<th>Created date</th>
		<th>Language</th>
		<th>Activity type</th>
		<th>Translator</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$sql = "SELECT a.*, c.name FROM otp_activity a LEFT OUTER JOIN otp_activity_codes c ON a.type=c.id WHERE a.video_id=$video_id_sql ORDER BY a.created DESC";
	if ($result = $db->query($sql)) {
		while ($row4 = $result->fetch_assoc()) {
			echo '<tr>';
			echo '<td>';
			echo pageutils_record_link('activity', $row4['id']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row4['created']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row4['language']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row4['name']);
			echo '</td>';
			echo '<td>';
			echo pageutils_name_link($db, $row4['user']);
			echo '</td>';
			echo '</tr>';
		}
		$result->free();
	} else {
		pageutils_log('page_error', $db->error .'line '.__LINE__);
	}
	?>
	</tbody>
</table>

<h2>Errors</h2>

<table id="table_4" class="tablesorter">
	<thead>
	<tr>
		<th>Reported on</th>
		<th>Reported by</th>
		<th>Error code</th>
		<th>Error description</th>
		<th>Additional details</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$sql = "SELECT e.*, t.*, u.name 
			FROM otp_errors e 
			LEFT OUTER JOIN otp_error_types t 
			ON e.error_code=t.error_code
	     	LEFT OUTER JOIN otp_users u
	     	ON e.user=u.username 
	     	WHERE e.object_id_type='video_id'
	     		AND e.object_id=$video_id_sql ORDER BY e.last_update DESC";

	if ($result = $db->query($sql)) {
		while ($row5 = $result->fetch_assoc()) {
			echo '<tr>';
			echo '<td>';
			echo pageutils_check_empty($row5['last_update']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row5['name'], $row5['user']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row5['error_code']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row5['error_description']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row5['error_text']);
			echo '</td>';
			echo '</tr>';
		}
		$result->free();
	} else {
		pageutils_log('page_error', $db->error .'line '.__LINE__);
	}
	?>
	</tbody>
</table>

<h2>Comments by LCs on this subtitle job</h2>

<table id="table_5" class="tablesorter">
	<thead>
	<tr>
		<th>Added on</th>
		<th>Added by</th>
		<th>Comment</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$sql = "SELECT c.*, u.name FROM otp_comments c LEFT OUTER JOIN otp_users u ON c.user=u.username "
	     . "WHERE c.video_id=$video_id_sql ORDER BY c.date DESC";
	if ($result = $db->query($sql)) {
		while ($row6 = $result->fetch_assoc()) {
			echo '<tr>';
			echo '<td>';
			echo pageutils_check_empty($row6['date']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row6['name']);
			echo '</td>';
			echo '<td>';
			echo pageutils_check_empty($row6['comment']);
			echo '</td>';
			echo '</tr>';
		}
		$result->free();
	} else {
		pageutils_log('page_error', $db->error .'line '.__LINE__);
	}
	?>
	</tbody>
</table>

<p>-- end of page --</p>

<script type="text/javascript">
	$(document).ready(function() {
		<?php for ($i=1; $i < 6 ; $i++) { 
			echo '$(\'#table_'. $i .'\').tablesorter();';
		}?>
	});
</script>


<?php 
	$db->close();
	pbody();
	phtml();
	pageutils_cleanup(); 
?>
