<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: login.php,v $
 *
 * @version $Revision: 1.46 $
 * @modified $Date: 2009/01/03 17:28:40 $ by $Author: franciscom $
 * @author Martin Havlat
 * 
 * Login management
 *
 * rev: 20081231 - franciscom - minor refactoring
 *      20081015 - franciscom - access to config parameters following development standard
 **/
require_once('lib/functions/configCheck.php');
checkConfiguration();
require_once('config.inc.php');
require_once('common.php');
require_once('doAuthorize.php');

$op = doDBConnect($db);
//@TODO: schlundus, this kind of code should be contained within doDBConnect!
if (!$op['status'])
{
	$smarty = new TLSmarty();
	$smarty->assign('title', lang_get('fatal_page_title'));
	$smarty->assign('content', $op['dbms_msg']);
	$smarty->display('workAreaSimple.tpl'); 
	tLog('Connection fail page shown.','ERROR'); 
	exit();
}

$args=init_args();

if(!is_null($args->login))
{
	doSessionStart();
	unset($_SESSION['basehref']);
	setPaths();
	
	if(doAuthorize($db,$args->login,$args->pwd,$msg) < tl::OK)
	{
		if (!$msg)
		{
			$args->note = lang_get('bad_user_passwd');
		}
		else
		{
			$args->note = $msg;
		}	
	}
	else
	{
		logAuditEvent(TLS("audit_login_succeeded",$args->login,
		                  $_SERVER['REMOTE_ADDR']),"LOGIN",$_SESSION['currentUser']->dbID,"users");
		redirect($_SESSION['basehref']."index.php".($args->preqURI ? "?reqURI=".urlencode($args->preqURI) :""));
		exit();
	}
}


$logPeriodToDelete = config_get('removeEventsOlderThan');
$g_tlLogger->deleteEventsFor(null, strtotime("-{$logPeriodToDelete} days UTC"));

$authCfg = config_get('authentication');

$gui = new stdClass();
$gui->note = $args->note;
$gui->reqURI = $args->reqURI ? $args->reqURI : $args->preqURI;
$gui->securityNotes = getSecurityNotes($db);
$gui->external_password_mgmt = ('LDAP' == $authCfg['method']) ? 1 : 0;
$gui->login_disabled = ($gui->external_password_mgmt && !checkForLDAPExtension()) ? 1:0;
$gui->user_self_signup = config_get('user_self_signup');

$smarty = new TLSmarty();
$smarty->assign('gui', $gui);

// $smarty->assign('g_user_self_signup', config_get('user_self_signup'));
// $smarty->assign('securityNotes',$securityNotes);
// $smarty->assign('note',$args->note);
// $smarty->assign('reqURI',$args->reqURI ? $args->reqURI : $args->preqURI);
// $smarty->assign('login_disabled', $login_disabled);
// $smarty->assign('external_password_mgmt', $external_password_mgmt);
$smarty->display('login.tpl');


/*
  function: 

  args:
  
  returns: 

*/
function init_args()
{
    $args = new stdClass();
    $_REQUEST = strings_stripSlashes($_REQUEST);
    
    $args->note = isset($_REQUEST['note']) ? $_REQUEST['note'] : null;
    $args->login = isset($_REQUEST['tl_login']) ? trim($_REQUEST['tl_login']) : null;
    $args->pwd = isset($_REQUEST['tl_password']) ? $_REQUEST['tl_password'] : null;

    $args->reqURI = isset($_REQUEST['req']) ? $_REQUEST['req'] : null;
    $args->preqURI = (isset($_REQUEST['reqURI']) && strlen($_REQUEST['reqURI'])) ? $_REQUEST['reqURI'] : null;

    switch($args->note)
    {
    	case 'expired':
    		if(!isset($_SESSION))
    		{
    			session_start();
    		}
    		session_unset();
    		session_destroy();
    		$args->note = lang_get('session_expired');
    		$args->reqURI = null;
    		break;
    		
    	case 'first':
    		$args->note = lang_get('your_first_login');
    		$args->reqURI = null;
    		break;
    		
    	case 'lost':
    		$args->note = lang_get('passwd_lost');
    		$args->reqURI = null;
    		break;
    		
    	default:
    		$args->note = lang_get('please_login');
    		break;
    }
  
    return $args;
}

?>
