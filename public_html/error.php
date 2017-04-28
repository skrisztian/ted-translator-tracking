<?php

require_once "pageutils.php";
require_once "htmlutils.php";

# Print document headers for the web page
html_print_header("Error");

# Document body starts here
body();
?>

<h1>Oops!</h1>

<p>Something unexpectedly terrible happened. Or expectedly normal. Or just a piece of bad code. Anyway, we'll look it up in the logs and fix it soon.</p>

<?php

# End of document body
pbody();
phtml();
pageutils_cleanup();

?>