<?php

require_once "pageutils.php";
require_once "htmlutils.php";

# Print document headers for the web page
html_print_header("List of Subtitles");

# Body starts here
body();

?>

<h1>List of Subtitles</h1>
<table id="table_1" class="tablesorter">
	<thead>
		<tr>
			<th>Video id</th>
			<th>Created on</th>
			<th>Speaker name</th>
			<th>Original title</th>
			<th>Hungarian title</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$db = pageutils_open_db();
		$sql = "SELECT * FROM otp_subtitles ORDER BY created_orig DESC";

		if ($result = $db->query($sql)) {
			while($row = $result->fetch_assoc()) {
				echo '<tr>';
				echo '<td>', pageutils_view_link('video', $row['video_id']), '</td>';
				echo '<td>', pageutils_check_empty($row['created_orig']), '</td>';
				echo '<td>', pageutils_check_empty($row['speaker_name']), '</td>';
				echo '<td>', pageutils_check_empty($row['title_orig']), '</td>';
				echo '<td>', pageutils_check_empty($row['title_hu']), '</td>';
				echo '</tr>';
			}
			$result->free();
		} else {
			pageutils_log('page_error', $db->error .' line '.__LINE__);
		}
		$db->close();
		?>
	</tbody>
</table>

<script type="text/javascript">
	$(document).ready(function() {$('#table_1').tablesorter(); });
</script>

<?php

# End of document body
pbody();
phtml();
pageutils_cleanup();

?>