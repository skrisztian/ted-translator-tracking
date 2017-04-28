<?php

require_once "pageutils.php";
require_once "htmlutils.php";

# Print document headers for the web page
html_print_header("List of Unidentified Hungarian Translators");
body();

?>


<h1>Hungarian translators from TED without Amara account</h1>

<table id="table_1" class="tablesorter">
	<thead>
		<tr>
			<th>Name</th>
			<th>TED ID</th>
			<th>Last updated</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$db = pageutils_open_db();
		$sql = "SELECT * FROM ted_translators ORDER BY ted_full_name";
		if ($result = $db->query($sql)) {
			while($row = $result->fetch_assoc()) {
				echo '<tr>';
				echo '<td>'. pageutils_view_link('ted_translator', $row['ted_id'], null, $row['ted_full_name']) .'</td>';
				echo '<td>';
				echo '<a href="http://www.ted.com/profiles/'.$row['ted_id'].'/translator" title="Open profile page on ted.com" target="_blank" class="foreign_link">'. $row['ted_id'] .'</a>';
				echo '</td>';
				echo '<td>'. pageutils_check_empty($row['last_update']) .'</td>';
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
	$(document).ready(function() {$('#table_1').tablesorter();});
</script>

<?php
pbody();
phtml();
pageutils_cleanup();
?>