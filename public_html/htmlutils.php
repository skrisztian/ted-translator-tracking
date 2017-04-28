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
	echo '<body>';
	include_once("analyticstracking.php");
	# This will print the page menu in the page header
	echo '<div id="top_menu">';
	echo '<ul>';
	echo '<li><a href="index.php">Main page</a></li>';
	echo '<li><a href="list_translator.php">Translators</a></li>';
	echo '<li><a href="list_video.php">Subtitles</a></li>';
	echo '<li><a href="list_task_current.php">Status</a></li>';
	echo '<li><a id="search_link" href="javascript:showSearchField()">Search</a></li>';
	echo '<li><form id="search_form" action ="find.php" method="get" id="search">
			<input type="text" name="find" size="20" id="search_text_field" />
			<input type="submit" value="search" />
		  </form></li>';
	echo '<li id="logout_link">', html_print_logout_link(), '</li>';
	echo '</ul>';
	echo '</div>';
	
	# This is the jQerty script searchFiled which hides the links and shos the text field with a button
	echo '<script type="text/javascript">';
	echo 'function showSearchField() {
			document.getElementById("search_link").style.display="none";
			document.getElementById("search_form").style.display="block";
			document.getElementById("search_text_field").focus();
		};';
	echo '</script>';

	echo '<div id="otp_logo"><img src="images/otpedia-logo.png" alt="" /></div>';

	# Beginning of content area
	echo '<div id="page_content">';
}

function pbody(){
	echo '</div>'; # end of div id='content'
	echo "</body>";
}

function html(){
	echo "<html>";
}

function phtml(){
	echo "</html>";
}

function html_print_header($title=NULL, $plot=null){
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	echo '<html xmlns = "http://www.w3.org/1999/xhtml">';
	echo '<head>';
	echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
	echo '<link rel="stylesheet" type="text/css" href="css/otp.css" />';
	if ($plot) {
		echo '<link rel="stylesheet" type="text/css" href="css/jquery.jqplot.min.css" />';
	}

	echo '<script type="text/javascript" src="src/jquery-1.11.0.min.js"></script>';
	echo '<script type="text/javascript" src="src/jquery.highlight-4.closure.js"></script>';
	echo '<script type="text/javascript" src="src/jquery.tablesorter.min.js"></script>';
	if ($plot) {
		echo '<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="src/excanvas.min.js"></script><![endif]-->';
		echo '<script language="javascript" type="text/javascript" src="src/jquery.jqplot.min.js"></script>';
	}
	echo "<title>$title</title>";
	echo '</head>';
}

function html_print_logout_link() {
	$location_l = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$location_l = preg_replace('/\?.*$/', '', $location_l);
	echo '<a href="http://'. $location_l .'?logout">Log out</a>';
}

?>