<?php
/** 
*	TestLink Open Source Project - http://testlink.sourceforge.net/ 
* 	@version $Id: testSetNavigator.php,v 1.10 2006/02/04 20:13:15 schlundus Exp $
*	@author Martin Havlat 
*
* This page navigate according to Test Set. It builds the javascript trees 
* that allow the user to connect feature for choosen test suite or test case
* Used for 'treemenu' frame generated by frmWorkArea.php script.
*
*
* 20050916 - fm - I18N
* 20051022 - scs - title wasn't set correctly, consmetic changes
* 20051126 - scs - corrected wrong help file
*/ 	
require('../../config.inc.php');
require("common.php");
require("treeMenu.inc.php");
testlinkInitPage($db);

$workPath = null;
// set feature data
if ($_GET['feature'] == 'removeTC')
{
	$workPath = "lib/plan/testSetRemove.php";
	$title = lang_get('title_test_plan_navigator');
	$tcHide = 0;
	$helpFile = "testSetRemove.html";
}
elseif ($_GET['feature'] == 'priorityAssign')
{
	$workPath = "lib/plan/planOwner.php";
	$title = lang_get('title_test_plan_navigator');
	$tcHide = 1;
	$helpFile = "planOwnerAndPriority.html";
}
else
{
	tLog("Wrong or missing GET argument 'feature'.", 'ERROR');
	exit();
}

$treeData = generateTestSuiteTree($db,$workPath, $tcHide);
$tree = invokeMenu($treeData);

$smarty = new TLSmarty();
$smarty->assign('treeKind', TL_TREE_KIND);
$smarty->assign('tree', $tree);
$smarty->assign('treeHeader', $title);
$smarty->assign('menuUrl',$workPath);
$smarty->assign('SP_html_help_file',TL_INSTRUCTIONS_RPATH . $_SESSION['locale'] ."/". $helpFile);
$smarty->display('tcTree.tpl');
?>
