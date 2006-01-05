<?
/** TestLink Open Source Project - http://testlink.sourceforge.net/ 
* $Id: builds.inc.php,v 1.14 2006/01/05 07:30:33 franciscom Exp $
* 
* @author Martin Havlat
*
* Functions for Test Plan management - build related
*/
require_once('../../config.inc.php');
require_once("../functions/common.php");

/**
 * Collect all builds for the Test Plan
 *
 * 20051002 - fm - refactoring
 * 20050921 - fm - refactoring
 */
function getBuilds(&$db,$idPlan, $order_by="ORDER BY build.id DESC")
{
 	$sql = "SELECT build.id, name FROM build WHERE projid = " . $idPlan;
 	
 	if ( strlen(trim($order_by)) )
 	{
 		$sql .= " " . $order_by;
 	}
	return getBuildInfo($db,$sql);
}

/**
 * @author kl - 10/13/2005
 * return a comma delimited list of build.id's which are part of a test plan
 *
 */
function get_cs_builds(&$db,$idPlan, $order_by="ORDER BY build.id DESC")
{
  $comma_separated = null;
  $arrAllBuilds = getBuilds($db,$idPlan, $order_by);
  if ($arrAllBuilds){
    $arrAllKeys = array_keys($arrAllBuilds);
    $comma_separated = implode("','", $arrAllKeys);
    // add single quotes to front and back
    $comma_separated = "'" . $comma_separated . "'";
  }
  return $comma_separated;
}

// 20051002 - fm - refactoring
// added by 09242005 kl - i want the build.build fields in the array
function getBuilds_build(&$db,$idPlan){
	$sql = "SELECT build.id, build.name FROM build WHERE projid = " . $idPlan . " ORDER BY build.id DESC";
	return getBuildInfo($db,$sql);
}

function getBuildsAndNotes(&$db,$idPlan)
{
  	$sql = "SELECT build.id,note FROM build WHERE projid = " . $idPlan . " ORDER BY build.id DESC";
	return getBuildInfo($db,$sql);
}


function getBuildInfo(&$db,$sql)
{
	$arrBuilds = array();
 	$result = $db->exec_query($sql) or die($db->error_msg());

	while ($myrow = $db->fetch_array($result))
	{
		$arrBuilds[$myrow[0]] = $myrow[1];
  }

 	return $arrBuilds;
}
//20051203 - scs - correct wrong column name while deleting bugs and results
function deleteTestPlanBuild($testPlanID,$buildID)
{
	$result = 1;
	if ($testPlanID)
	{ 
		$catIDs = null;
		getTestPlanCategories($testPlanID,$catIDs);
	
		// 20050914 - fm
		$tcIDs = getCategories_TC_ids($catIDs);	
		
		if (sizeof($tcIDs))
		{
			$tcIDList = implode(",",$tcIDs);
			
			$query = "DELETE FROM bugs WHERE tcid IN ({$tcIDList}) AND build_id = {$buildID}";
			$result = $result && do_sql_query($query);
			
			
			$query = "DELETE FROM results WHERE tcid IN ({$tcIDList}) AND build_id = {$buildID}";
			$result = $result && do_sql_query($query);
		}
	
		$query = "DELETE FROM build WHERE build.id={$buildID} AND projid=" . $testPlanID;
		$result = $result && do_sql_query($query);
	}
	return $result ? 1 : 0;
}


/* 20051005 - fm */
function getBuild_by_id($buildID)
{
  $sql = "SELECT build.* FROM build WHERE build.id = " . $buildID;
  $result = do_sql_query($sql);
  $myrow = $GLOBALS['db']->fetch_array($result);
	return($myrow);
}


?>