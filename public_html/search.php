<?php

require_once "pageutils.php";
require_once "htmlutils.php";

# Print document headers for the web page
html_print_header("Search");

# Document body starts here
body();

/*



<form action = "find.php" method="get" id="1">
<table>
	<tr>
		<td>Translator</td>
		<td><input type="text" name="translator" size="40"></td>
	</tr>
	<tr>
		<td>Video</td>
		<td><input type="text" name="video" size="40"></td>
	</tr>
		<tr>
		<td>Task</td>
		<td><input type="text" name="task" size="40"></td>
	</tr>
	<tr>
		<td>Activity</td>
		<td><input type="text" name="activity" size="40"></td>
	</tr>
</table>
<input type="submit" value="search">
</form>

*/

?>
<h1>Search</h1>
<form action = "find.php" method="get" id="2">
<table>
	<tr>
		<td>Anything</td>
		<td><input type="text" name="find" size="40"></td>
	</tr>
</table>
<input type="submit" value="search">
</form>

<?php

# End of document body
pbody();
phtml();
pageutils_cleanup();

?>
