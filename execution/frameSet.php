<?php

////////////////////////////////////////////////////////////////////////////////
//File:     frameSet.php
//Author:   Chad Rosen
//Purpose:  This is the main frame page that displays the javascript tree 
//          on the left and the execution page in the center
////////////////////////////////////////////////////////////////////////////////


require_once("../functions/header.php");

  session_start();
  doDBConnect();
  doHeader();

	//builds the frame page

	echo "<frameset rows='70,*' frameborder='NO' border='0' framespacing='0'>";
  
	echo "<frame src='navBar.php?type=project&nav=" . $_GET['nav'] . "' name='topFrame' scrolling='NO' noresize MARGINWIDTH=1 MARGINHEIGHT=1>";

	echo "<frameset cols='35%,*' frameborder='NO' border='0' framespacing='0'>\n\n";
		
	//The left frame should use executionFrameLeft.php
	//I pass the frame the build the user chose from the build select page as well as the detailed or quick page

	//echo "<frame src='execution/executionFrameLeft.php?&page=" . $_GET['page'] . "' name='leftFrame' noresize scrolling='yes'>\n\n";
	
	
	//If the user is comming from the metrics page then I want to show these line that automatically show what they've selected

	if($_GET['tc'])
	{

		echo "<frame src='execution/executionFrameLeft.php?keyword=None&build=" . $_GET['build'] . "&page=" . $_GET['page'] . "' name='leftFrame' noresize scrolling='yes'>\n\n";

		echo "<frame src='execution/execution.php?keyword=None&page=detailed&build=" . $_GET['build'] . "&edit=testcase&tc=" . $_GET['tc'] . "' name='mainFrame'>\n\n";

	}
	
	if($_GET['com'])
	{

		
		echo "<frame src='execution/executionFrameLeft.php?keyword=None&build=" . $_GET['build'] . "&page=" . $_GET['page'] . "' name='leftFrame' noresize scrolling='yes'>\n\n";

		echo "<frame src='execution/execution.php?keyword=None&page=detailed&build=" . $_GET['build'] . "&edit=component&com=" . $_GET['com'] . "' name='mainFrame'>\n\n";

	}

	if($_GET['cat'])
	{
		
		echo "<frame src='execution/executionFrameLeft.php?keyword=None&build=" . $_GET['build'] . "&page=" . $_GET['page'] . "' name='leftFrame' noresize scrolling='yes'>\n\n";

		echo "<frame src='execution/execution.php?keyword=None&page=detailed&build=" . $_GET['build'] . "&edit=category&cat=" . $_GET['cat'] . "' name='mainFrame'>\n\n";

	}
	
	//Else I just show the info page

	else
	{
		
		echo "<frame src='execution/executionFrameLeft.php?page=" . $_GET['page'] . "' name='leftFrame' scrolling='yes'>\n\n";

		echo "<frame src='execution/execution.php?edit=info' name='mainFrame'>\n\n";
	
	}
	echo "</frameset>'";


?>
