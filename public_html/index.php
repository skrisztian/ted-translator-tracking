<?php

require "pageutils.php";
include "htmlutils.php";

# Print document headers for the web page
html_print_header("OTP Statistics Landing Page");

# Document body starts here
body();

?>

<h1>Home</h1>

<!-- Left column start -->
<div id="left_col">
<h2>Translators' data </h2>

<ul>
	<li><a href="list_translator_active.php">List of active translators</a></li>
	<li><a href="list_translator.php">List of translators</a></li>
	<li><a href="list_translator.php?filter=manager">List of language coordinators</a></li>
	<li><a href="list_translator_ted.php">List of translators without Amara ID</a></li>
</ul>

<h2>Subtitles</h2>

<ul>
	<li><a href="list_video.php">List of subtitles</a></li>
</ul>

<h2>What's going on?</h2>
<ul>
	<li><a href="list_task_current.php">Status of current approve, review, translate, subtitle activities</a>
	<li><a href="list_task.php">Status of all approve, review, translate subtitle activities</a>
</ul>

<h2>Statistics</h2>

<ul>
	<li><a href="stats_approval_activity.php">Approval and activity statistics</a>
</ul>

<h2>Subtitle problems</h2>

<ul>
	<li><a href="list_error.php">Crediting/appeareance/etc. errors of subtitles</a>
</ul>

</div>
<!-- End of left column -->

<!-- Right column start -->
<div id="right_col">

<h2>Database tools</h2>

<h3>Table data</h3>

<p>You can download the database tables for further statistical analysis. 
The column delimiter in the csv files is ';'. The text encoding is UTF-8.</p>

<table id="table_1">
	<tr>
		<td>Translators table</td>
		<td><a href="list_record.php?type=translator">View table</a></td>
		<td><a href="download_csv.php?type=translator">Download table as csv file</a></td>
	</tr>
	<tr>
		<td>Subtitles table</td>
		<td><a href="list_record.php?type=video">View table</a></td>
		<td><a href="download_csv.php?type=video">Download table as csv file</a></td>
	</tr>
		<td>Subtitle versions table</td>
		<td><a href="list_record.php?type=subtitle_version">View table</a></td>
		<td><a href="download_csv.php?type=subtitle_version">Download table as csv file</a></td>
	</tr>
	<tr>
		<td>Tasks table</td>
		<td><a href="list_record.php?type=task">View table</a></td>
		<td><a href="download_csv.php?type=task">Download table as csv file</a></td>
	</tr>
	<tr>
		<td>Activity table</td>
		<td><a href="list_record.php?type=activity">View table</a></td>
		<td><a href="download_csv.php?type=activity">Download table as csv file</a></td>
	</tr>
	<tr>
		<td>LC Comments table</td>
		<td><a href="list_record.php?type=comment">View table</a></td>
		<td><a href="download_csv.php?type=comment">Download table as csv file</a></td>
	</tr>
	<tr>
		<td>Page errors table</td>
		<td><a href="list_record.php?type=error">View table</a></td>
		<td><a href="download_csv.php?type=error">Download table as csv file</a></td>
	</tr>
	<tr>
		<td>Languages table</td>
		<td><a href="list_record.php?type=language">View table</a></td>
		<td><a href="download_csv.php?type=language">Download table as csv file</a></td>
	</tr>
	<tr>
		<td>TED Translators table</td>
		<td><a href="list_record.php?type=ted_translator">View table</a></td>
		<td><a href="download_csv.php?type=ted_translator">Download table as csv file</a></td>
	</tr>
	<tr>
		<td>Task codes table</td>
		<td><a href="list_record.php?type=task_code">View table</a></td>
		<td><a href="download_csv.php?type=task_code">Download table as csv file</a></td>
	</tr>
	<tr>
		<td>Activity codes table</td>
		<td><a href="list_record.php?type=activity_code">View table</a></td>
		<td><a href="download_csv.php?type=activity_code">Download table as csv file</a></td>
	</tr>
	<tr>
		<td>Error codes</td>
		<td><a href="list_record.php?type=error_code">View table</a></td>
		<td><a href="download_csv.php?type=error_code">Download table as csv file</a></td>
	</tr>
	<?php
	# Hide admin data
	if (!empty($_SESSION['otpweb_auth_level']) && $_SESSION['otpweb_auth_level']) {
		echo '<tr>';
		echo '<td>OTP DB users table*</td>';
		echo '<td><a href="list_record.php?type=otpuser">View table</a></td>';
		echo '<td><a href="download_csv.php?type=otpuser">Download table as csv file</a></td>';
		echo '</tr>';
	}
	?>
</table>

<h3>Log files</h3>

<p>These log files are used for debugging. They show how the automated processes are running.</p>

<table id="table_2">
	<tr>
		<td>Data update</td>
		<td><a href="view_log.php?log=data">View log file</a></td>
	</tr>
	<tr>
		<td>Script run</td>
		<td><a href="view_log.php?log=script">View log file</a></td>
	</tr>
	<tr>
		<td>Page errors</td>
		<td><a href="view_log.php?log=error">View log file</a></td>
	</tr>
	<?php
	# Hide admin data
	if (!empty($_SESSION['otpweb_auth_level']) && $_SESSION['otpweb_auth_level']) {
		echo '<tr><td>Authentication*</td><td><a href="view_log.php?log=auth">View log file</a></td></tr>';
		echo '<tr><td>Page access*</td><td><a href="view_log.php?log=access">View log file</a></td></tr>';
	}
	?>
</table>

<h3>User data</h3>

<p>See what <a href="view_record.php?type=otpuser&id=<?php echo $_SESSION['otpweb_auth_username'] ?>">user data</a> is stored about you.</p> 
</div>
<!-- End of right column -->

<?php
# End of document body
pbody();
phtml();
pageutils_cleanup();
?>