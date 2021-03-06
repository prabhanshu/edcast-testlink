<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: attachmentdelete.php,v $
 *
 * @version $Revision: 1.18 $
 * @modified $Date: 2011/01/10 15:38:55 $ by $Author: asimon83 $
 *
 * Deletes an attachment by a given id
 */
require_once('../../config.inc.php');
require_once('../functions/common.php');
require_once('../functions/attachments.inc.php');
testlinkInitPage($db,false,false,"checkRights");

$args = init_args();	
$deleteDone = false;
if ($args->id)
{
	$attachmentRepository = tlAttachmentRepository::create($db);
	$attachmentInfo = $attachmentRepository->getAttachmentInfo($args->id);
	if ($attachmentInfo && checkAttachmentID($db,$args->id,$attachmentInfo))
	{
		$deleteDone = $attachmentRepository->deleteAttachment($args->id,$attachmentInfo);
		if ($deleteDone)
		{
			logAuditEvent(TLS("audit_attachment_deleted",
			              $attachmentInfo['title']),"DELETE",$args->id,"attachments");
		}	
	}
}

$smarty = new TLSmarty();
$smarty->assign('bDeleted',$deleteDone);
$smarty->display('attachmentdelete.tpl');


/**
 * @return object returns the arguments for the page
 */
function init_args()
{
	//the id (attachments.id) of the attachment to be deleted
	$iParams = array(
		"id" => array(tlInputParameter::INT_N),
	);
	$args = new stdClass();
	G_PARAMS($iParams,$args);
	
	// BUGID 4066 - take care of proper escaping when magic_quotes_gpc is enabled
	$_REQUEST=strings_stripSlashes($_REQUEST);

	return $args;
}


/**
 * @param $db resource the database connection handle
 * @param $user the current active user
 * 
 * @return boolean returns true if the page can be accessed
 */
function checkRights(&$db,&$user)
{
	return (config_get("attachments")->enabled);
}
?>