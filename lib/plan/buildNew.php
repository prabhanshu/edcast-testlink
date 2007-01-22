<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: buildNew.php,v $
 *
 * @version $Revision: 1.27 $
 * @modified $Date: 2007/01/22 08:31:14 $ $Author: franciscom $
 *
 * rev :
 *       20070121 - franciscom - active and open management
 *       20061118 - franciscom - added check_build_name_existence()
 *
*/
require('../../config.inc.php');
require_once("../functions/common.php");
require_once("plan.inc.php");
require_once("../functions/builds.inc.php");
require_once("../../third_party/fckeditor/fckeditor.php");

testlinkInitPage($db);

$tplan_mgr = new testplan($db);
$tpID    = isset($_SESSION['testPlanId']) ? $_SESSION['testPlanId'] : 0;
$buildID = isset($_REQUEST['buildID']) ? intval($_REQUEST['buildID']) : 0;
$build_name = isset($_REQUEST['build_name']) ? trim(strings_stripSlashes($_REQUEST['build_name'])) : null;
$notes = isset($_REQUEST['notes']) ? strings_stripSlashes($_REQUEST['notes']) : null;
$tpName = $_SESSION['testPlanName'];

$is_active = isset($_REQUEST['is_active']) ? intval($_REQUEST['is_active']) : ACTIVE;
$is_open = isset($_REQUEST['is_open']) ? intval($_REQUEST['is_open']) : OPEN;


$the_builds = $tplan_mgr->get_builds_for_html_options($tpID);

$smarty = new TLSmarty();

$of = new fckeditor('notes') ;
$of->BasePath = $_SESSION['basehref'] . 'third_party/fckeditor/';
$of->ToolbarSet = 'TL_Medium';

$build_action = 'newBuild';
$button_value = lang_get('btn_create');

$can_insert_or_update = 0;
$sqlResult =  lang_get("invalid_build_id");

if (strlen($build_name))
{
	$sqlResult = lang_get("warning_duplicate_build");  
	if(sizeof($the_builds) == 0 || 
	   !$tplan_mgr->check_build_name_existence($tpID,$build_name) ||
	   (isset($the_builds[$buildID]) && $the_builds[$buildID] == $build_name))
	{
		$sqlResult = 'ok';
		$can_insert_or_update = 1;
	}
}

if(isset($_REQUEST['newBuild']))
{
	$of->Value = $notes;
	if ($can_insert_or_update)
	{
		if (!$tplan_mgr->create_build($tpID,$build_name,$notes,$is_active,$is_open))
			$sqlResult = lang_get("cannot_add_build");
		else
		{
			$build_name = '';
			$of->Value = '';
		} 	
	}
	$smarty->assign('sqlResult', $sqlResult);
}

if(isset($_REQUEST['del_build']))
{
	$sqlResult = 'ok';
	if (!delete_build($db,$buildID))
	{
		$sqlResult = lang_get("cannot_delete_build");
	}
	$smarty->assign('sqlResult', $sqlResult);
	$smarty->assign('action', 'deleted');
}

if(isset($_REQUEST['edit_build']))
{
	$build_action = 'edit_build';
	$button_value = lang_get('btn_save');
	if(strcasecmp($_REQUEST['edit_build'], "load_info") == 0 )
	{
		$my_b_info = getBuild_by_id($db,$buildID);
		$build_name = $my_b_info['name'];
		$of->Value = $my_b_info['notes'];
	}
	else
	{
		$of->Value = $notes;
		if ($can_insert_or_update)
		{
		   	if (!updateTestPlanBuild($db,$buildID,$build_name,$notes))
			 	$sqlResult = lang_get("cannot_update_build");
			else
			{
				$build_name = '';
				$of->Value = '';
				$build_action = 'newBuild';
				$button_value = lang_get('btn_create');
			}
		}
		$smarty->assign('sqlResult', $sqlResult);
		
	}
}

// Refesh data after operation
$the_builds = $tplan_mgr->get_builds($tpID);

$smarty->assign('TPname', $tpName);
$smarty->assign('arrBuilds', $the_builds);
$smarty->assign('build_name', $build_name);
$smarty->assign('notes', $of->CreateHTML());
$smarty->assign('button_name', $build_action);
$smarty->assign('button_value', $button_value);
$smarty->display('buildNew.tpl');
?>
