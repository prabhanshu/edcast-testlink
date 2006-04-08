<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later. 
 *  
 * @filesource $RCSfile: reqView.php,v $
 * @version $Revision: 1.7 $
 * @modified $Date: 2006/04/08 19:51:42 $ by $Author: schlundus $
 * @author Martin Havlat
 * 
 * Screen to view content of requirement.
 */
require_once('../../config.inc.php');
require_once('common.php');
require_once('requirements.inc.php');
require_once('users.inc.php');
testlinkInitPage($db);

$idReq = isset($_GET['idReq']) ? strings_stripSlashes($_GET['idReq']) : null;

$arrReq = getReqData($db,$idReq);
$arrReq['author'] = getUserName($db,$arrReq['author_id']);
$arrReq['modifier'] = getUserName($db,$arrReq['modifier_id']);
$arrReq['coverage'] = getTc4Req($db,$idReq);


$smarty = new TLSmarty();
$smarty->assign('title', $arrReq['title']);
$smarty->assign('arrReq', $arrReq);
$smarty->assign('internalTemplate', 'inc_reqView.tpl');
$smarty->assign('modify_req_rights', has_rights($db,"mgt_modify_req")); 
$smarty->assign('selectReqStatus', $arrReqStatus);
$smarty->display('infoWindow.tpl');
?>
