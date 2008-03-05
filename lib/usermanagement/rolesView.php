<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: rolesView.php,v $
 *
 * @version $Revision: 1.17 $
 * @modified $Date: 2008/03/05 22:22:39 $ by $Author: franciscom $
**/
require_once("../../config.inc.php");
require_once("common.php");
require_once("users.inc.php");
require_once("roles.inc.php");
testlinkInitPage($db);

$template_dir = 'usermanagement/';
$default_template = str_replace('.php','.tpl',basename($_SERVER['SCRIPT_NAME']));

init_global_rights_maps();
$args = init_args();

$userFeedback = null;
$affectedUsers = null;
$doDelete = false;

switch ($args->doAction)
{
	case 'delete':
		$role = tlRole::getByID($db,$args->roleid,tlRole::TLOBJ_O_GET_DETAIL_MINIMUM);
		$affectedUsers = $role->getAllUsersWithRole($db);
		$doDelete = (sizeof($affectedUsers) == 0);
		break;  

	case 'confirmDelete':
		$doDelete = 1;
		break;  
}
if($doDelete)
{
    $userFeedback = deleteRole($db,$args->roleid);
	//refresh the current user
	checkSessionValid($db);
}
$roles = tlRole::getAll($db,null,null,null,tlRole::TLOBJ_O_GET_DETAIL_MINIMUM);

$highlight->view_roles=1;
$smarty = new TLSmarty();
$smarty->assign('highlight',$highlight);
$smarty->assign('mgt_users',has_rights($db,"mgt_users"));
$smarty->assign('role_management',has_rights($db,"role_management"));
$smarty->assign('tp_user_role_assignment', 
                has_rights($db,"mgt_users") ? "yes" : has_rights($db,"testplan_user_role_assignment"));
$smarty->assign('tproject_user_role_assignment', 
                has_rights($db,"mgt_users") ? "yes" : has_rights($db,"user_role_assignment",null,-1));
$smarty->assign('roles',$roles);
$smarty->assign('id',$args->roleid);
$smarty->assign('sqlResult',$userFeedback);
$smarty->assign('affectedUsers',$affectedUsers);
$smarty->assign('role_id_replacement',config_get('role_replace_for_deleted_roles'));
$smarty->display($template_dir . $default_template);

function init_args()
{
    $args = new stdClass();
    $_REQUEST = strings_stripSlashes($_REQUEST);
	
	  $args->roleid = isset($_REQUEST['roleid']) ? intval($_REQUEST['roleid']) : 0;
    $args->doAction = isset($_REQUEST['doAction']) ? $_REQUEST['doAction'] : '';
    $args->userID = $_SESSION['currentUser']->dbID;

    return $args;  
}

?>
