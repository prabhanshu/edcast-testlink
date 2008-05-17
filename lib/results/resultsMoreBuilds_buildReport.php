<?php
/** 
* TestLink Open Source Project - http://testlink.sourceforge.net/ 
* $Id: resultsMoreBuilds_buildReport.php,v 1.57 2008/05/17 17:41:09 franciscom Exp $ 
*
* @author	Kevin Levy <kevinlevy@users.sourceforge.net>
* 
* This page will forward the user to a form where they can select
* the builds they would like to query results against.
*
* rev :
*      20070901 - franciscom - refactoring
* 
**/
require('../../config.inc.php');
require_once('common.php');
require_once('results.class.php');
require_once('users.inc.php');
require_once('displayMgr.php');
testlinkInitPage($db);
$template_dir='results/';

$args = init_args();
$gui=initializeGui($db,$args);

$reports_cfg=config_get('reportsCfg');
$tc_status_verbose_code=config_get('tc_status');   
$tc_status_verbose_labels=config_get('tc_status_verbose_labels');   



// Config to manage versobe and code status
$tc_status_code_verbose=array_flip($tc_status_verbose_code);

// same key that tcstatus_verbose_code
$displayTCRows=array();
$gui->lastStatus=array();
foreach($reports_cfg->exec_status as $verbose => $label)
{
  $displayTCRows[$verbose]=false;
}

foreach($args->lastStatus	as $key => $status_code)
{
   $verbose=$tc_status_code_verbose[$status_code];
   $displayTCRows[$verbose]=true;
   $lastStatus_localized[]=lang_get($tc_status_verbose_labels[$verbose]);
}	
$gui->lastStatus=$lastStatus_localized;


$smarty = new TLSmarty();
$smarty->assign('gui', $gui);

if ($args->ownerSelected) {
	$smarty->assign('ownerSelected', $gui->users[$args->ownerSelected]);
}
if ($args->executorSelected) {
	$smarty->assign('executorSelected', $gui->users[$args->executorSelected]);
}
if ($args->search_notes_string) {
	$smarty->assign('search_notes_string', $args->search_notes_string);
}

$smarty->assign('displayUnexecutedRows', $displayTCRows['not_run']);
$smarty->assign('displayBlockedRows', $displayTCRows['blocked']);
$smarty->assign('displayPassedRows', $displayTCRows['passed']);
$smarty->assign('displayFailedRows', $displayTCRows['failed']);

$report_type = isset($_GET['report_type']) ? intval($_GET['report_type']) : null;

$smarty->assign('report_type', $report_type);
if (!isset($_GET['report_type']))
{
	tlog('$_GET["report_type"] is not defined');
	exit();
}

displayReport($template_dir . 'resultsMoreBuilds_report', $smarty, $report_type);
?>


<?php
function get_date_range($hash)
{
    $date_range=new stdClass();
    $date_range->start=new stdClass();    
    $date_range->end=new stdClass();
    
    $date_range->start->day=isset($hash['start_Day']) ? $hash['start_Day'] : "01";
    $date_range->start->month=isset($hash['start_Month']) ? $hash['start_Month'] : "01";
    $date_range->start->year=isset($hash['start_Year']) ? $hash['start_Year'] : "2000";
    $date_range->start->hour=isset($hash['start_Hour']) ? $hash['start_Hour'] : "00";
    
    $mm=sprintf("%02d",$date_range->start->month);
    $dd=sprintf("%02d",$date_range->start->day);
    $date_range->start->date=$date_range->start->year . "-" . $mm . "-" . $dd;
    $date_range->start->time=$date_range->start->date . " " . $date_range->start->hour . ":00:00";
    
    $date_range->end->day=isset($hash['end_Day']) ? $hash['end_Day'] : "01";
    $date_range->end->month=isset($hash['end_Month']) ? $hash['end_Month'] : "01";
    $date_range->end->year=isset($hash['end_Year']) ? $hash['end_Year'] : "2050";
    $date_range->end->hour=isset($hash['end_Hour']) ? $hash['end_Hour'] : "00";
    
    $mm=sprintf("%02d",$date_range->end->month);
    $dd=sprintf("%02d",$date_range->end->day);
    $date_range->end->date=$date_range->end->year . "-" . $mm . "-" . $dd;
    $date_range->end->time=$date_range->end->date . " " . $date_range->end->hour . ":00:00";
    
    return $date_range;
}


/*
  function: initializeGui

  args :

  returns: 

*/
function initializeGui(&$dbHandler,&$argsObj)
{
    $gui=new stdClass();  
    $tplan_mgr = new testplan($dbHandler);
    $tproject_mgr = new testproject($dbHandler);
    
    $date_range=get_date_range($_REQUEST);
    $gui->startTime=$date_range->start->time;
    $gui->endTime=$date_range->end->time;
    

    $gui->tplan_id=$_REQUEST['tplan_id'];
    $gui->tproject_id=$_SESSION['testprojectID'];
    
    
    $tplan_info = $tplan_mgr->get_by_id($gui->tplan_id);
    $gui->tplan_name = $tplan_info['name'];

    $tproject_info = $tproject_mgr->get_by_id($gui->tproject_id);
    $gui->tproject_name = $tproject_info['name'];

    
    $execution_link_build = isset($_REQUEST['build']) ? intval($_REQUEST['build']) : null;
    
    $testsuiteIds = null;
    $testsuiteNames = null;
    
    $tsuites_qty=sizeOf($argsObj->testsuitesSelected);
    for ($id = 0; $id < $tsuites_qty ; $id++)
    {
    	list($suiteId, $suiteName) = split("\,", $argsObj->testsuitesSelected[$id], 2);
    	$testsuiteIds[$id] = $suiteId;
    	$testsuiteNames[$id] = $suiteName;	
    }

    $buildsToQuery = -1;
    if (sizeof($argsObj->buildsSelected)) {
    	$buildsToQuery = implode(",", $argsObj->buildsSelected);
    }

    // statusForClass is used for results.class.php
    // lastStatus is used to be displayed 
    $statusForClass = 'a';
    
    $re = new results($dbHandler, $tplan_mgr,$tproject_info,$tplan_info, 
                      $testsuiteIds, $buildsToQuery, $statusForClass, 
                      $argsObj->keywordSelected, $argsObj->ownerSelected, 
                      $date_range->start->time, $date_range->end->time, 
                      $argsObj->executorSelected, $argsObj->search_notes_string, $execution_link_build);
                      
    $gui->suiteList = $re->getSuiteList();
    $gui->flatArray = $re->getFlatArray();
    $gui->mapOfSuiteSummary =  $re->getAggregateMap();
    $gui->totals = $re->getTotalsForPlan();

    $gui->keywords = new stdClass();             
    $gui->keywords->items = $tplan_mgr->get_keywords_map($gui->tplan_id); 
    $gui->keywords->qty = count($gui->keywords->items);
    
    $gui->builds = $tplan_mgr->get_builds($gui->tplan_id); 
    $gui->builds_html = $tplan_mgr->get_builds_for_html_options($gui->tplan_id);
    $gui->users = getUsersForHtmlOptions($dbHandler, ALL_USERS_FILTER, !ADD_BLANK_OPTION);


    // $gui->testsuitesSelected=$argsObj->testsuitesSelected;
    $gui->testsuitesSelected=$testsuiteNames;
    $gui->buildsSelected=$argsObj->buildsSelected;
    
    $gui->display=$argsObj->display;
    return $gui;
}


function init_args()
{
    $args = new stdClass();  
    $args->format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'HTML';
  
    $args->display = new stdClass();
    $args->display->suite_summaries = isset($_REQUEST['display_suite_summaries']) ? $_REQUEST['display_suite_summaries'] : true;
    $args->display->totals = isset($_REQUEST['display_totals']) ? $_REQUEST['display_totals'] : true;
    $args->display->query_params = isset($_REQUEST['display_query_params']) ? $_REQUEST['display_query_params'] : true;

    $args->lastStatus = isset($_REQUEST['lastStatus']) ? $_REQUEST['lastStatus'] : array();

    $args->keywordSelected = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : 0;
    $args->ownerSelected = (isset($_REQUEST['owner']) && $_REQUEST['owner'] > 0 ) ? $_REQUEST['owner'] : null;
    $args->executorSelected = (isset($_REQUEST['executor']) && $_REQUEST['executor'] > 0) ? $_REQUEST['executor'] : null;
    
    $args->buildsSelected = isset($_REQUEST['build']) ? $_REQUEST['build'] : array();
    $args->testsuitesSelected = isset($_REQUEST['testsuite']) ? $_REQUEST['testsuite'] : array();
    $args->search_notes_string = isset($_REQUEST['search_notes_string']) ? $_REQUEST['search_notes_string'] : null;


    return $args;  
}
?>
