<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: keywordsexport.php,v $
 *
 * @version $Revision: 1.3 $
 * @modified $Date: 2006/02/15 08:49:20 $ by $Author: franciscom $
 *
 * This page this allows users to export keywords. 
 *
**/
require_once("../../config.inc.php");
require_once("../functions/csv.inc.php");
require_once("../functions/xml.inc.php");
require_once("../functions/common.php");
require_once("keywords.inc.php");
testlinkInitPage($db);

$bExport = isset($_POST['export']) ? $_POST['export'] : null;
$exportType = isset($_POST['exportType']) ? $_POST['exportType'] : null;

$prodID = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
$productName = $_SESSION['testprojectName'];

if ($bExport)
{
	$keywords = selectKeywords($db,$prodID);
	switch($exportType)
	{
		case 'CSV':
			$pfn = "exportKeywordDataToCSV";
			$fileName = 'keywords.csv';
			break;
		case 'XML':
			$pfn = "exportKeywordDataToXML";
			$fileName = 'keywords.xml';
			break;
	}
	if ($pfn)
	{
		$content = $pfn($keywords);
		downloadContentsToFile($content,$fileName);
		exit();
	}
}


$smarty = new TLSmarty;
$smarty->assign('productName', $productName);
$smarty->assign('productID', $prodID);
$smarty->assign('importTypes',$g_keywordImportTypes);
$smarty->display('keywordsexport.tpl');

?>