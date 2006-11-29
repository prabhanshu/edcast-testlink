<?php
/** 
* TestLink Open Source Project - http://testlink.sourceforge.net/ 
* $Id: resultsSend.php,v 1.12 2006/11/29 19:59:19 kevinlevy Exp $ 
*
* @author	Martin Havlat <havlat@users.sourceforge.net>
* @author	Chad Rosen
* 
* Shows and processes the form for sending a Test Report.
*
* 
* @author Francisco Mancardi - 20050906 - reduce global coupling
*
*/
require('../../config.inc.php');
require_once('common.php');
// require_once('results.inc.php');
require_once('builds.inc.php');
require_once('info.inc.php');
require_once("../../lib/functions/lang_api.php");
require_once('../functions/results.class.php');
testlinkInitPage($db);

$tp = new testplan($db);
$tpID = isset($_SESSION['testPlanId']) ? $_SESSION['testPlanId'] : 0 ;
$builds = $tp->get_builds($tpID); 
$builds_two = array();
for ($i = 0; $i < sizeOf($builds); $i++ ) {
	$array = $builds[$i];
	$builds_two[$array['id']] = $array['name'];
}

$tp = new testplan($db);
$builds_to_query = -1;
$suitesSelected = 'all';
$re = new results($db, $tp, $suitesSelected, $builds_to_query);
$topLevelSuites= $re->getTopLevelSuites();
$topLevelSuites_two = array();
while ($i = key($topLevelSuites)){
	$array = $topLevelSuites[$i];
	$topLevelSuites_two[$array['id']] = $array['name'];
	next($topLevelSuites);	
}

$message = null;
// process input data
if(isset($_POST['submit']))
{
	if($_POST['to'] == "") //check to see if the to field was blank
		$message = lang_get("send_to_empty_email_warning");
	else
	{

		print "testPlanId = " . $_SESSION['testPlanId'] . " <BR>";
		print "buildProj = " . $_POST['buildProj'] . "<BR>";
		print "buildCom = " . $_POST['buildCom'] . " <BR>";

		// create message body
		$msgBody = (isset($_POST['body']) ? $_POST['body'] : null) . "\n\n";
		$status = isset($_POST['status']) ? $_POST['status'] : null;
		//$builds = getBuilds($db,$_SESSION['testPlanId']," ORDER BY builds.name ");

		if($status == 'projAll')
		{
			 //if the user has chosen to sent the entire testplan priority info
			//grab all of the priority info and stuff it into the message body
			$msgBody .= "reportGeneralStatus in progress";
				// reportGeneralStatus($db,$_SESSION['testPlanId']);
		} 
		else if($status == 'comAll')
		{ 
		  // user has chosen to send a specific component status across all builds
		  
		  // 20051106 - fm - missed argument
			$msgBody .= "reportSuiteStatus"; 
				//reportSuiteStatus($db,$_SESSION['testPlanId'],$_POST['comSelectAll']);
		}	
		else if($status == 'projBuild') 
		{ 
			// 20051106 - fm - missed argument
		  //user has chosen to send the status of a particular build
			$msgBody .= "reportBuildStatus"; 
				// reportBuildStatus($db,$_SESSION['testPlanId'],
			                         //     $_POST['buildProj'],$builds[$_POST['buildProj']]);
		}	
		else
		{ 
			// 20051106 - fm - missed argument
		  //user has chosen to send the status of a particular component for a build
			$msgBody .= "reportSuiteBuildStatus"; 
				//reportSuiteBuildStatus($db,$_SESSION['testPlanId'],$_POST['comSelectBuild'], 
			                       //            $_POST['buildCom'],$builds[$_POST['buildCom']]);
		}
		
			
		// Send mail
		$headers = null;
		$send_cc_to_myself=false;
		if (isset($_POST['cc']))
		{
			// 20051106 - fm
			// $headers = "Cc: " . $_SESSION['email'] . "\r\n";
			$send_cc_to_myself=true;
    }
    
    // 20050906 - fm
		$message = sendMail($_SESSION['email'],$_POST['to'], $_POST['subject'],
		                    $msgBody,$send_cc_to_myself);
	}
}

/**
* 20061127 - KL - temporarily comment out
*/
//Gather all of the current TP components for the dropdown box
//$suites = listTPComponent($db,$_SESSION['testPlanId']);
// Gather info for the build dropdown box
//$builds = getBuilds($db,$_SESSION['testPlanId']," ORDER BY builds.name ");
// warning if no build or component

if(count($topLevelSuites_two) == 0 || count($builds_two) == 0) {
	displayInfo($_SESSION['testPlanName'], lang_get("warning_create_build_first"));
}

$smarty = new TLSmarty;
$smarty->assign('tpName', $_SESSION['testPlanName']);
$smarty->assign('message', $message);
$smarty->assign('suites', $topLevelSuites_two);
$smarty->assign('builds', $builds_two);
$smarty->display('resultsSend.tpl');
?>