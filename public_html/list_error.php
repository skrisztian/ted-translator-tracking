<?php

require_once "pageutils.php";
require_once "htmlutils.php";

# Print document headers for the web page
html_print_header("List of current subtitle errors");

# Document body starts here
body();

echo "<h1>Current subtitle errors</h1>";

# Open database
$db = pageutils_open_db();
$error_list = array();

# Get list of subtitle errors that the robot or users has picked up
$sql = "SELECT e.*, t.error_description, s.title_orig 
		FROM otp_errors e
		LEFT OUTER JOIN otp_error_types t
		ON e.error_code = t.error_code
		LEFT OUTER JOIN otp_subtitles s
		ON e.object_id = s.video_id
		WHERE e.object_id_type = 'video_id'
		ORDER BY e.object_id, e.error_code";

if ($result = $db->query($sql)) { 
	while($row = $result->fetch_assoc()) {
		$error_list[] = $row;
	}
} else {
	pageutils_log('page_error', $db->error .' line '.__LINE__);
}

# Now pour the array into a nicely formatted table
?>

<table id="table_1" class="tablesorter">
	<thead>
		<tr>
			<th>Id</th>
			<th>Video Id</th>
			<th>Title</th>
			<th>Error code</th>
			<th>Error</th>
			<th>Additional info</th>
			<th>Picked up by</th>
			<th>Last update</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($error_list as $key => $error_data): ?>
			<tr>
				<td><?php echo @pageutils_view_link('error', $error_data['id'], 'record', null); ?></td>
				<td><?php echo @pageutils_view_link('video', $error_data['object_id'], null, null); ?></td>
				<td><?php echo @pageutils_check_empty($error_data['title_orig']); ?></td>
				<td><?php echo @pageutils_check_empty($error_data['error_code']); ?></td>
				<td><?php echo @pageutils_check_empty($error_data['error_description']); ?></td>
				<td><?php echo @pageutils_check_empty($error_data['error_text']); ?></td>
				<td><?php echo @pageutils_check_empty($error_data['user']); ?></td>
				<td><?php echo @pageutils_check_empty($error_data['last_update']); ?></td>
			</tr>
		<?php endforeach ?>
	</tbody>
</table>

<p>Total: <b><?php echo count($error_list); ?></b> errors</p>
<p>-- End of page --</p>

<script type="text/javascript">
	$(document).ready(function() { 
		$('#table_1').tablesorter(); 
	});
</script>

<?php

$db->close();

# End of document body
pbody();
phtml();
pageutils_cleanup();

?>