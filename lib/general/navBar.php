<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 *
 * Filename $RCSfile: navBar.php,v $
 *
 * @version $Revision: 1.35 $
 * @modified $Date: 2008/01/31 22:15:48 $ $Author: schlundus $
 *
 * This file manages the navigation bar. 
 *
 * rev :
 *       20070505 - franciscom - use of role_separator configuration
 *
**/
require_once('../../config.inc.php');
require_once("common.php");
testlinkInitPage($db,true);

$tpID = isset($_SESSION['testPlanId']) ? $_SESSION['testPlanId'] : null;
$curr_tproject_id = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
$currentUser = $_SESSION['currentUser'];
$userID = $currentUser->dbID;

$gui_cfg = config_get('gui');
$order_by = $gui_cfg->tprojects_combo_order_by;
$role_separator = config_get('role_separator');

$tproject_mgr = new testproject($db);
$arr_tprojects = $tproject_mgr->get_accessible_for_user($userID,'map', $order_by);

if ($curr_tproject_id)
	getAccessibleTestPlans($db,$curr_tproject_id,$userID,1,$tpID);
	
$testprojectRole = null;
if ($curr_tproject_id && isset($currentUser->tprojectRoles[$curr_tproject_id]))
{
	$role = $currentUser->tprojectRoles[$curr_tproject_id];
	$testprojectRole = $role_separator->open . $role->name . $role_separator->close;
}	                   
$countPlans = getNumberOfAccessibleTestPlans($db,$curr_tproject_id, $_SESSION['filter_tp_by_product'],null);

// only when the user has changed the product using the combo
// the _GET has this key.
// Use this clue to launch a refresh of other frames present on the screen
// using the onload HTML body attribute
$updateMainPage = 0;
if (isset($_GET['testproject']))
{
	$updateMainPage = 1;
	// set test project ID for the next session
	setcookie('lastProductForUser'. $userID, $_GET['testproject'], TL_COOKIE_KEEPTIME, '/');
}

$logo_img = defined('LOGO_NAVBAR') ? LOGO_NAVBAR : '';

$smarty = new TLSmarty();
$smarty->assign('rights_mgt_view_events', has_rights($db,"mgt_view_events"));
$smarty->assign('logo', $logo_img);
$smarty->assign('view_tc_rights',has_rights($db,"mgt_view_tc"));
$smarty->assign('user', $currentUser->getDisplayName() . ' '. 
                        lang_get('Role'). " :: {$role_separator->open} {$currentUser->globalRole->name} {$role_separator->close}");
$smarty->assign('testprojectRole',$testprojectRole);
$smarty->assign('rightViewSpec', has_rights($db,"mgt_view_tc"));
$smarty->assign('rightExecute', has_rights($db,"testplan_execute"));
$smarty->assign('rightMetrics', has_rights($db,"testplan_metrics"));
$smarty->assign('rightUserAdmin', has_rights($db,"mgt_users"));
$smarty->assign('countPlans', $countPlans);
$smarty->assign('countProjects',sizeof($arr_tprojects));
$smarty->assign('arrayProducts', $arr_tprojects);
$smarty->assign('currentProduct', $curr_tproject_id);
$smarty->assign('updateMainPage', $updateMainPage); 
$smarty->assign('currentTProjectID',$curr_tproject_id);
$smarty->display('navBar.tpl');
?>