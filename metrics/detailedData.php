<?php

////////////////////////////////////////////////////////////////////////////////
//File:     detailedData.php
//Author:   Chad Rosen
//Purpose:  This page generates detailed data for a report.
////////////////////////////////////////////////////////////////////////////////


require_once("../functions/header.php");

  session_start();
  doDBConnect();
  doHeader();

?>

<LINK REL="stylesheet" TYPE="text/css" HREF="CommonStyles.css" TITLE="CommonStyles">

<?

echo "<a href='mainpage.php' target='_parent'>Back To Main Page</a> > <a href='metrics/metricsSelection.php' target='_parent'>Metrics Selection Page</a><br><br>";

if($_GET['edit'] == 'info')
{

echo "<table border=0 width=100%>";
echo "<tr><td align='center'><h2>Detailed Test Case Metrics</td></tr></table>";

echo "<table border=0 width=100%>";

echo "<tr><td bgcolor='#CCCCCC'><b>Purpose:</td><td bgcolor='#EEEEEE'>This Page allows the user to see all of the different build results for each test case</td></tr>";
echo "<tr><td bgcolor='#CCCCCC'><b>Getting Started:</td><td bgcolor='#EEEEEE'>Click on a component level to see all of its categories and their test cases. Clicking on a specific category shows only its test cases</td></tr>";
echo "</table>";


}

if($_GET['edit'] == 'component')
{

	$sqlCOM = "select component.name,component.id from project,component where project.id='" . $_SESSION['project'] . "' and component.projid=project.id and component.id='" . $_GET['com'] . "'";


	$resultCOM = mysql_query($sqlCOM);

	while ($myrowCOM = mysql_fetch_row($resultCOM)) //Cycle through all of the Components
	{

	echo "<table border='1' cellpadding='0' cellspacing='4' width='100%'>";

	echo "<tr><td bgcolor='#99CCFF' class='boldFont'>Component: " . $myrowCOM[0] . "</td></tr>";

	$sqlCAT = "select category.name,category.id from component,category where category.compid='" . $myrowCOM[1] . "' and component.id=category.compid";

	$resultCAT = mysql_query($sqlCAT); //Execute Query



	while ($myrowCAT = mysql_fetch_row($resultCAT)) //Cycle through all of the Categories
	{
	
		echo "<table border='1' cellpadding='0' cellspacing='4' width='100%'>";
		
		echo "<tr><td bgcolor='#FFFFCC' class='boldFont'>Category: " . $myrowCAT[0] . "</td></tr>";

		$sqlTC = "select testcase.title,testcase.id from testcase,category where testcase.catid='" . $myrowCAT[1] . "' and category.id=testcase.catid";

		$resultTC = mysql_query($sqlTC); //Execute Query

		
		while ($myrowTC = mysql_fetch_row($resultTC)) //Cycle through all of the test cases
		{
			
						
			echo "<table border='1' cellpadding='0' cellspacing='4' width='100%'>";

			echo "<tr><td bgcolor='#FFCC99' class='boldFont'>Test Case: " . $myrowTC[0] . "</td></tr>";
				
			$sqlResult = "select build,notes,status,daterun,runby from testcase,results where results.tcid='" . $myrowTC[1] . "' and testcase.id=results.tcid";
	
			$resultResult = mysql_query($sqlResult); //Execute Query

			echo "<table border='1' width='100%' border='0' cellpadding='0' cellspacing='4'>";

			

			if(mysql_num_rows($resultResult) == 0)
			{

				echo "<tr><td>No Results Available</td></tr>";
			}else
			{

				echo "<tr><td bgcolor='#EEEEEE' width='5%'><b>Build</td><td bgcolor='#EEEEEE' width='50%'><b>Notes</td><td bgcolor='#EEEEEE' width='5%'><b>Status</td><td bgcolor='#EEEEEE'><b>Date</td><td bgcolor='#EEEEEE'><b>Run By</td><td bgcolor='#EEEEEE'><b>Bugs</td></tr>";

			}

			while ($myrowResult = mysql_fetch_row($resultResult)) //Cycle through all of the test cases
			{
							
				

				if(!$myrowResult[1])
				{
					echo "<tr><td>" . $myrowResult[0] . "</td><td>-</td><td>" . $myrowResult[2] . "</td><td>" . $myrowResult[3] . "</td><td>" . $myrowResult[4] . "</td>";


				}else
				{

				echo "<tr><td>" . $myrowResult[0] . "</td><td>" . $myrowResult[1] . "</td><td>" . $myrowResult[2] . "</td><td>" . $myrowResult[3] . "</td><td>" . $myrowResult[4] . "</td>";
				
				}


				//This is where we display the bugs

				$sqlBugs = "select bug from bugs where tcid=" . $myrowTC[1] . " and build=" . $myrowResult[0];
				$resultBugs = mysql_query($sqlBugs);

				if(mysql_num_rows($resultBugs) == 0)
				{

				echo "<td>-</td>";
				}else
				{
					echo "<td>";

					while ($myrowBugs = mysql_fetch_row($resultBugs))
					{
						echo "<a href=http://box.good.com/bugzilla/show_bug.cgi?id=" . $myrowBugs[0] . " target='_blank'>" . $myrowBugs[0] . "</a>,";
					

					}
				

				}
						


				echo "</td></tr>";
			
			}
			
			echo "</table>";
			
		}
		
		echo "</table>";



	}

	echo "</table>";

	}


}

if($_GET['edit'] == 'category')
{

	echo "<table border='1' cellpadding='0' cellspacing='4' width='100%'>";

	$sqlCAT = "select category.name,category.id from category where category.id='" . $_GET['cat'] . "'";

	$resultCAT = mysql_query($sqlCAT); //Execute Query


	while ($myrowCAT = mysql_fetch_row($resultCAT)) //Cycle through all of the Categories
	{
	
		echo "<table border='1' cellpadding='0' cellspacing='4' width='100%'>";
		
		echo "<tr><td bgcolor='#FFFFCC' class='boldFont'>Category: " . $myrowCAT[0] . "</td></tr>";

		$sqlTC = "select testcase.title,testcase.id from testcase,category where testcase.catid='" . $myrowCAT[1] . "' and category.id=testcase.catid";

		$resultTC = mysql_query($sqlTC); //Execute Query

		
		while ($myrowTC = mysql_fetch_row($resultTC)) //Cycle through all of the test cases
		{
			
						
			echo "<table border='1' cellpadding='0' cellspacing='4' width='100%'>";

			echo "<tr><td bgcolor='#FFCC99' class='boldFont'>Test Case: " . $myrowTC[0] . "</td></tr>";
				
			$sqlResult = "select build,notes,status,daterun,runby from testcase,results where results.tcid='" . $myrowTC[1] . "' and testcase.id=results.tcid";
	
			$resultResult = mysql_query($sqlResult); //Execute Query

			echo "<table border='1' width='100%' border='0' cellpadding='0' cellspacing='4'>";

			

			if(mysql_num_rows($resultResult) == 0)
			{

				echo "<tr><td>No Results Available</td></tr>";
			}else
			{

				echo "<tr><td bgcolor='#EEEEEE' width='5%'><b>Build</td><td bgcolor='#EEEEEE' width='50%'><b>Notes</td><td bgcolor='#EEEEEE' width='5%'><b>Status</td><td bgcolor='#EEEEEE'><b>Date</td><td bgcolor='#EEEEEE'><b>Run By</td><td bgcolor='#EEEEEE'><b>Bugs</td></tr>";

			}

			while ($myrowResult = mysql_fetch_row($resultResult)) //Cycle through all of the test cases
			{
							
				

				if(!$myrowResult[1])
				{
					echo "<tr><td>" . $myrowResult[0] . "</td><td>-</td><td>" . $myrowResult[2] . "</td><td>" . $myrowResult[3] . "</td><td>" . $myrowResult[4] . "</td>";


				}else
				{

				echo "<tr><td>" . $myrowResult[0] . "</td><td>" . $myrowResult[1] . "</td><td>" . $myrowResult[2] . "</td><td>" . $myrowResult[3] . "</td><td>" . $myrowResult[4] . "</td>";
				
				}


				//This is where we display the bugs

				$sqlBugs = "select bug from bugs where tcid=" . $myrowTC[1] . " and build=" . $myrowResult[0];
				$resultBugs = mysql_query($sqlBugs);

				if(mysql_num_rows($resultBugs) == 0)
				{

				echo "<td>-</td>";
				}else
				{
					echo "<td>";

					while ($myrowBugs = mysql_fetch_row($resultBugs))
					{
						echo "<a href=http://box.good.com/bugzilla/show_bug.cgi?id=" . $myrowBugs[0] . " target='_blank'>" . $myrowBugs[0] . "</a>,";
					

					}//end while
				

				}//end else
						


				echo "</td></tr>";
			
			}//end if
			
			echo "</table>";
			
		}
		
		//echo "</table>";



	}




}


?>
