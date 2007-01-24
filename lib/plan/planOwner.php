<?php
/* TestLink Open Source Project - http://testlink.sourceforge.net/ */
/* $Id: planOwner.php,v 1.15 2007/01/24 08:10:25 franciscom Exp $ */
/**
 * Manage the ownership and priority of test suite
 *
 * @author Francisco Mancardi - 20050914 - refactoring         
 * @author Francisco Mancardi - 20050907 - bug on help          
 * 
 *
 * 20051112 - scs - simplified case 'component', added localization of imp's
 * 					small cosmetic changes
 * 20051203 - scs - added filtering of tp users by tpid
 *
 * 20070124 - franciscom
 * use show_help.php to apply css configuration to help pages
 *
 */
require('../../config.inc.php');
require_once("../functions/common.php");
require_once('plan.inc.php');
testlinkInitPage($db);

$level = isset($_GET['level']) ? $_GET['level'] : null;
$compID = isset($_GET['data']) ? intval($_GET['data']) : null;
$catID = isset($_GET['data']) ? intval($_GET['data']) : null;

$tpID = $_SESSION['testPlanId'];
$updated = null;
if(isset($_POST['updateSuiteAttribute']) && $_POST['updateSuiteAttribute'])
{
	$updated = updateSuiteAttributes($db,$_POST);
}

$arrSuites = null;
if($level == 'testproject')
{
	//redirect("../../" . TL_INSTRUCTIONS_RPATH . $_SESSION['locale'] . '/planOwnerAndPriority.html');
 	redirect($_SESSION['basehref'] . "/lib/general/show_help.php?help=planOwnerAndPriority&locale={$_SESSION['locale']}");

}	
else if($level == 'component')
{
	$arrSuites = getAllTestPlanComponentCategories($db,$tpID,$compID);
}
else if($level == 'category')
{
	$arrSuites = getTP_category_info($db,$catID);
}

$arrUsers = getTestPlanUsers($db,$tpID);
$smarty = new TLSmarty();
$smarty->assign('sqlResult', $updated);
$smarty->assign('optionImportance', array(
											'L' => lang_get('opt_imp_low'),
											'M' => lang_get('opt_imp_medium'),
											'H' => lang_get('opt_imp_high'),
										)
				);
$smarty->assign('optionRisk', array(
										'3' => '3',
										'2' => '2',
										'1' => '1'
									)
				);
$smarty->assign('arrUsers', $arrUsers);
$smarty->assign('arrSuites', $arrSuites);
$smarty->display('planOwner.tpl');
?>