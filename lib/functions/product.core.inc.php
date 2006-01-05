<?
/** TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * 
 * @filesource $RCSfile: product.core.inc.php,v $
 * @version $Revision: 1.3 $
 * @modified $Date: 2006/01/05 07:30:33 $
 * @author Martin Havlat
 *
 * Core Functions for Product management (get data)
 * To edit product see ./product.inc.php
 * 
 */


function getProducts(&$db,$id = null)
{
	$sql = "SELECT * FROM mgtproduct";
	
	if (!is_null($id)) {
		$sql .= " WHERE id = " . $id;
	}
	
	return selectData($db,$sql);
}


/** collect all information about Product */
function getProduct(&$db,$id)
{
	$products = getProducts($db,$id);

	return $products ? $products[0] : null;
}


function getAllProductsBut(&$db,$id,&$products)
{
	$sql = "SELECT id, name FROM mgtproduct WHERE id !=" . $id;
	$products = selectData($db,$sql);

	return (!empty($products)) ? 1 : 0;
}	

/** get option list of products; all for admin and active for others 

rev :
     20050810 - fm
     refactoring
     
*/
function getOptionProducts(&$db)
{
	$arrProducts = array();
	
	// 20050810 - fm
	$sql =  "SELECT id,name,active FROM mgtproduct ";
	$order_by = " ORDER BY name";
	
	if (has_rights('mgt_modify_product') == 'yes') {
		$sql .= $order_by;
		$arrTemp = selectData($db,$sql);
		if (sizeof($arrTemp))
		{
			foreach($arrTemp as $oneProduct)
			{
				if ($oneProduct['active']) {
					$noteActive = '';
				} else {
					$noteActive = '* ';
				}
				$arrProducts[$oneProduct['id']] = $noteActive . $oneProduct['name'];
			}
		}
		
	} else {
		$sql .= " WHERE active=1 " . $order_by;
		$arrProducts = selectOptionData($db,$sql);
	}
	
	return $arrProducts;
}
?>