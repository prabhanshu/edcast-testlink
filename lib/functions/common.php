<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * @filesource $RCSfile: common.php,v $
 * @version $Revision: 1.34 $ $Author: franciscom $
 * @modified $Date: 2006/03/03 16:21:02 $
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
 * @author: francisco mancardi - 20051005 - set_dt_formats()
 * @author: francisco mancardi - 20051002 - more changes to support filter_tp_by_product
 * 20051002 - am - code reformatted, small corrections
 * @author: francisco mancardi - 20050929 - changes to support filter_tp_by_product
 * @author: francisco mancardi - 20050917 - BUG ID 0000120: Impossible to edit product
 *
 * @author: francisco mancardi - 
 * created updateSessionTp_Prod() and changed doInitSelection() to solve: 
 * BUGID  0000092: Two products each with one active test plan incorrectly prints the wrong plan
 * 
 * @author: francisco mancardi - 20050907 - added hash2array()
 * @author: francisco mancardi - 20050904 - added check_hash_keys()
 *
 * @author: francisco mancardi - 20050904
 * TL 1.5.1 compatibility, get also Test Plans without product id.
 *
 * @author: francisco mancardi - 20050813 - added localize_date_smarty()
 * @author: francisco mancardi - 20050813 - added TP filtered by Product *
 * @author: francisco mancardi - 20050810 - added function to_boolean($alt_boolean)
 * 
 * @author: Asiel Brumfield - 20051012 - optimize sql queries
**/ 

// 20051227 - fm - ADODB
require_once("database.class.php");

require_once("roles.inc.php");
require_once("product.core.inc.php");

// 20060219 - franciscom
require_once("testproject.class.php");

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
		/** @todo check if the session product is updated when its modified per adminproductedit.php  */
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
	// global $g_date_format;
  $date_format = config_get('date_format');

	$the_d = strftime($date_format, strtotime($params['d']));	
	if(	isset($params['var']) )
	{
		$smarty->assign($params['var'], $the_ret);
	}
	else
	{
		return $the_d;
	}
}

/*
20060303 - franciscom
*/
function localize_timestamp_smarty($params, &$smarty)
{
  $timestamp_format = config_get('timestamp_format');

	$the_ts = strftime($timestamp_format, strtotime($params['ts']));	
	if(	isset($params['var']) )
	{
		$smarty->assign($params['var'], $the_ret);
	}
	else
	{
		return $the_ts;
	}
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
?>