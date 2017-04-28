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
	echo "<pre>";
}

function ppre() {
	echo "</pre>";
}

function body(){
	echo "<body>";
}

function pbody(){
	echo "</body>";
}

function html(){
	echo "<html>";
}

function phtml(){
	echo "</html>";
}

function html_print_header($title=NULL){
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	echo '<html xmlns = "http://www.w3.org/1999/xhtml">';
	echo '<head>';
	echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
	echo "<title>$title</title>";
	echo '</head>';
}




?>