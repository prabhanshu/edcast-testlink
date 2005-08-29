<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 *
 * Filename $RCSfile: adminProductNew.php,v $
 *
 * @version $Revision: 1.3 $
 * @modified $Date: 2005/08/29 11:13:46 $
 *
 * @author Martin Havlat
 *
 * This page create New products.
 *
 * 20050829 - scs - moved POST params to the top of the script
 *
**/
require_once('../../config.inc.php');
require_once('../functions/common.php');
require_once('../functions/product.inc.php');
testlinkInitPage();

$bNewProduct = isset($_POST['newProduct']) ? 1 : 0;
$name = isset($_POST['name']) ? strings_stripSlashes($_POST['name']) : null;
$color = isset($_POST['color']) ? strings_stripSlashes($_POST['color']) : TL_BACKGROUND_DEFAULT;
$optReq = isset($_POST['optReq']) ? intval($_POST['optReq']) : 0;

$createResult = null;
if ($bNewProduct)
{
	if (strlen($name))
	{
		if (createProduct($name,$color,$optReq))
			$createResult = 'ok';
		else
			$createResult = lang_get('refer_to_log');
	}
	else
		$createResult = lang_get('info_product_name_empty');
}

$smarty = new TLSmarty();
$smarty->assign('sqlResult', $createResult);
$smarty->assign('name', $name);
$smarty->assign('defaultColor', TL_BACKGROUND_DEFAULT);
$smarty->display('adminProductNew.tpl');
?>
