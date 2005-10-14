<?
/** TestLink Open Source Project - http://testlink.sourceforge.net/ 
* $Id: builds.inc.php,v 1.10 2005/10/14 19:38:54 kevinlevy Exp $
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
function getBuilds($idPlan, $order_by="ORDER BY build.id DESC")
{
 	$sql = "SELECT build.id, name FROM build WHERE projid = " . $idPlan;
 	
 	if ( strlen(trim($order_by)) )
 	{
 		$sql .= " " . $order_by;
 	}
	return getBuildInfo($sql);
}

/**
 * @author kl - 10/13/2005
 * return a comma delimited list of build.id's which are part of a test plan
 *
 */
function get_cs_builds($idPlan, $order_by="ORDER BY build.id DESC")
{
  $comma_separated = null;
  $arrAllBuilds = getBuilds($idPlan, $order_by);
  if ($arrAllBuilds){
    $arrAllKeys = array_keys($arrAllBuilds);
    $comma_separated = implode("','", $arrAllKeys);
  }
  return $comma_separated;
}

// 20051002 - fm - refactoring
// added by 09242005 kl - i want the build.build fields in the array
function getBuilds_build($idPlan){
	$sql = "SELECT build.id, build.name FROM build WHERE projid = " . $idPlan . " ORDER BY build.id DESC";
	return getBuildInfo($sql);
}

function getBuildsAndNotes($idPlan)
{
  	$sql = "SELECT build.id,note FROM build WHERE projid = " . $idPlan . " ORDER BY build.id DESC";
	return getBuildInfo($sql);
}


function getBuildInfo($sql)
{
	$arrBuilds = array();
 	$result = do_mysql_query($sql) or die(mysql_error());

	while ($myrow = mysql_fetch_array($result))
	{
		$arrBuilds[$myrow[0]] = $myrow[1];
  }

 	return $arrBuilds;
}

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
			
			$query = "DELETE FROM bugs WHERE tcid IN ({$tcIDList}) AND build.id = {$buildID}";
			$result = $result && do_mysql_query($query);
			
			
			$query = "DELETE FROM results WHERE tcid IN ({$tcIDList}) AND build.id = {$buildID}";
			$result = $result && do_mysql_query($query);
		}
	
		$query = "DELETE FROM build WHERE build.id={$buildID} AND projid=" . $testPlanID;
		$result = $result && do_mysql_query($query);
	}
	return $result ? 1 : 0;
}


/* 20051005 - fm */
function getBuild_by_id($buildID)
{
  $sql = "SELECT build.* FROM build WHERE build.id = " . $buildID;
  $result = do_mysql_query($sql);
  $myrow = mysql_fetch_assoc($result);
	return($myrow);
}


?>