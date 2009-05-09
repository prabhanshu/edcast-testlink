<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: attachmentdelete.php,v $
 *
 * @version $Revision: 1.11 $
 * @modified $Date: 2009/05/09 17:59:19 $ by $Author: schlundus $
 *
 * Deletes an attachment by a given id
 */
require_once('../../config.inc.php');
require_once('../functions/common.php');
require_once('../functions/attachments.inc.php');
testlinkInitPage($db);

if (!config_get("attachments")->enabled)
	exit();

$args = init_args();	
$bDeleted = false;
if ($args->id)
{
	$attachmentRepository = tlAttachmentRepository::create($db);
	$attachmentInfo = $attachmentRepository->getAttachmentInfo($args->id);
	if ($attachmentInfo && checkAttachmentID($db,$args->id,$attachmentInfo))
	{
		$bDeleted = $attachmentRepository->deleteAttachment($args->id,$attachmentInfo);
		if ($bDeleted)
			logAuditEvent(TLS("audit_attachment_deleted",$attachmentInfo['title']),"DELETE",$id,"attachments");
	}
}

$smarty = new TLSmarty();
$smarty->assign('bDeleted',$bDeleted);
$smarty->display('attachmentdelete.tpl');

function init_args()
{
	//the id (attachments.id) of the attachment to be downloaded
	$iParams = array(
		"id" => array(tlInputParameter::INT_N),
	);
	$args = new stdClass();
	$pParams = G_PARAMS($iParams,$args);
	
	
	return $args;
}
?>
