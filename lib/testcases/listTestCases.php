<?php
/** 
* 	TestLink Open Source Project - http://testlink.sourceforge.net/
* 
* 	@version 	$Id: listTestCases.php,v 1.13 2006/05/03 08:31:25 franciscom Exp $
* 	@author 	Martin Havlat
* 
* 	This page generates tree menu with test specification. It builds the
*	  javascript tree that allows the user to choose required container
*	  or test case.
*
*   20060501 - franciscom - refactoring
*/
require('../../config.inc.php');
require_once("common.php");
require_once("treeMenu.inc.php");
testlinkInitPage($db);

$feature = isset($_GET['feature']) ? $_GET['feature'] : null;
$tproject_id   = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
$tproject_name = isset($_SESSION['testprojectName']) ? $_SESSION['testprojectName'] : 'xxx';

$title = lang_get('title_navigator'). ' - ' . lang_get('title_test_spec');

$feature_action=array('edit_tc' => "lib/testcases/archiveData.php",
                      'keywordsAssign' => "lib/keywords/keywordsAssign.php",
                      'assignReqs' => "lib/req/reqTcAssign.php");

if(!is_null($feature) && strlen($feature))
{
  if( isset($feature_action[$feature]) )
  {
    $workPath = $feature_action[$feature];
  }
	else
	{
		tLog("Wrong get argument 'feature'.", 'ERROR');
		exit();
	}
}
else
{
	tLog("Missing argument 'feature'.", 'ERROR');
	exit();
}

define('SHOW_TESTCASE_ITEMS',0);
define('TC_ACTION_ENABLED',1);
$treeString = generateTestSpecTree($db,$tproject_id, $tproject_name,
                                   $workPath, SHOW_TESTCASE_ITEMS,TC_ACTION_ENABLED);

$tree = null;
if (strlen($treeString))
{
	$tree = invokeMenu($treeString);
}
	
$smarty = new TLSmarty();
$smarty->assign('treeKind', TL_TREE_KIND);
$smarty->assign('tree', $tree);
$smarty->assign('treeHeader', $title);
$smarty->assign('menuUrl',$workPath);
$smarty->display('tcTree.tpl');
?>
