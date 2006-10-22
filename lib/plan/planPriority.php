<?php
/* 
TestLink Open Source Project - http://testlink.sourceforge.net/ 
$Id: planPriority.php,v 1.9 2006/10/22 19:50:25 schlundus Exp $

This feature allows to define rules for priority dependency 
to importance/risk for actual Test Plan
*/
require('../../config.inc.php');
require_once("../functions/common.php");
require_once("../functions/priority.inc.php");
testlinkInitPage($db);


$sqlResult = null;
if(isset($_POST['updatePriorityRules']))
	$sqlResult = setPriority($db,$_POST['priority']);

$priorities = getPriority($db,$_SESSION['testPlanId']);

$smarty = new TLSmarty();
$smarty->assign('optionPriority', array(
										'a' => 'A', 
										'b' => 'B', 
										'c' => 'C')
									);
$smarty->assign('sqlResult', $sqlResult);
$smarty->assign('arrRules', $priorities);
$smarty->assign('testplan_name', $_SESSION['testPlanName']);
$smarty->display('planPriority.tpl');
?>
