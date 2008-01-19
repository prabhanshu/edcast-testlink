<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *  
 * @version $Id: archiveData.php,v 1.31 2008/01/19 17:51:21 franciscom Exp $
 * @author Martin Havlat
 *  
 * Allows you to show test suites, test cases.
 * Normally launched from tree navigator.
 *
 * rev :
 *      20080119 - franciscom - code clean up
 *      20070930 - franciscom - REQ - BUGID 1078
 * 
 */
require_once('../../config.inc.php');
require_once('common.php');
require_once("../functions/attachments.inc.php");
testlinkInitPage($db);

$template_dir='testcases/';
$args=init_args();

// load data and show template
$smarty = new TLSmarty();
$smarty->assign('page_title',lang_get('container_title_' . $args->feature));
switch($args->feature)
{
	case 'testproject':
		$item_mgr = new testproject($db);
		$smarty->assign('id',$args->id);
		$attachments = getAttachmentInfosFrom($item_mgr,$args->id);
		$smarty->assign('attachmentInfos',$attachments);
   	$item_mgr->show($smarty,$template_dir,$args->id);
		break;

	case 'testsuite':
		$smarty->assign('id',$args->id);
		$item_mgr = new testsuite($db);
		$attachments = getAttachmentInfosFrom($item_mgr,$args->id);
		$smarty->assign('attachmentInfos',$attachments);
		
	    $_SESSION['tcspec_refresh_on_action'] = isset($_REQUEST['tcspec_refresh_on_action'])? "yes":"no";
		$item_mgr->show($smarty,$template_dir,$args->id);
		break;

	case 'testcase':
		$item_mgr = new testcase($db);
    if( !is_null($args->targetTestCase) )
    {
       // need to get internal Id from External ID
       $cfg = config_get('testcase_cfg');
       $args->id=$item_mgr->getInternalID($args->targetTestCase,$cfg->glue_character); 
    }

		$attachments = getAttachmentInfosFrom($item_mgr,$args->id);
		$attachmentsTpl[$args->id] = $attachments;
		
		$smarty->assign('id',$args->id);
		$smarty->assign('attachments',$attachmentsTpl);
				
		$no_msg = '';
		$no_action = '';
		$no_user_feedback = '';

		$spec_cfg = config_get('spec_cfg');
		$do_refresh_yes_no=$spec_cfg->automatic_tree_refresh?"yes":"no";
		if(isset($_SESSION['tcspec_refresh_on_action']))
			$do_refresh_yes_no=$_SESSION['tcspec_refresh_on_action'];
    	
	    // 20070930 - franciscom - REQ - BUGID 1078
    	// added two arguments on call.
		$item_mgr->show($smarty,$template_dir,$args->id,$args->user_id,TC_ALL_VERSIONS,
		                $no_action,$no_msg,$do_refresh_yes_no,$no_user_feedback,!$args->allow_edit);
		break;

	default:
		tLog('$_GET["edit"] has invalid value: ' . $args->feature , 'ERROR');
		trigger_error($_SESSION['currentUser']->login.'> $_GET["edit"] has invalid value.', E_USER_ERROR);
}
?>

<?php
function init_args()
{
    $args->user_id = isset($_SESSION['userID']) ? $_SESSION['userID'] : 0;
    $args->feature = isset($_REQUEST['edit']) ? $_REQUEST['edit'] : null;
    $args->id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;
    $args->targetTestCase = isset($_REQUEST['targetTestCase']) ? $_REQUEST['targetTestCase'] : null;
    $args->allow_edit = isset($_REQUEST['allow_edit']) ? intval($_REQUEST['allow_edit']) : 1;
    return $args;  
}
?>