<?php
/* TestLink Open Source Project - http://testlink.sourceforge.net/ */
/* $Id: searchData.php,v 1.3 2005/08/22 07:00:51 franciscom Exp $ */
/* Purpose:  This page presents the search results. 
 *
 * 
 * @ author: Francisco Mancardi - 20050821
 * changes to use template customization
 * (trying to reduce code redundancy)
 *
 * @ author: Francisco Mancardi - 20050810
 * deprecated $_SESSION['product'] removed
 */
require('../../config.inc.php');
require("../functions/common.php");
require_once("../../lib/functions/lang_api.php");
testlinkInitPage();

$arrTc = array();
if(!$_POST['submit'])
	tlog('searchData.php requires a submit data');

//Assign the values of the posts to variables
$title = isset($_POST['title']) ? mysql_escape_string(strings_stripSlashes($_POST['title'])) : null;
$summary = isset($_POST['summary']) ? mysql_escape_string(strings_stripSlashes($_POST['summary'])) : null;
$steps = isset($_POST['steps']) ? mysql_escape_string(strings_stripSlashes($_POST['steps'])) : null;
$exresult = isset($_POST['exresult']) ? mysql_escape_string(strings_stripSlashes($_POST['exresult'])) : null;
$key = isset($_POST['key']) ? mysql_escape_string(strings_stripSlashes($_POST['key'])) : null;
$TCID = isset($_POST['TCID']) ? mysql_escape_string(strings_stripSlashes($_POST['TCID'])) : 0;

$product = isset($_SESSION['productID']) ? $_SESSION['productID'] : 0;
if ($product)
{
	$sqlTC = " SELECT mgttestcase.id,title,summary,steps,exresult,keywords,version," .
	         " author,create_date,reviewer,modified_date,catid,TCorder " .
	         " FROM mgttestcase, mgtcategory,	mgtcomponent " .
	         " WHERE prodid = ".$product.
 			     " AND mgtcategory.compID = mgtcomponent.id " .
 			     " AND mgttestcase.catID = mgtcategory.id " .
 			     " AND mgttestcase.id like '%" . 	$TCID . "%' " .
 			     " AND title like '%" . $title . "%' " .
 			     " AND summary like '%" . $summary . "%' " . 
 			     " AND steps like '%" . $steps . "%' " .
 			     " AND exresult like '%" . $exresult."%'";

	//keywordlist always have a trailing slash, so there are only two cases 
	//to consider the keyword is the first in the 	list
	//or its in the middle of list 		 
	if($key != 'none')
	{
		$sqlTC .= " AND (keywords LIKE '%,{$key},%' OR keywords like '{$key},%')";
	}	
	$sqlTC .= " ORDER BY title";

	$result = do_mysql_query($sqlTC);
	
	
	while ($row = mysql_fetch_assoc($result)) //loop through all categories
	{
		$row['keywords'] = substr($row['keywords'], 0, -1);
		array_push($arrTc, $row);
	}
}

/* 20050821 - fm
if (!sizeof($arrTc))
{
	$arrTc = null;
}
*/

$smarty = new TLSmarty;
$smarty->assign('modify_tc_rights', 'no');
if(has_rights("mgt_modify_tc"))
{
	$smarty->assign('modify_tc_rights', 'yes');
}

$smarty->assign('testcase', $arrTc);

// 20050821 - fm
global $tpl;
$smarty->display($tpl['tcSearchView']);
?>
