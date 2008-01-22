<?php
/** 
* TestLink Open Source Project - http://testlink.sourceforge.net/ 
* This script is distributed under the GNU General Public License 2 or later. 
*
* Filename $RCSfile: usersEdit.php,v $
*
* @version $Revision: 1.9 $
* @modified $Date: 2008/01/22 21:52:19 $ $Author: schlundus $
* 
* rev :  BUGID 918
*
*   20070829 - jbarchibald - fix bug 1000 - Testplan role assignments
*
* Allows editing a user
*/
require_once('../../config.inc.php');
require_once('testproject.class.php');
require_once('users.inc.php');
require_once('email_api.php');
testlinkInitPage($db);

$template_dir = 'usermanagement/';
$default_template = str_replace('.php','.tpl',basename($_SERVER['SCRIPT_NAME']));

$args = init_args($_GET,$_POST);
$user_id = $args->user_id;
$sessionUserID = $_SESSION['currentUser']->dbID;

$sqlResult = null;
$action = null;
$user_feedback = '';

if ($args->do_update)
{
	if ($args->user_id == 0)
	{
		$user = new tlUser();	
		$sqlResult = $user->setPassword($args->password);
		if ($sqlResult >= tl::OK)
		{
			$user->login = $args->login;
			$user->emailAddress = $args->email;
			$user->firstName = $args->first;
			$user->lastName = $args->last;
			$user->globalRoleID = $args->rights_id;
			$user->locale = $args->locale;
			$user->bActive = $args->user_is_active;
			
			$sqlResult = $user->writeToDB($db);
		}
		if ($sqlResult >= tl::OK)
		{
			logAuditEvent(TLS("audit_user_created",$user->login),"CREATE",$user->dbID,"users");
			$user_feedback = sprintf(lang_get('user_created'),$args->login);
		}
		else 
			$sqlResult = getUserErrorMessage($sqlResult);
	}
	else
	{
		$user = new tlUser($args->user_id);
		$sqlResult = $user->readFromDB($db);
		if ($sqlResult >= tl::OK)
		{
			$user->firstName = $args->first;
			$user->lastName = $args->last;
			$user->emailAddress = $args->email;
			$user->locale = $args->locale;
			$user->bActive = $args->user_is_active;
			$user->globalRoleID = $args->rights_id;
			
			$sqlResult = $user->writeToDB($db);
			if ($sqlResult >= tl::OK)
			{
				logAuditEvent(TLS("audit_user_saved",$user->login),"SAVE",$user->dbID,"users");
				if ($sessionUserID == $args->user_id)
				{
					$_SESSION['currentUser'] = $user;
					setUserSession($db,$user->login, $args->user_id, $user->globalRoleID, $user->emailAddress, $user->locale);
					if (!$args->user_is_active)
					{
						header("Location: ../../logout.php");
						exit();
					}
				}
			}
			$sqlResult = getUserErrorMessage($sqlResult);
			$action = "updated";							
		}
	}
}
else if ($args->do_reset_password && $user_id)
{
	$result = resetPassword($db,$user_id,$user_feedback);
	if ($result >= tl::OK)
	{
		logAuditEvent(TLS("audit_pwd_reset_requested",$user->login),"PWD_RESET",$user_id,"users");
		$user_feedback = lang_get('password_reseted');  		
	}
}
$user = null;
if ($user_id)
{
	$user = new tlUser($user_id);
	$user->readFromDB($db);
}	
$roles = tlRole::getAll($db,null,null,null,tlRole::TLOBJ_O_GET_DETAIL_MINIMUM);
unset($roles[TL_ROLES_UNDEFINED]);

$smarty = new TLSmarty();
$smarty->assign('user_feedback',$user_feedback);
$smarty->assign('external_password_mgmt', tlUser::isPasswordMgtExternal());
$smarty->assign('mgt_users',has_rights($db,"mgt_users"));
$smarty->assign('role_management',has_rights($db,"role_management"));
$smarty->assign('tp_user_role_assignment', has_rights($db,"mgt_users") ? "yes" : has_rights($db,"testplan_user_role_assignment"));
$smarty->assign('tproject_user_role_assignment', has_rights($db,"mgt_users") ? "yes" : has_rights($db,"user_role_assignment",null,-1));
$smarty->assign('optRights',$roles);
$smarty->assign('userData', $user);
$smarty->assign('result',$sqlResult);
$smarty->assign('action',$action);
$smarty->display($template_dir . $default_template);

function init_args($get_hash, $post_hash)
{
	$post_hash = strings_stripSlashes($post_hash);

	$intval_keys = array('delete' => 0, 'user' => 0,'user_id' => 0);
	foreach ($intval_keys as $key => $value)
	{
		$args->$key = isset($get_hash[$key]) ? intval($get_hash[$key]) : $value;
	}
	
	$intval_keys = array('rights_id' => TL_ROLES_GUEST);
	if(!isset($get_hash['user_id']))
	{
		$intval_keys['user_id'] = 0; 
	}
	
	foreach ($intval_keys as $key => $value)
	{
		$args->$key = isset($post_hash[$key]) ? intval($post_hash[$key]) : $value;
	}
	
	$nullable_keys = array('first','last','email','locale','login','password');
	foreach ($nullable_keys as $value)
	{
		$args->$value = isset($post_hash[$value]) ? $post_hash[$value] : null;
	}
 
	$bool_keys = array('user_is_active','do_update','do_reset_password');
	foreach ($bool_keys as $value)
	{
		$args->$value = isset($post_hash[$value]) ? 1 : 0;
	}
  
	return $args;
}
?>
