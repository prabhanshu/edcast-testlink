<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * @filesource $RCSfile: common.php,v $
 * @version $Revision: 1.51 $ $Author: schlundus $
 * @modified $Date: 2006/10/15 19:05:39 $
 *
 * @author 	Martin Havlat
 * @author 	Chad Rosen
 *
 * Common functions: database connection, session and data initialization,
 * maintain $_SESSION data, redirect page, log, etc. 
 *
 * @var array $_SESSION
 * - user related data are adjusted via doAuthorize.php and here (product & test plan)  
 * - has next values: valid (yes/no), user (login name), role (e.g. admin),
 * email, userID, productID, productName, testplan (use rather testPlanID),
 * testPlanID, testPlanName
 *
 *
 **/ 

// 20051227 - fm - ADODB
require_once("database.class.php");

require_once("roles.inc.php");
require_once("product.core.inc.php");
require_once(dirname(__FILE__)."/testproject.class.php");
require_once(dirname(__FILE__)."/testplan.class.php");
require_once(dirname(__FILE__)."/testcase.class.php");
require_once(dirname(__FILE__)."/testsuite.class.php");
require_once(dirname(__FILE__)."/tree.class.php");
require_once(dirname(__FILE__)."/treeMenu.inc.php");

require_once("plan.core.inc.php");
require_once("logging.inc.php");
require_once("lang_api.php");

/** $db is a global used throughout the code when accessing the db. */
$db = 0;


// ----------------------------------------------------------------
/** 
* TestLink connects to the database
*
* @return assoc array
*         aa['status'] = 1 -> OK , 0 -> KO
*         aa['dbms_msg''] = 'ok', or $db->error_msg().
*
* 20050416 - fm
* 
*/
function doDBConnect(&$db)
{
	$result = array('status' => 1, 
					'dbms_msg' => 'ok'
					);
	$db = new database(DB_TYPE);
	$result = $db->connect(DSN, DB_HOST, DB_USER, DB_PASS, DB_NAME);

	if (!$result['status'])
	{
		echo $result['dbms_msg'];
		$result['status'] = 0;
		tLog('Connect to database fails!!! ' . $result['dbms_msg'], 'ERROR');
  	}
  	else
	{
		if (DB_SUPPORTS_UTF8)
		{
			if(DB_TYPE == 'mysql')
			{
				$r = $db->exec_query("SET CHARACTER SET utf8");
				$r = $db->exec_query("SET collation_connection = 'utf8_general_ci'");
			}
		}
	}

 	return $result;
}


// 20050622 mht added options and productID
function setSessionTestProject($tproject_info)
{
	if ($tproject_info)
	{
		/** @todo check if the session product is updated when its modified per projectedit.php.php  */
		$_SESSION['testprojectID'] = $tproject_info['id']; 
		$_SESSION['testprojectName'] = $tproject_info['name'];
		$_SESSION['testprojectColor'] = $tproject_info['color'];
		$_SESSION['testprojectOptReqs'] = isset($tproject_info['option_reqs']) ? $tproject_info['option_reqs'] : null;
		$_SESSION['testprojectOptPriority'] = isset($tproject_info['option_priority']) ? $tproject_info['option_priority'] : null;
		
		tLog("Product was adjusted to [" . $tproject_info['id'] . "]" . $tproject_info['name'], 'INFO');
		tLog("Product features REQ=" . $_SESSION['testprojectOptReqs'] . ", PRIORITY=" . $_SESSION['testprojectOptPriority']);
	}
	else
	{
		unset($_SESSION['testprojectID']);
		unset($_SESSION['testprojectName']);
		unset($_SESSION['testprojectColor']);
		unset($_SESSION['testprojectOptReqs']);
		unset($_SESSION['testprojectOptPriority']);
	}
}


// 20050926 - fm
function setSessionTestPlan($tplan_info)
{
	if ($tplan_info)
	{
		$_SESSION['testPlanId'] = $tplan_info['id'];
		$_SESSION['testPlanName'] = $tplan_info['name'];
		
		tLog("Test Plan was adjusted to '" . $tplan_info['name'] . "' ID(" . $tplan_info['id'] . ')', 'INFO');
	}
	else
	{
		unset($_SESSION['testPlanId']);
		unset($_SESSION['testPlanName']);
	}
}

/**
 * Function set paths
 * @todo solve problems after session expires
 */
// MHT 20050712 create extra function for this; 
function setPaths()
{
	tLog('test ' . getenv('SCRIPT_NAME'));
	if (!isset($_SESSION['basehref']))
		$_SESSION['basehref'] = get_home_url();

	$my_locale = isset($_SESSION['locale']) ?  $_SESSION['locale'] : TL_DEFAULT_LOCALE;
	
	global $g_rpath;
	$g_rpath = array ( 'help' => TL_HELP_RPATH . $my_locale,
	                   'instructions' => TL_HELP_RPATH . $my_locale);
	
	global $g_apath;
	foreach ($g_rpath as $key => $value)
	    $g_apath[$key] = TL_ABS_PATH . $value;
	
	return 1;
}

/** Verify if user is log in. Redirect to login page if not. */
function checkSessionValid()
{
	if (!isset($_SESSION['userID']))
	{
		$ip = getenv ("REMOTE_ADDR");
	    tLog('Invalid session from ' . $ip . '. Redirected to login page.', 'INFO');
		// 20051012 - am - fix for 134
		$fName = "login.php";
		for($i = 0;$i < 5;$i++)
		{
			if (file_exists($fName))
			{
				redirect($_SESSION['basehref'] . $fName."?note=expired","top.location");
				break;
			}
			$fName = "../".$fName;
		}
		exit();
	}
}

/** 
* Function adjust Product and Test Plan to $_SESSION
*
*/
function doInitSelection(&$db)
{
	upd_session_tplan_tproject($db,$_REQUEST);

	return 1;
}

/**
* Function start session
*/
function doSessionStart()
{
	session_set_cookie_params(99999);
	session_start();

	return 1;
}

/** 
* General page initialization procedure 
*
* @param boolean $initProduct (optional) Set true if adjustment of Product or  
* 		Test Plan is required; default is FALSE
* @param boolean $bDontCheckSession (optional) Set to true if no session should be
* 		 started
*/
function testlinkInitPage(&$db,$initProduct = FALSE, $bDontCheckSession = false)
{
	doSessionStart() or die("Could not start session");
	doDBConnect($db) or die("Could not connect to DB");
	
	setPaths();
	set_dt_formats();
	
	if (!$bDontCheckSession)
		checkSessionValid();

	checkUserRights($db);
		
	if ($initProduct)
		doInitSelection($db) or die("Could not set session variables");
}

// 20060107 - fm
function checkUserRights(&$db)
{
	//bypassed as long roles and rights aren't fully defined
	return;
	
	// global $g_userRights;
	$g_userRights = config_get('userRights');
	
	$self = strtolower($_SERVER['SCRIPT_FILENAME']);
	$fName = str_replace(strtolower(str_replace("\\","/",TL_ABS_PATH)),"",$self);

	if (isset($g_userRights[$fName]) && !is_null($g_userRights[$fName]))
	{
		$fRights = $g_userRights[$fName];
		if (has_rights($db,$fRights) != 'yes')
		{
			tLog("Warning: Insufficient rights for ".$self);
			die("Insufficient rights");
		}
		else
			tLog("Sufficient rights for ".$self);
	}

}
/**
 * Redirect page to another one
 *
 * @param   string   URL of required page
 * @param   string   Browser location - use for redirection or refresh of another frame
 * 					 Default: 'location'  
 */
function redirect($path, $level = 'location')
{
	echo "<html><head></head><body>";
	echo "<script type='text/javascript'>";
	echo "$level.href='$path';";
	echo "</script></body></html>";
	exit;
}

function strings_stripSlashes($parameter,$bGPC = true)
{
	if ($bGPC && !ini_get('magic_quotes_gpc'))
		return $parameter;

	if (is_array($parameter))
	{
		$retParameter = null;
		if (sizeof($parameter))
		{
			foreach($parameter as $key=>$value)
			{
				if (is_array($value))
					$retParameter[$key] = strings_stripSlashes($value,$bGPC);
				else
					$retParameter[$key] = stripslashes($value);		
			}
		}
		return $retParameter;
	}
	else
		return stripslashes($parameter);
}

/** 
 * generalized execution SELECT query
 * @param string SQL request
 * @return associated array  
 */
// MHT 200506 created
function selectData(&$db,$sql)
{
	$output = null;
	$result = $db->exec_query($sql);
	
	if ($result)
	{
		while($row = $db->fetch_array($result))
		{
			$output[] = $row;
		}	
	}
	else
	{
		tLog('FAILED SQL: ' . $sql . "\n" . $db->error_msg(), 'ERROR');
	}
	
	return($output);
}

// --------------------------------------------------------------
// returns an array of messages, one element for every
// key of $a_fields_msg, that has empty value in $a_fields_values.
// The messages is taken from $a_fields_msg
//
// If the key from $a_fields_msg doesn't exists in $a_fields_values
// is considered has existent and empty.
//
//
// 20050417 - fm
// 
function control_empty_fields( $a_fields_values, $a_fields_msg )
{
	$a_msg = array();
	
	foreach ($a_fields_msg as $key_f=>$value_m)
	{
		if (strlen($a_fields_values[$key_f]) == 0)
			$a_msg[] = $value_m ;    
	}
	return $a_msg;
}


// 20050809 - fm - to cope with the active field type change
// 20050816 - scs - simplified
function to_boolean($alt_boolean)
{
	$the_val = 1;
	
	if (is_numeric($alt_boolean) && !intval($alt_boolean))
	{
		$the_val = 0;
	}  	
	else
	{ 
		$a_bool	= array ("on" => 1, "y" => 1, "off" => 0, "n" => 0);
		$alt_boolean = strtolower($alt_boolean);	
		if(isset($a_bool[$alt_boolean]))
		{
			$the_val = $a_bool[$alt_boolean];
		}  
	}
	
	return $the_val;
}


/* 
-------------------------------------------------------------------------------------------
20050708 - fm
Modified to cope with situation where you need to assign a Smarty Template variable instead
of generate output.
Now you can use this function in both situatuons.

if the key 'var' is found in the associative array instead of return a value, 
this value is assigned to $params['var`]

usage: Important: if registered as localize_date()
       {localize_date d='the date to localize'} 
------------------------------------------------------------------------------------------
*/
function localize_date_smarty($params, &$smarty)
{
	return localize_dateOrTimeStamp($params,$smarty,'date_format',$params['d']);
}
function localize_timestamp_smarty($params, &$smarty)
{
	return localize_dateOrTimeStamp($params,$smarty,'timestamp_format',$params['ts']);
}
function localize_dateOrTimeStamp($params,&$smarty,$what,$value)
{
	$format = config_get($what);

	$retVal = strftime($format, strtotime($value));	
	if(isset($params['var']))
		$smarty->assign($params['var'],$retVal);
	return $retVal;
}


/*
check the existence of every element of $akeys2check, in the hash.
For every key not found a call to tlog() is done. 

@param associative array: $hash
@param array: $akeys2check
@param string: [$msg] append to key name to use as tlog message
                      

@returns 1: all keys can be found
         0: at least one key not found  

@author Francisco Mancardi - 20050905 - creation
 20050905 - scs - corrected and refactored
*/
function check_hash_keys($hash, $akeys2check, $msg='')
{
	$status = 1;
	if (sizeof($akeys2check))
	{
		$tlog_msg = $msg . " is not defined";
		foreach($akeys2check as $key)
		{
			if (!isset($hash[$key])) 
			{
				$status = 0;
				tlog( $key . $tlog_msg);
			}
		}
	}
	
	return ($status);
}

/**
 * Turn a hash into a number valued array
 *
 * 
 * @return  array    number valued array of posted input 
 */
function hash2array($hash, $bStripInput = false)
{
	$newArray = null;
	foreach ($hash as $key)
	{
		$newArray[] = $bStripInput ? strings_stripSlashes($key) : $key;
	}
	return $newArray;
}

/**
 * Turn a hash into a number valued array
 *
 * @param string $str2check
 * @param string  $ereg_forbidden_chars: regular expression
 * 
 * @return  1: check ok, 0:check KO
 *
 * @author Francisco Mancardi - 20050907 
 *
 */
function check_string($str2check, $ereg_forbidden_chars)
{
	$status_ok = 1;
	
	if( $ereg_forbidden_chars != '' && !is_null($ereg_forbidden_chars))
	{
		if (eregi($ereg_forbidden_chars, $str2check))
		{
			$status_ok=0;	
		} 	
	}	
	return $status_ok;
}

// If we receive TestPlan ID in the _SESSION
//    then do some checks and if everything OK
//    Update this value at Session Level, to set it available in other
//    pieces of the application
//
//
// Calling getUserProdTestPlans() instead of getUserTestPlans()
//         to add ptoduct filtering of TP
//
function upd_session_tplan_tproject(&$db,$hash_user_sel)
{
	$tproject = new testproject($db);

	// ------------------------------------------------------------------
	$filter_tp_by_product = 1;
	if( isset($hash_user_sel['filter_tp_by_product']) )
	{
	  $filter_tp_by_product = 1;
	}
	else if ( isset($hash_user_sel['filter_tp_by_product_hidden']) )
	{
	  $filter_tp_by_product = 0;
	} 
	// ------------------------------------------------------------------
	$user_sel = array("tplan_id" => 0, "tproject_id" => 0 );
	$user_sel["tproject_id"] = isset($hash_user_sel['testproject']) ? intval($hash_user_sel['testproject']) : 0;
	$user_sel["tplan_id"] = isset($hash_user_sel['testplan']) ? intval($hash_user_sel['testplan']) : 0;

	$tproject_id = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
	
	// Now what to do ???
	// test project is Test Plan container, then we start checking the container
	if( $user_sel["tproject_id"] != 0 )
	{
		$tproject_id = $user_sel["tproject_id"];
	} 
	$tproject_data = $tproject->get_by_id($tproject_id);

	// We need to do checks before updating the SESSION
	if (!$tproject_id || !$tproject_data)
	{
		$all_tprojects = $tproject->get_all();
		if ($all_tprojects)
		{
			$tproject_data = $all_tprojects[0];
		}	
	}
	setSessionTestProject($tproject_data);
	$tproject_id = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;

	$tplan_id    = isset($_SESSION['testPlanId']) ? $_SESSION['testPlanId'] : 0;
	// Now we need to validate the TestPlan
	if($user_sel["tplan_id"] != 0)
		$tplan_id = $user_sel["tplan_id"];

	//check if the specific combination of testprojectid and testplanid is valid
	$tplan_data = getAccessibleTestPlans($db,$tproject_id,$filter_tp_by_product,$tplan_id);
	if(!is_null($tplan_data))
	{ 
		$tplan_data = $tplan_data[0];
		setSessionTestPlan($tplan_data);
		return;
	}
  
	//get the first accessible TestPlan
	$tplan_data = getAccessibleTestPlans($db,$tproject_id,$filter_tp_by_product,null);
	if(!is_null($tplan_data))
		$tplan_data = $tplan_data[0];
		
	setSessionTestPlan($tplan_data);
}

// 20051005 - fm - SET Date and Time FORMATS 
function set_dt_formats()
{
	global $g_date_format;
	global $g_timestamp_format;
	global $g_locales_date_format;
	global $g_locales_timestamp_format;

	if(isset($_SESSION['locale']))
	{
		if($g_locales_date_format[$_SESSION['locale']])
		{
			$g_date_format = $g_locales_date_format[$_SESSION['locale']];
		}
		if($g_locales_timestamp_format[$_SESSION['locale']])
		{
			$g_timestamp_format = $g_locales_timestamp_format[$_SESSION['locale']];
		}
	}
}


// 20051105 - francisco.mancardi@gruppotesi.com
// idea from mantisbt
function config_get($config_id)
{
	$my = "g_" . $config_id;

	return $GLOBALS[$my];
}


# --------------------
# Return true if the parameter is an empty string or a string
#  containing only whitespace, false otherwise
# --------------------------------------------------------
# This piece of sowftare is based on work belonging to:
# --------------------------------------------------------
#
# Mantis - a php based bugtracking system
# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
# Copyright (C) 2002 - 2004  Mantis Team   - mantisbt-dev@lists.sourceforge.net
# This program is distributed under the terms and conditions of the GPL
# See the README and LICENSE files for details

function is_blank( $p_var ) {
	$p_var = trim( $p_var );
	$str_len = strlen( $p_var );
	if ( 0 == $str_len ) {
		return true;
	}
	return false;
}


/**
 * Builds the header needed to make the content available for downloading
 *
 * @param string $content the content which should be downloaded
 * @param string $fileName the filename
 *
 *
**/
function downloadContentsToFile($content,$fileName)
{
	ob_get_clean();
	header('Pragma: public' );
	header('Content-Type: text/plain; charset='.TL_TPL_CHARSET.'; name=' . $fileName );
	header('Content-Transfer-Encoding: BASE64;' );
	header('Content-Disposition: attachment; filename="' . $fileName .'"');
	echo $content;
}



/* 20060401 - franciscom */
function translate_tc_status($status_code)
{
$map_tc_status = array_flip(config_get('tc_status'));

$verbose = lang_get('test_status_not_run');
if( $status_code != '')
{
	$suffix = $map_tc_status[$status_code];
  $verbose=lang_get('test_status_' . $suffix);
}
return ($verbose);
}

function translate_tc_status_smarty($params, &$smarty)
{
	$the_ret = translate_tc_status($params['s']);  
	if(	isset($params['var']) )
	{
		$smarty->assign($params['var'], $the_ret);
	}
	else
	{
		return $the_ret;
	}
}
// -----------------------------------------------

/*

arguments:
          spec_view_type: can get one of the following values:
                          'testproject','testplan'
                          
                          This setting change the processing done 
                          to get the keywords.
                          And indicates the type of id (testproject/testplan) 
                          contained in the argument tobj_id.


returns: array where every element is an associative array with the following
         structure:
        
         [testsuite] => Array( [id] => 28
                          [name] => TS1 )

         [testcases] => Array( [79] => Array( [id] => 79
                                             [name] => TC0
                                             [tcversions] => Array 
                                                             (
                                                              [1093] => 2   // key=tcversion id,value=version
                                                              [6] => 1
                                                             )
                    
                                                             [linked_version_id] => 0
                                             )

                               [81] => Array( [id] => 81
            
                                             [name] => TC88))

       [level] =  
       [write_buttons] => yes or no

       level and write_buttons are used to generate the user interface

rev :
      20060924 - franciscom - added $tobj_name
                                    $map_node_tccount
      20060913 - franciscom - added new member to the tcversions hash
      
*/
function gen_spec_view(&$db,$spec_view_type='testproject',
                            $tobj_id,$id,$name,&$linked_items,
                            $map_node_tccount,
                            $keyword_id = 0,$tcase_id = null,
							              $write_button_only_if_linked=0)
{
  $write_status = 'yes';
  if($write_button_only_if_linked)
  {
	  $write_status = 'no';
  }
	$result = array('spec_view'=>array(), 'num_tc' => 0);
	$out = array(); 
	$a_tcid = array();
    
	$tcase_mgr = new testcase($db); 
	$tree_manager = new tree($db);
  $hash_descr_id = $tree_manager->get_available_node_types();
  $tcase_node_type = $hash_descr_id['testcase'];
  $hash_id_descr = array_flip($hash_descr_id);

  $test_spec = $tree_manager->get_subtree($id,array('testplan'=>'exclude me'),
                                              array('testcase'=>'exclude my_children'));
	     
  // --------------------------------------------------------------------------------------------
  // filters
  if($keyword_id)
  {
      switch ($spec_view_type)
      {
         case 'testproject':
	         $tobj_mgr = new testproject($db); 
           break;  
  
         case 'testplan':
	         $tobj_mgr = new testplan($db); 
	         break;  
      }
      $tck_map = $tobj_mgr->get_keywords_tcases($tobj_id,$keyword_id);
     
      // Get the Test Cases that has the Keyword_id
      // filter the test_spec
      foreach($test_spec as $key => $node)
      {
		    if($node['node_type_id'] == $tcase_node_type && !isset($tck_map[$node['id']]) )
		    {
			   $test_spec[$key]=null;            
		    }      
      }
  }
  
  if(!is_null($tcase_id))
  {
	  // filter the test_spec
	  foreach($test_spec as $key => $node)
	  {
		  if($node['node_type_id'] == $tcase_node_type &&  $node['id'] != $tcase_id )
		  {
			  $test_spec[$key]=null;            
		  }      
	  }
  }
  // --------------------------------------------------------------------------------------------
  
    $idx = 0;
    $a_tcid = array();
    $a_tsuite_idx = array();
  	$hash_id_pos[$id] = $idx;
  	$out[$idx]['testsuite'] = array('id' => $id, 'name' => $name);
  	$out[$idx]['testcases'] = array();
  	$out[$idx]['write_buttons'] = 'no';
  	
  	$out[$idx]['testcase_qty'] = 0;
  	$out[$idx]['level'] = 1;

    $idx++;
                                
  	if(count($test_spec))
  	{
		  $pivot = $test_spec[0];
		  $the_level = 2;
		  $level = array();

		  foreach ($test_spec as $current)
		  {
	        if(is_null($current))
	        {
	           continue;
	        }
          if($hash_id_descr[$current['node_type_id']] == "testcase")
          {
          	$tc_id = $current['id'];
          	$parent_idx = $hash_id_pos[$current['parent_id']];
            $a_tsuite_idx[$tc_id] = $parent_idx;
            
            $out[$parent_idx]['testcases'][$tc_id] = array('id' => $tc_id,
     			                                       'name' => $current['name']);
            $out[$parent_idx]['testcases'][$tc_id]['tcversions'] = array();             
            $out[$parent_idx]['testcases'][$tc_id]['linked_version_id'] = 0;
            $out[$parent_idx]['testcases'][$tc_id]['executed'] = 'no';
            // 20060921 - francisco.mancardi@gruppotesi.com 
            //$out[$parent_idx]['level'] = $the_level;
            $out[$parent_idx]['write_buttons'] = $write_status;
            $out[$parent_idx]['testcase_qty']++;
  	        $out[$parent_idx]['linked_testcase_qty'] = 0;
  	  
  	        // useful for tc_exec_assignment.php          
  	        // 20060913 - franciscom
            $out[$parent_idx]['testcases'][$tc_id]['user_id'] = 0;
            $out[$parent_idx]['testcases'][$tc_id]['feature_id'] = 0;

            $a_tcid[] = $current['id'];
          }
          else
          {
              // -----------------------------------------------
              // 20060921 - franciscom   
    			    if( $pivot['parent_id'] != $current['parent_id'])
    			    {
      			    if ($pivot['id'] == $current['parent_id'])
      			    {
        				  $the_level++;
        				  $level[$current['parent_id']] = $the_level;
      			    }
      			    else 
      			    {
        				  $the_level = $level[$current['parent_id']];
      			    }
              }
              // -----------------------------------------------

            	$out[$idx]['testsuite']=array('id' => $current['id'],
     			                            'name' => $current['name']);
  	          $out[$idx]['testcases'] = array();
  	          $out[$idx]['testcase_qty'] = 0;
  	          $out[$idx]['linked_testcase_qty'] = 0;
              $out[$idx]['level'] = $the_level;
  	          $out[$idx]['write_buttons'] = 'no';
  	          $hash_id_pos[$current['id']] = $idx;
				      $idx++;
			    
    			    // update pivot.
		    	    // 20060921 - franciscom - Only if current is a test suite ??? 
    			    $level[$current['parent_id']] = $the_level;
		    	    $pivot = $current;
			    
			    }
			    
   	  }  //for
		}

    // 20060924 - franciscom
    if(!is_null($map_node_tccount))
    {
      foreach($out as $key => $elem)
      {
        if(isset($map_node_tccount[$elem['testsuite']['id']]) &&
           $map_node_tccount[$elem['testsuite']['id']]['testcount'] == 0)  
        {
             $out[$key]=null;
        }
      }
    }


	  $result['num_tc'] = count($a_tcid);
	  $result['has_linked_items'] = 0;
		
    if($result['num_tc'])
    {
		  $tcase_set = $tcase_mgr->get_by_id($a_tcid);
		
		  foreach($tcase_set as $the_k => $the_tc)
    	{
			  $tc_id = $the_tc['testcase_id'];
			  $parent_idx = $a_tsuite_idx[$tc_id];
			  $out[$parent_idx]['testcases'][$tc_id]['tcversions'][$the_tc['id']] = $the_tc['version'];
            
			  if(!is_null($linked_items))
			  {
				  foreach($linked_items as $the_item)
				  {
					  if(($the_item['tc_id'] == $the_tc['testcase_id']) &&
						  ($the_item['tcversion_id'] == $the_tc['id']) )
					  {
						  $out[$parent_idx]['testcases'][$tc_id]['linked_version_id'] = $the_item['tcversion_id'];
						  $out[$parent_idx]['write_buttons'] = 'yes';
						  $out[$parent_idx]['linked_testcase_qty']++;
						
					  	$result['has_linked_items'] = 1;
						
						  if( intval($the_item['executed']))
						  {
							  $out[$parent_idx]['testcases'][$tc_id]['executed']='yes';
						  } 

              // 20060911 - franciscom
						  if( isset($the_item['user_id']) )
						  {
							  $out[$parent_idx]['testcases'][$tc_id]['user_id']=intval($the_item['user_id']);
						  } 
						  if( isset($the_item['feature_id']) )
						  {
							  $out[$parent_idx]['testcases'][$tc_id]['feature_id']=intval($the_item['feature_id']);
						  }
						  break;
					 }
				 }
			 }    
		}
	}
	$result['spec_view'] = $out;
	
	return $result;
}


function my_array_intersect_keys($array1,$array2)
{
	$aresult = array();
	foreach($array1 as $key => $val)
	{
		if(isset($array2[$key]))
		{
			$aresult[$key] = $array2[$key];
		} 	
	}	
	return($aresult);	
}

?>