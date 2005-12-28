<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: adminProductEdit.php,v $
 *
 * @version $Revision: 1.8 $
 * @modified $Date: 2005/12/28 07:34:54 $
 *
 * @author Martin Havlat
 *
 * This page allows users to edit/delete products.
 * 
 * @todo Verify dependency before delete project 
 *
 * 20051211 - fm - poor workaround for the delete loop - BUGID 180 Unable to delete Product
 * 20050908 - fm - BUGID 0000086
 * 20050831 - scs - moved POST to top, some small changes
**/
include('../../config.inc.php');
require_once('common.php');
require_once('product.inc.php');

global $db;
testlinkInitPage(true);


$updateResult = null;
$action = 'no';
$show_prod_attributes = 'yes';

$error = null;
$smarty = new TLSmarty();

$_GET = strings_stripSlashes($_GET);
$_POST = strings_stripSlashes($_POST);
$bDeleteProduct     = isset($_GET['deleteProduct']) ? 1 :  0;
$bEditProduct       = isset($_POST['editProduct']) ? 1 : 0;
$bInactivateProduct = isset($_POST['inactivateProduct']) ? 1 : 0;
$bActivateProduct   = isset($_POST['activateProduct']) ? 1 : 0;


// Product ID and name
$name = isset($_GET['name']) ? $_GET['name'] : null;
if (is_null($name))
{
	$name = isset($_POST['name']) ? $_POST['name'] : null;
}
	
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (is_null($id))
{
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
}	


$color = isset($_POST['color']) ? $_POST['color'] : null;
$optReq = isset($_POST['optReq']) ? intval($_POST['optReq']) : 0;

if (isset($_SESSION['productID']))
{
	tLog('Edit product: ' . $_SESSION['productID'] . ': ' . $_SESSION['productName']);
}

if ($bDeleteProduct)
{
  // 20051211 - fm - 
  $show_prod_attributes = 'no';
	$sql = "SELECT id FROM mgtproduct WHERE id=" . $id;
	$result = do_sql_query($sql);

	if( $GLOBALS['db']->num_rows($result) == 1 )
  {
  	if (deleteProduct($id,$error))
  	{
  		$updateResult = lang_get('info_product_was_deleted');
  		tLog('Product: [' . $id . '] ' . $name . ' was deleted.', 'INFO');
  	} 
  	else 
  	{
  		$updateResult = lang_get('info_product_not_deleted_check_log') . ' ' . $error;
  		tLog('Product: [' . $id . '] ' . $name . " wasn't deleted.\t", 'ERROR');
  	}
   	$action = 'delete';
  }
}
else
{
	if ($bEditProduct)
	{
		$name_ok = 1;
		if ($name_ok && !strlen($name))
		{
			$updateResult = lang_get('info_product_name_empty');
			$name_ok = 0;
		}
		
		// BUGID 0000086
		if ($name_ok && !check_string($name,$g_ereg_forbidden))
		{
			$updateResult = lang_get('string_contains_bad_chars');
			$name_ok = 0;
		}
		if ($name_ok && $id)
		{
			$updateResult = updateProduct($id, $name, $color, $optReq);
		}
		$action = 'updated';
		$show_prod_attributes = 'yes';
	}
	else if ($bInactivateProduct)
	{
		if (activateProduct($id, 0))
		{
			$updateResult = lang_get('info_product_inactivated');
			tLog('Product: ' . $id . ': ' . $name . 'was inactivated.', 'INFO');
		}
		$action = 'inactivate';
		$show_prod_attributes = 'yes';
	}
	else if ($bActivateProduct)
	{
		if (activateProduct($id, 1))
		{
			$updateResult = lang_get('info_product_activated');
			tLog('Product: ' . $id . ': ' . $name . 'was activated.', 'INFO');
		}
		$action = 'activate';
		$show_prod_attributes = 'yes';
	}
	
	if (isset($_SESSION['productID']))
	{
		$productData = getProduct($_SESSION['productID']);
		if ($productData)
		{
			$smarty->assign('found', 'yes');
			$smarty->assign('id', $productData['id']);
			$name = $productData['name'];
			$smarty->assign('color', $productData['color']);
			$smarty->assign('active', $productData['active']);
			$smarty->assign('reqs_default', $productData['option_reqs']);
		}
		else
		{
			$updateResult = lang_get('info_failed_loc_prod');
		}	
	}
	else
	{
		$updateResult = lang_get('info_no_more_prods');
	}	
}


$smarty->assign('action', $action);
$smarty->assign('sqlResult', $updateResult);
$smarty->assign('name', $name);
$smarty->assign('productName', isset($_SESSION['productName']) ? $_SESSION['productName'] : '');

// 20051211 - fm - poor workaround
$smarty->assign('show_prod_attributes', $show_prod_attributes);

$smarty->display('adminProductEdit.tpl');
?>