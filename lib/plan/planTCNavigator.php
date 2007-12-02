<?php
/** 
*	TestLink Open Source Project - http://testlink.sourceforge.net/ 
* @version $Id: planTCNavigator.php,v 1.1 2007/12/02 17:16:02 franciscom Exp $
*	@author Martin Havlat 
*
* Used in the remove test case feature
*
* rev :
*      20070925 - franciscom - added management of workframe
*/ 	
require('../../config.inc.php');
require_once("common.php");
require_once("treeMenu.inc.php");
testlinkInitPage($db);

$workframe=null;
$user_id=$_SESSION['userID'];
$template_dir='plan/';

$tplan_mgr = new testplan($db);

$tproject_id = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
$tproject_name = isset($_SESSION['testprojectName']) ? $_SESSION['testprojectName'] : '';

// 20070120 - franciscom - 
// is possible to call this page using a Test Project that have no test plans
// in this situation the next to entries are undefined in SESSION
$tplan_id = isset($_SESSION['testPlanId']) ? intval($_SESSION['testPlanId']) : 0;
$tplan_name =isset($_SESSION['testPlanName']) ? $_SESSION['testPlanName'] : '';

if( $tplan_id != 0 )
{
  $tplan_id = isset($_REQUEST['tplan_id']) ? $_REQUEST['tplan_id'] : $_SESSION['testPlanId'];
  $tplan_info = $tplan_mgr->get_by_id($tplan_id); 
  $tplan_name = $tplan_info['name'];
}

// filter using user roles 
$tplans=getAccessibleTestPlans($db,$tproject_id,$user_id,1);
$map_tplans=array();
foreach($tplans as $key => $value)
{
  $map_tplans[$value['id']]=$value['name'];
}


$keyword_id = 0;

// We only want to use in the filter, keywords present in the test cases that are
// linked to test plan, and NOT all keywords defined for test project
$keywords_map = $tplan_mgr->get_keywords_map($tplan_id, " order by keyword "); 

if(!is_null($keywords_map))
{
	$keywords_map = array( 0 => '') + $keywords_map;
}

// if(isset($_POST['filter']))
// {
// 	$keyword_id = isset($_POST['keyword_id']) ? $_POST['keyword_id'] : 0;
// }
$keyword_id = isset($_POST['keyword_id']) ? $_POST['keyword_id'] : 0;

// set feature data
$the_feature=$_GET['feature'];
$help_topic=isset($_GET['help_topic']) ? $_GET['help_topic'] : $the_feature;
switch($the_feature)
{
  case 'removeTC':
	$menuUrl = "lib/plan/planTCRemove.php";
	$title = lang_get('title_test_plan_navigator');
	$hide_tc = 0;
	$help_file = "testSetRemove.html";
  break;
  
  case 'plan_risk_assignment':
	$menuUrl = "lib/plan/plan_risk_assignment.php";
	$title = lang_get('title_test_plan_navigator');
	$hide_tc = 1;
	$help_file = "priority.html";
  break;

  case 'tc_exec_assignment':
	$menuUrl = "lib/plan/tc_exec_assignment.php";
	$title = lang_get('title_test_plan_navigator');
	$hide_tc = 0;
	$help_file = "planOwnerAndPriority.html";
  break;
  
  default:   
	tLog("Wrong or missing GET argument 'feature'.", 'ERROR');
	exit();
	break;
	
}


$workframe=$_SESSION['basehref'] . "lib/general/show_help.php" .
                                   "?help={$help_topic}&locale={$_SESSION['locale']}";

$getArguments = '&tplan_id=' . $tplan_id;       // 20070922 - franciscom
if ($keyword_id)
{
	$getArguments .= '&keyword_id='.$keyword_id;
}

// 20070204 - franciscom - added $hide_tc
$sMenu = generateExecTree($db,$menuUrl,$tproject_id,$tproject_name,$tplan_id,$tplan_name,
                          FILTER_BY_BUILD_OFF,$getArguments,$keyword_id,FILTER_BY_TC_OFF,
                          $hide_tc);

$tree = invokeMenu($sMenu,'',null);

$smarty = new TLSmarty();  

if( !( isset($_POST['filter']) || isset($_REQUEST['called_by_me'])) )
{
  $workframe='';
}


$smarty->assign('workframe',$workframe);
$smarty->assign('args',$getArguments);
$smarty->assign('tplan_id',$tplan_id);
$smarty->assign('map_tplans',$map_tplans);


$smarty->assign('treeKind', TL_TREE_KIND);
$smarty->assign('tree', $tree);
$smarty->assign('keywords_map', $keywords_map);
$smarty->assign('keyword_id', $keyword_id);

$smarty->assign('treeHeader', $title);
$smarty->assign('menuUrl',$menuUrl);
$smarty->assign('SP_html_help_file',TL_INSTRUCTIONS_RPATH . $_SESSION['locale'] ."/". $help_file);
$smarty->assign('additional_string',$tplan_name);
$smarty->display($template_dir . 'planTCNavigator.tpl');
?>
