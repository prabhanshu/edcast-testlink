<?

////////////////////////////////////////////////////////////////////////////////
//File:     executionFrameLeft.php
//Author:   Chad Rosen
//Purpose:  This page is the left frame of the execution pages. It builds the
//          javascript trees that allow the user to jump around to any point
//	    on the screen
////////////////////////////////////////////////////////////////////////////////


require_once("../../functions/header.php");
doSessionStart();
doDBConnect();
doHeader();
require_once("../../functions/generateTreeMenu.php");


//////////////////////////////////////////////////////////////Start the display of the components
		
			$sqlProject = "select name from project where id=" . $_SESSION['project'];
		$resultProject = mysql_query($sqlProject);
		$myrowProj = mysql_fetch_row($resultProject);

		$menustring =  ".|" . $myrowProj[0] . "||||mainFrame|\n";

		//Here I create a query that will grab every component depending on the project the user picked
		
		$sql = "select component.id, component.name from component,project where project.id = " . $_SESSION['project'] . " and component.projid = project.id order by name";

		$comResult = mysql_query($sql);

		while ($myrowCOM = mysql_fetch_row($comResult)) 
		{ 
			//display all the components until we run out
			
			$menustring =  $menustring . "..|" . $myrowCOM[1] . "|admin/TC/editData.php?level=com&data=" . $myrowCOM[0] . "|||mainFrame|\n";

			//Here I create a query that will grab every category depending on the component the user picked

			$catResult = mysql_query("select category.id, category.name from component,category where component.id = " . $myrowCOM[0] . " and component.id = category.compid order by CATorder,category.id",$db);
			
			while ($myrowCAT = mysql_fetch_row($catResult)) 
			{  
				$menustring =  $menustring . "...|" . $myrowCAT[1] . "|admin/TC/editData.php?level=cat&data=" . $myrowCAT[0] . "|||mainFrame|\n";

				$sqlTestCase = "select id,title from testcase where catid=" . $myrowCAT[0] . " order by id";
				$resultTestCase = mysql_query($sqlTestCase);

				while ($myrowTC = mysql_fetch_row($resultTestCase)) 
				{
					$menustring =  $menustring . "....|<b>" . $myrowTC[0] . ":</b>" . $myrowTC[1] . "|admin/TC/editData.php?level=tc&data=" . $myrowTC[0] . "|||mainFrame|\n";
						
				}
		



			}

		}

		//Table title
		$tableTitle = "Active/Inactive Test Case";
		//Help link
		$helpInfo = "Click <a href='admin/TC/editData.php?edit=info' target='mainFrame'>here</a> for help";

		//This variable is used when the user is using a server side tree. Ignore otherwise
		if(isset($_GET['p']))
		{
			$_SESSION['p'] = $_GET['p'];
		}

		invokeMenu($menustring, $tableTitle, $helpInfo, "", "");

?>