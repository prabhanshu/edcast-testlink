<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 *
 * Filename $RCSfile: exec.inc.php,v $
 *
 * @version $Revision: 1.39 $
 * @modified $Date: 2008/01/31 22:15:47 $ $Author: schlundus $
 *
 * @author Martin Havlat
 *
 * Functions for execution feature (add test results) 
 *
 *
 * 20051119  - scs - added fix for 227
 * 20060311 - kl - some modifications to SQL queries dealing with 1.7
 *                 builds table in order to comply with new 1.7 schema
 *
 * 20060528 - franciscom - adding management of bulk update
 * 20060916 - franciscom - added write_execution_bug()
 *                               get_bugs_for_exec()
 *
 * 20070105 - franciscom - interface changes write_execution()
 * 20070222 - franciscom - BUGID 645 createResultsMenu()
 * 20070617 - franciscom - BUGID     insert_id() problems for Postgres and Oracle?
 *
**/
require_once('common.php');


/**
 * Function just grabs number of builds
 *
 * @param numeric test plan ID
 * @return integer Count of Builds
 * 20060311 - kl - adjusted SQL for 1.7 schema
 */  
function buildsNumber(&$db,$tpID=0)
{
	$sql = "SELECT count(*) AS num_builds FROM builds WHERE builds.testplan_id = " . $tpID;
	$buildCount=0;
	if ($tpID)
	{
		$result = $db->exec_query($sql);
		if ($result)
		{
			$myrow = $db->fetch_array($result);
			$buildCount = $myrow['num_builds'];
		}
	}
	return $buildCount;
}

/** 
 * This code here displays the keyword dropdown box for Test Plan. It's fairly interesting code
 * What it does is searches through all of the currently viewed testplans test cases and puts together
 * all of the unique keywords from each testcase. It then builds a dropdown box to dispaly them
 * @todo rewrite this to use selectOptionData($sql) 
 *
 * @param $idPlan
 *
 * 20050807 - fm
 * added $idPlan to remove global coupling via _SESSION
 */
function filterKeyword(&$db,$idPlan)
{
		//SQL to grab all of the keywords
		$sqlKeyword = "SELECT DISTINCT(keywords) FROM testplans, component, category, testcase WHERE " .
				"testplans.id = " .  $idPlan . " AND testplans.id = component.projid" .
				" AND component.id = category.compid AND category.id = testcase.catid ORDER BY keywords";
		
		//refactored
		$keyArray = buildKeyWordArray($db,$sqlKeyword);
		//Now I begin the display of the keyword dropdown
		$data = '<select name="keyword">'; //Create the select
		$data .= "<option>All</option>"; //Add a none value to the array in case the user doesn't want to sort

		$keyword = isset($_POST['keyword']) ? strings_stripSlashes($_POST['keyword']) : null;
		//For each of the unique values in the keyword array 
		//I want to loop through and display them as an option to select
		foreach($keyArray as $key=>$word)
		{
			//For some reason I'm getting a space.. Now I'll ignore any spaces
			if($word != "")
			{
				//This next if statement makes the keyword field "sticky" 
				//if the user has already selected a keyword and submitted the form
				$sel = '';
				if($word == $keyword)
					$sel = ' selected="selected"';
				$data .= "<option{$sel}>" . htmlspecialchars($word) . "</option>";
			}
		}
		$data .= "</select>";
	return $data;
}

function buildKeyWordArray(&$db,$sqlKeyword)
{
	$resultKeyword = $db->exec_query($sqlKeyword);
	
	//Loop through each of the testcases
	$keyArray = null;
	while ($myrowKeyword = $db->fetch_array($resultKeyword))
	{
		$keyArray .= $myrowKeyword[0].",";
	}
	//removed quotes and separate the list
	$keyArray = explode(",",$keyArray);

	//I need to make sure there are elements in the result 2 array. I was getting an error when I didn't check
	if(count($keyArray))
		$keyArray = array_unique ($keyArray);
	
	return $keyArray;
}


/** Building the dropdown box of results filter */
// 20070222 - franciscom - BUGID 645 
function createResultsMenu()
{
  $map_verbose_status_code=config_get('tc_status');
  $tc_status_verbose_labels = config_get('tc_status_verbose_labels');
  $tc_status_for_ui = config_get('tc_status_for_ui');
  
  // Fixed values, that has to be added always
	$menu_data[$map_verbose_status_code['all']] = lang_get($tc_status_verbose_labels['all']);
	$menu_data[$map_verbose_status_code['not_run']] = lang_get($tc_status_verbose_labels['not_run']);
	
	// loop over tc_status_for_ui, because these are the statuses
	// user can assign while executing test cases
	//
	foreach($tc_status_for_ui as $verbose_status => $status_label)
	{
	   $code=$map_verbose_status_code[$verbose_status];
	   $menu_data[$code]=lang_get($status_label); 
  }

	return $menu_data;
}//end results function


/** Building the dropdown box of builds */
// MHT 200507	refactorization; improved SQL
//
// 20050921 - fm - build.build -> build.id
function createBuildMenu(&$db,$tpID)
{
	$sql = " SELECT builds.id, builds.name " .
	       " FROM builds WHERE builds.testplan_id = " .  $tpID . 
	       " ORDER BY builds.id DESC";
	return $db->fetchColumnsIntoMap($sql,'id','name');
}//end function


/**
 * Add editted test results to database
 *
 * 20050911 - fm - refactoring
 *
 * 20050905 - fm
 * interface changes
 *
 */
// MHT 200507	added conversion of special chars on input - [ 900437 ] table results -- incoherent data ?
function editTestResults(&$db,$user_id, $exec_data, $tplan_id,$build_id,$map_last_exec)
{
	
	$map_tc_status=config_get('tc_status');
	
	$bugInterfaceOn = config_get('bugInterfaceOn');
	$tc_status_map = config_get('tc_status');
	$db_now = $db->db_now();
	
	$num_tc = count($tcData['tc']);
	foreach ($exec_data['tc_version'] as $tcversion_id => $val)
	{
   
    $current_status = $exec_data['status'][$tcversion_id];
    $has_been_executed = ($current_status != $map_tc_status['not_run'] ? TRUE : FALSE);
    $do_write = $has_been_executed;
    
    if( $has_been_executed )
    {
      $status_changed=($current_status != $map_last_exec[$tcversion_id]['status'] ? TRUE : FALSE);
      
      echo "Status changed ???" . $status_changed;
      $do_write = $status_changed;
    }
    
    if( $do_write )
    { 
    
      $my_notes = $db->prepare_string(trim($exec_data['notes'][$tcversion_id]));		
	  	$sql="INSERT INTO executions
	    	    (build_id,tester_id,status,testplan_id,tcversion_id,execution_ts,notes)
	      	  VALUES ( {$build_id}, {$user_id}, '{$exec_data['status'][$tcversion_id]}',
	      	           {$tplan_id}, {$tcversion_id},{$db_now},'{$my_notes}')";
	    $db->exec_query($sql);  	     
    }
	
	
	}
	
  /*
	$num_tc = count($tcData['tc']);
	
	for ($idx=0; $idx < $num_tc; $idx++ )
	{
		$tcID = $tcData['tc'][$idx];
		$tcNotes = $db->prepare_string(trim($tcData['notes'][$idx])); 
		$tcStatus = $db->prepare_string($tcData['status'][$idx]); 

		$tcBugs = '';
		if ($bugInterfaceOn)
		{
			$tcBugs = isset($tcData['bugs'][$idx]) ? $db->prepare_string($tcData['bugs'][$idx]) : ''; 
		}

		// Does exist a result for this (tcid, build) ?
	  $sql = " SELECT tcid, build_id, notes, status FROM results " .
		       " WHERE tcid=" . $tcID .  
		       " AND build_id=" . $build_id;

	  
		$result = $db->exec_query($sql); 
		$num = $db->num_rows($result); 

 	  // We will only update the results if (notes, status) information has changed ...
		if($num == 1)
		{ 
			$myrow = $db->fetch_array($result);
			if(! ($myrow['notes'] == $tcNotes && $myrow['status'] == $tcStatus) )
			{
				$sql = " UPDATE results " .
				       " SET runby ='" . $login_name . "', " . "status ='" .  $tcStatus . "', " .
				       " notes='" . $tcNotes . "' " .
						   " WHERE tcid=" . $tcID . " AND build_id=" . $build_id;
				$result = $db->exec_query($sql); 
			}
    }
    else
    {
    	// Check to know if we need to insert a new result
			if( !($tcNotes == "" && $tcStatus == $g_tc_status['not_run']) )
			{ 
				$sql = " INSERT INTO results (build_id,daterun,status,tcid,notes,runby) " .
				       " VALUES (" . $build_id . ",CURRENT_DATE(),'" . $tcStatus . 
				       "'," . $tcID . ",'" . $tcNotes . "','" . $login_name . "')";
				$result = $db->exec_query($sql);
      }  
    }
    // -------------------------------------------------------------------------


    // -------------------------------------------------------------------------
    // Update Bug information (delete+insert) 
	  $sqlDelete = "DELETE FROM bugs WHERE tcid=" . $tcID . " and build_id=" . $build_id;
	  $result = $db->exec_query($sqlDelete);

	  $bugArray = strlen($tcBugs) ?  explode(",",$tcBugs) : null;
	  $counter = 0;
	  $num_bugs = count($bugArray);
	  while($counter < $num_bugs)	
	  {

		  $sql = "INSERT INTO bugs (tcid,build_id,bug) VALUES (" . $tcID . ",'" . 
			  	   $build_id . "','" . $bugArray[$counter] . "')";
		  $result = $db->exec_query($sql); 
		  $counter++;
	  }
    // -------------------------------------------------------------------------
	}

	return ("<div class='info'><p>" . lang_get("test_results_submitted") . "</p></div>");
	
	*/
}
// -----------------------------------------------------------------------------

	
/**
 * This function returns data for display test cases
 *
 * @param resource $resultTC Result of SQL query
 * @param string $build Build Id
 * @return array $arrTC
 *
 * @author Francisco Mancardi
 * refactoring removing global coupling (Test Plan ID)
 *
 * @author Andreas Morsing - removed unnecessary code
 */
function createTestInput(&$db,$resultTC,$build_id,$tpID)
{
	global $g_bugInterfaceOn,$g_tc_status;;
	$arrTC = array();
	while ($myrow = $db->fetch_array($resultTC))
	{ 

		//display all the test cases until we run out
		//If the result is empty it leaves the box blank.. This looks weird. 
		//Entering a space if it's blank
 	  $a_keys = array('title','summary','steps','exresult');
    foreach($a_keys as $field_name)
    {
		  if(trim($myrow[$field_name]) == "")
		  {
		    $myrow[$field_name] = "none";
		  }
		}
			
		//This query grabs the results from the build passed in

    // 20050926 - fm
    $sql = " SELECT notes, status FROM results " .
           " WHERE tcid=" . $myrow['tcid'] .
		       " AND build_id=" . $build_id;
    
		$resultStatus = $db->exec_query($sql);
		
		$dataStatus = $db->fetch_array($resultStatus);

		//This query grabs the most recent result
		$sqlRecentResult = " SELECT builds.name AS build_name,status,runby,daterun " .
		                   " FROM results,builds " .
				               " WHERE tcid=" . $myrow['tcid'] . " AND status != '" . $g_tc_status['not_run'] . "' " .
				               " AND results.build_id = build.id " .
				               " AND projid = " . $tpID ." ORDER by build.id " .	"DESC limit 1";
				               
		$dataRecentResult = $db->exec_query($sqlRecentResult);
		$rowRecent = $db->fetch_array($dataRecentResult);
		
		//routine that collect the test cases bugs.
		//Check to see if the user is using a bug system
		$resultBugList = null;
		//20050825 - scs - added code to show the related bugs of the tc
		$bugLinkList = null;
		if($g_bugInterfaceOn)
		{
			global $g_bugInterface ;
			//sql code to grab the appropriate bugs for the test case and build
			//2005118 - scs - fix for 227
			$sqlBugs = "SELECT bug,name FROM bugs,build WHERE bugs.build_id = build.id AND tcid='" . $myrow['tcid'] . "' ";
			$resultBugs = $db->exec_query($sqlBugs);
			
			//For each bug that is found
			while ($myrowBugs = $db->fetch_array($resultBugs))
			{ 
				if (!is_null($resultBugList))
				{
					$resultBugList .= ",";
				}	
				$bugID = $myrowBugs['bug'];
				$buildName = $myrowBugs['name'];
				$resultBugList .= $bugID;
				$bugLinkList[] = array($g_bugInterface->buildViewBugLink($bugID,true),$buildName);
			}
		}
		// add to output array
		$arrTC[] = array( 'id' => $myrow['tcid'],
   						'title' => $myrow['title'],
						'summary' => $myrow['summary'], 
	   					'steps' => $myrow['steps'],
						'outcome' => $myrow['exresult'],
   						'mgttcid' => $myrow['mgttcid'],
						'version' => $myrow['version'],
						'status' => $dataStatus[1],
   						'note' => $dataStatus[0], 
   						'bugs' => $resultBugList, 
						'recentResult' => $rowRecent,
						'bugLinkList' => $bugLinkList,
						);
	}
			
	return $arrTC;	
}
	
	
/** 
* Determine what the build result is and apply the specific color 
* @param string $buildResult [p,f,...]
* @return string CSS class
*/
function defineColor($buildResult)
{
	//Determine what the build result is and apply the specific color
	switch ($buildResult)
	{
		case 'p':
			return "green";
			break;
		case 'f':
			return "red";
			break;
		case 'b':
			return "blue";
			break;
		default:
			return "black";
	}
}


/*
  function: write_execution

  args :
  
  returns: 

  rev :
       20070105 - franciscom - added $tproject_id
*/
function write_execution(&$db,$user_id, $exec_data,$tproject_id,$tplan_id,$build_id,$map_last_exec)
{
	$map_tc_status = config_get('tc_status');
	$bugInterfaceOn = config_get('bugInterfaceOn');
	$db_now = $db->db_now();
	$cfield_mgr=New cfield_mgr($db);
  $cf_prefix=$cfield_mgr->get_name_prefix();
	$len_cfp=strlen($cf_prefix);
  $cf_nodeid_pos=4;
	
	// --------------------------------------------------------------------------------------
	$ENABLED=1;
  $cf_map= $cfield_mgr->get_linked_cfields_at_execution($tproject_id,$ENABLED,'testcase');
  $has_custom_fields=is_null($cf_map) ? 0 : 1;
  // --------------------------------------------------------------------------------------

	// --------------------------------------------------------------
	// extract custom fields id.
	$map_nodeid_array_cfnames=null;
  foreach($exec_data as $input_name => $value)
  {
      if( strncmp($input_name,$cf_prefix,$len_cfp) == 0 )
      {
        $dummy=explode('_',$input_name);
        $map_nodeid_array_cfnames[$dummy[$cf_nodeid_pos]][]=$input_name;
      } 
  }
  // --------------------------------------------------------------
	
	// is a bulk save ???
  if( isset($exec_data['do_bulk_save']) )
  {
      // create structure to use common algoritm
      $item2loop= $exec_data['status'];
  }	
	else
	{
	    $item2loop= $exec_data['save_results'];
	}
	
	foreach ( $item2loop as $tcversion_id => $val)
	{
	  $tcase_id=$exec_data['tc_version'][$tcversion_id];
		$current_status = $exec_data['status'][$tcversion_id];
		$has_been_executed = ($current_status != $map_tc_status['not_run'] ? TRUE : FALSE);
		if($has_been_executed)
		{ 
			$my_notes = $db->prepare_string(trim($exec_data['notes'][$tcversion_id]));		
			$sql = "INSERT INTO executions ".
				     "(build_id,tester_id,status,testplan_id,tcversion_id,execution_ts,notes)".
				     " VALUES ( {$build_id}, {$user_id}, '{$exec_data['status'][$tcversion_id]}',".
				     "{$tplan_id}, {$tcversion_id},{$db_now},'{$my_notes}')";
			$db->exec_query($sql);  	
			
			// 20070617 - franciscom - BUGID : at least for Postgres DBMS table name is needed. 
			//    
			$execution_id=$db->insert_id('executions');
			
      if( $has_custom_fields )
      {
        // test useful when doing bulk update, because some type of custom fields
        // like checkbox can not exist on exec_data
        //
        $hash_cf=null;
        if( isset($map_nodeid_array_cfnames[$tcase_id]) )
        { 
          foreach($map_nodeid_array_cfnames[$tcase_id] as $cf_v)
          {
             $hash_cf[$cf_v]=$exec_data[$cf_v];
          }  
			  }                                     
		    // 20070105 - custom field management
		    $cfield_mgr->execution_values_to_db($hash_cf,$tcversion_id, $execution_id, $tplan_id,$cf_map);
			}                                     
		}
	}
}

/*
  function: write_execution_bug

  args :
  
  returns: 

*/
function write_execution_bug(&$db,$exec_id, $bug_id,$just_delete=false)
{
	// Instead of Check if record exists before inserting, do delete + insert
	$prep_bug_id = $db->prepare_string($bug_id);
	
	$sql = "DELETE FROM execution_bugs " .
	       "WHERE execution_id={$exec_id} " .
	       "AND bug_id='" . $prep_bug_id ."'";
	$result = $db->exec_query($sql);
	
	if(!$just_delete)
	{
    	$sql = "INSERT INTO execution_bugs " .
    	      "(execution_id,bug_id) " .
    	      "VALUES({$exec_id},'" . $prep_bug_id . "')";
    	$result = $db->exec_query($sql);  	     
	}
	return $result ? 1 : 0;
}

// 20060916 - franciscom
function get_bugs_for_exec(&$db,&$bug_interface,$execution_id)
{
  $bug_list=array();
	$sql = "SELECT execution_id,bug_id,builds.name AS build_name " .
	       "FROM execution_bugs,executions,builds ".
	       "WHERE execution_id={$execution_id} " .
	       "AND   execution_id=executions.id " .
	       "AND   executions.build_id=builds.id " .
	       "ORDER BY builds.name,bug_id";
	$map = $db->get_recordset($sql);
	if( !is_null($map) )
  {  	
    	foreach($map as $elem)
    	{
    		$bug_list[$elem['bug_id']]['link_to_bts'] = $bug_interface->buildViewBugLink($elem['bug_id'],GET_BUG_SUMMARY);
    		$bug_list[$elem['bug_id']]['build_name'] = $elem['build_name'];
    	}
  }
  return($bug_list);
}


// 20060916 - franciscom
function get_execution(&$db,$execution_id)
{
	$sql = "SELECT * " .
	       "FROM executions ".
	       "WHERE id={$execution_id} ";
	       
	$map = $db->get_recordset($sql);
  return($map);
}




/*
  function: delete_execution

  args :
  
  returns: 

  rev :
       
*/
function delete_execution(&$db,$exec_id)
{
  $sql=array();
  
  // delete bugs
  $sql[]="DELETE FROM execution_bugs WHERE execution_id = {$exec_id}";

 
  // delete custom field values
  $sql[]="DELETE FROM cfield_execution_values WHERE execution_id = {$exec_id}";
 
  // delete execution 
  $sql[]="DELETE FROM executions WHERE id = {$exec_id}";

  foreach ($sql as $the_stm)
  {
  		$result = $db->exec_query($the_stm);
  }

}
?>
