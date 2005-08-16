<?php
/** 
* TestLink Open Source Project - http://testlink.sourceforge.net/ 
* $Id: resultsNavigator.php,v 1.2 2005/08/16 18:00:58 franciscom Exp $ 
*
* @author	Martin Havlat <havlat@users.sourceforge.net>
* 
* This page list View of Test Results and Metrics.
*
* @todo Reload workarea if build is changed 
* @todo xls ouput should be general over all builds
*
*
* @author 20050807 - fm
* refactoring:  
* removed deprecated: $_SESSION['project']
*
*/
require('../../config.inc.php');
require_once('common.php');
require_once('builds.inc.php');
require_once("../../lib/functions/lang_api.php");
testlinkInitPage();

// there is list of available results and metrics view
$arrData = array(
	array('name' => lang_get('link_report_general_tp_metrics'), 'href' => 'resultsGeneral.php'),
	array('name' => lang_get('link_report_overall_build'), 'href' => 'resultsAllBuilds.php'),
	array('name' => lang_get('link_report_test'), 'href' => 'resultsTC.php'),
	array('name' => lang_get('link_report_excel'), 'href' => 'resultsTC.php?format=excel'),
	array('name' => lang_get('link_report_blocked_tcs'), 'href' => 'resultsByStatus.php?type=b'),
	array('name' => lang_get('link_report_failed'), 'href' => 'resultsByStatus.php?type=f'),
	array('name' => lang_get('link_report_total_bugs'), 'href' => 'resultsBugs.php'),
      array('name' => 'Query by Start and End Build', 'href' => 'mine_results.php'),
  );

// this results are related to selected build
$arrDataB = array(
	array('name' => lang_get('link_report_metrics_active_build'), 'href' => 'resultsBuild.php'),
);

// collect builds of Test Plan
//
// 20050807 - fm 
$arrBuilds = getBuilds($_SESSION['testPlanId']);
if (isset($_GET['build']))
	$selectedBuild = $_GET['build'];
else
	$selectedBuild = sizeof($arrBuilds) ? key($arrBuilds) : null;

$smarty = new TLSmarty;
$smarty->assign('title', 'Navigator - Results');
$smarty->assign('arrData', $arrData);
$smarty->assign('arrDataB', $arrDataB);
$smarty->assign('arrBuilds', $arrBuilds);
$smarty->assign('selectedBuild', $selectedBuild);
$smarty->display('resultsNavigator.tpl');
?>
