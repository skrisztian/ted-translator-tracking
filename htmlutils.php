<?php
# file name: html_utils.php
# version: 1.0
# date: 12 Feb 2014
# author: Krisztian Stancz
#
# Collection of functions that make easier
# to create html elements.

#
# Print entire line with end of line character
#
function println($line="") {
	echo $line, PHP_EOL;
}
	
function pre(){
	echo "<PRE>";
}

function ppre() {
	echo "</PRE>";
}

?>