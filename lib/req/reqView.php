<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later. 
 *  
 * @filesource $RCSfile: reqView.php,v $
 * @version $Revision: 1.4 $
 * @modified $Date: 2006/01/09 07:16:16 $ by $Author: franciscom $
 * @author Martin Havlat
 * 
 * Screen to view content of requirement.
 * 20060103 - scs - ADOdb changes
 */
 
////////////////////////////////////////////////////////////////////////////////

require_once('../../config.inc.php');
require_once('common.php');
require_once('requirements.inc.php');
require_once('users.inc.php');


// init page 
testlinkInitPage($db);

$idReq = isset($_GET['idReq']) ? strings_stripSlashes($_GET['idReq']) : null;

$arrReq = getReqData($db,$idReq);
$arrReq['author'] = getUserName($db,$arrReq['id_author']);
$arrReq['modifier'] = getUserName($db,$arrReq['id_modifier']);
$arrReq['coverage'] = getTc4Req($db,$idReq);


// smarty
$smarty = new TLSmarty;
$smarty->assign('title', $arrReq['title']);
$smarty->assign('arrReq', $arrReq);
$smarty->assign('internalTemplate', 'inc_reqView.tpl');
$smarty->assign('modify_req_rights', has_rights($db,"mgt_modify_req")); 
$smarty->assign('selectReqStatus', $arrReqStatus);

$smarty->display('infoWindow.tpl');

?>
