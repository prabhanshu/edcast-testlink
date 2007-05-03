<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later. 
 *  
 * @filesource $RCSfile: reqSpecAnalyse.php,v $
 * @version $Revision: 1.9 $
 * @modified $Date: 2007/05/03 20:44:29 $ by $Author: schlundus $
 * @author Martin Havlat
 * 
 * Analyse coverage of a req. specification.
 */
require_once("../../config.inc.php");
require_once("common.php");
require_once('requirements.inc.php');
testlinkInitPage($db);

$idSRS = isset($_GET['idSRS']) ? strings_stripSlashes($_GET['idSRS']) : null;
$tproject_id = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;

//get list of ReqSpec
$arrReqSpec = getOptionReqSpec($db,$tproject_id);

//get first ReqSpec if not defined
if (!$idSRS && count($arrReqSpec))
{
	reset($arrReqSpec);
	$idSRS = key($arrReqSpec);
	tLog('Set a first available SRS ID: ' . $idSRS);
}

// collect REQ data
$arrCoverage = getReqCoverage_general($db,$idSRS);
$arrMetrics = getReqMetrics_general($db,$idSRS);

$smarty = new TLSmarty;
$smarty->assign('arrMetrics', $arrMetrics);
$smarty->assign('arrCoverage', $arrCoverage);
$smarty->assign('arrReqSpec', $arrReqSpec);
$smarty->assign('selectedReqSpec', $idSRS);
$smarty->assign('modify_req_rights', has_rights($db,"mgt_modify_req")); 
$smarty->display('reqSpecAnalyse.tpl');
?>
