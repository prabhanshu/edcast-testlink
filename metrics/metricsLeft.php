<?

////////////////////////////////////////////////////////////////////////////////
//File:     executionFrameLeft.php
//Author:   Chad Rosen
//Purpose:  This page is the left frame of the execution pages. It builds the
//	    javascript trees that allow the user to jump around to any point
//	    on the screen
////////////////////////////////////////////////////////////////////////////////



require_once("../functions/header.php");

  session_start();
  doDBConnect();
  doHeader();


?>

<script language='javascript' src='functions/popupHelp.js'></script>

<LINK REL="stylesheet" TYPE="text/css" HREF="kenny.css">


<?


//begin the code that displays the user's options

echo "<img align=top src=icons/sym_question.gif onclick=javascript:open_popup('../help/met_main.php');>";

echo "<table width='100%' class=mainTable>";

echo "<tr><td class=userinfotable ><a href='metrics/metricsSelection.php' target='mainFrame'>View Project Status Across All Builds</td></tr>";

echo "<tr><td class=userinfotable >View Status by an Individual Build";

echo "<FORM method='post' ACTION='metrics/buildDetail.php' target='mainFrame'>";

$result = mysql_query("select build from build,project where project.id = " . $_SESSION['project'] . " and build.projid = project.id order by build desc",$db);

echo "<SELECT NAME='build'>";
		
		while ($myrow = mysql_fetch_row($result)) 
		{
			echo "<OPTION VALUE='" . $myrow[0] ."'>" . $myrow[0];

		}//END WHILE

echo "</SELECT>";


?>

<input type='submit' NAME='submit' value='submit'></td></tr>

<tr><td class=userinfotable ><a href='metrics/allBuildMetrics.php' target='mainFrame'>View The Overall Build Status</td></tr>

<tr><td class=userinfotable ><a href='metrics/totalTestCase.php' target='mainFrame'>View Status By Individual Test Cases</td></tr>

<tr><td class=userinfotable ><a href='metrics/blockedFailedReport.php?type=b' target='mainFrame'>Blocked Test Cases</td></tr>

<tr><td class=userinfotable ><a href='metrics/blockedFailedReport.php?type=f' target='mainFrame'>Failed Test Cases</td></tr>

<tr><td class=userinfotable ><a href='metrics/bugDetail.php' target='mainFrame'>Total Bugs For Each Test Case</td></tr>

<tr><td class=userinfotable ><a href='metrics/emailData.php' target='mainFrame'>Email Test Plan Info</td></tr>

<tr><td class=userinfotable >&nbsp</td></tr>

<tr><td class=userinfotable ><a href='metrics/platform/percentComplete.php' target='mainFrame'>Percentage complete by platform / build</td></tr>

<tr><td class=userinfotable ><a href='metrics/platform/executionByPlatform.php' target='mainFrame'>Platform Metrics By Platform Container</td></tr>

<tr><td class=userinfotable ><a href='metrics/platform/notRunPlatformTestCases.php' target='mainFrame'>Test Cases not run in any platform</td></tr>

<tr><td class=userinfotable ><a href='metrics/platform/failingByPlatform.php' target='mainFrame'>Failing Test Cases by platform</td></tr>

<tr><td class=userinfotable ><a href='metrics/platform/detailedPlatform.php' target='mainFrame'>Platform Metrics By Component</td></tr>

</table>