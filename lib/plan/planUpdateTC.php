<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * @version $Id: planUpdateTC.php,v 1.39 2010/01/23 09:48:28 franciscom Exp $
 *
 * Author: franciscom
 *
 * Allows for NON executed test cases linked to a test plan, update of Test Case versions
 * following user choices.
 * Test Case Execution assignments will be auto(magically) updated.
 *
 * 	@internal revisions:
 *	
 *	20100123 - franciscom - BUGID 2652 + missing refactoring for table prefix doUpdate()
 *	20091212 - franciscom - added contribution by asimon83 (refactored) - BUGID 2652
 *                          show newest testcase versions when updating all linked testcase versions
 *	
 */
require_once("../../config.inc.php");
require_once("common.php");
require_once("specview.php");
testlinkInitPage($db,false,false,"checkRights");

$tree_mgr = new tree($db);
$tsuite_mgr = new testsuite($db);
$tplan_mgr = new testplan($db);
$tcase_mgr = new testcase($db);

$templateCfg = templateConfiguration();

$args = init_args($tplan_mgr);
$gui = initializeGui($db,$args,$tplan_mgr,$tcase_mgr);
$keywordsFilter = null;
if(is_array($args->keyword_id))
{
    $keywordsFilter = new stdClass();
    $keywordsFilter->items = $args->keyword_id;
    $keywordsFilter->type = $gui->keywordsFilterType->selected;
}

new dBug($args);

switch ($args->doAction)
{
    case "doUpdate":
    case "doBulkUpdateToLatest":
	    $gui->user_feedback = doUpdate($db,$args);
	    break;

    default:
    	break;
}

$out = null;
$gui->show_details = 0;
$gui->operationType = 'standard';
$gui->hasItems = 0;        	
        	
switch($args->level)
{
	case 'testcase':
	    $out = processTestCase($db,$args,$keywordsFilter,$tplan_mgr,$tree_mgr);
		break;

	case 'testsuite':
	    $out = processTestSuite($db,$args,$keywordsFilter,$tplan_mgr,$tcase_mgr);
		break;

	case 'testplan':
        $itemSet = processTestPlan($db,$args,$keywordsFilter,$tplan_mgr);
        $gui->testcases = $itemSet['items'];
        $gui->user_feedback = $itemSet['msg'];
		$gui->instructions = lang_get('update2latest');
		$gui->buttonAction = "doBulkUpdateToLatest";
        $gui->operationType = 'bulk';
        if( !is_null($gui->testcases) )
        {
	        $gui->hasItems = 1;
			$gui->show_details = 1;
        }
  		break;
  
	default:
		// show instructions
  		redirect($_SESSION['basehref'] . "/lib/general/staticPage.php?key=planUpdateTC");
		break;
}

if(!is_null($out))
{
	$gui->hasItems = $out['num_tc'] > 0 ? 1 : 0;
	$gui->items = $out['spec_view'];
}

$smarty = new TLSmarty();
$smarty->assign('gui', $gui);
$smarty->display($templateCfg->template_dir . $templateCfg->default_template);

/*
  function: init_args

  args :
  
  returns: 

*/
function init_args(&$tplanMgr)
{
    $_REQUEST = strings_stripSlashes($_REQUEST);
    $args = new stdClass();
    $args->id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
    $args->level = isset($_REQUEST['level']) ? $_REQUEST['level'] : null;
    $args->doAction = isset($_REQUEST['doAction']) ? $_REQUEST['doAction'] : null;

    // Maps with key: test case ID value: tcversion_id
    $args->fullTestCaseSet = isset($_REQUEST['a_tcid']) ? $_REQUEST['a_tcid'] : null;
    $args->checkedTestCaseSet = isset($_REQUEST['achecked_tc']) ? $_REQUEST['achecked_tc'] : null;
    $args->newVersionSet = isset($_REQUEST['new_tcversion_for_tcid']) ? $_REQUEST['new_tcversion_for_tcid'] : null;
    $args->version_id = isset($_REQUEST['version_id']) ? $_REQUEST['version_id'] : 0;

    // Can be a list (string with , (comma) has item separator), that will be trasformed in an array.
    $keywordSet = isset($_REQUEST['keyword_id']) ? $_REQUEST['keyword_id'] : null;
    $args->keyword_id = is_null($keywordSet) ? 0 : explode(',',$keywordSet); 
    $args->keywordsFilterType = isset($_REQUEST['keywordsFilterType']) ? $_REQUEST['keywordsFilterType'] : 'OR';

    
    $args->tplan_id = isset($_REQUEST['tplan_id']) ? intval($_REQUEST['tplan_id']) : 0;
    if($args->tplan_id == 0)
    {
        $args->tplan_id = isset($_SESSION['testplanID']) ? intval($_SESSION['testplanID']) : 0;
        $args->tplan_name = $_SESSION['testplanName'];
    }
    else
    {
        $tpi = $tplanMgr->get_by_id($args->tplan_id);  
        $args->tplan_name = $tpi['name'];
    }
    $args->tproject_id = $_SESSION['testprojectID'];
    $args->tproject_name = $_SESSION['testprojectName'];

    return $args;
}

/*
  function: doUpdate

  args:

  returns: message

*/
function doUpdate(&$dbObj,&$argsObj)
{
	$debugMsg = 'File:' . __FILE__ . ' - Function: ' . __FUNCTION__;
	$tables = tlObject::getDBTables(array('testplan_tcversions','executions',
	                                      'cfield_execution_values'));
	$msg = "";
	if(!is_null($argsObj->checkedTestCaseSet))
	{
		foreach($argsObj->checkedTestCaseSet as $tcaseID => $tcversionID)
		{
			$newtcversion=$argsObj->newVersionSet[$tcaseID];
			foreach($tables as $table2update)
			{
				$sql = "/* $debugMsg */ UPDATE $table2update " .
				       " SET tcversion_id={$newtcversion} " . 
				       " WHERE tcversion_id={$tcversionID} " .
				       " AND testplan_id={$argsObj->tplan_id}";
				$dbObj->exec_query($sql);
			}
		}
		$msg = lang_get("tplan_updated");
	}
	return $msg;
}


/*
  function: initializeGui

  args :
  
  returns: 

*/
function initializeGui(&$dbHandler,$argsObj,&$tplanMgr,&$tcaseMgr)
{
    $tcase_cfg = config_get('testcase_cfg');
    $gui = new stdClass();
    $gui->refreshTree=false;
    $gui->instructions='';
    $gui->buttonAction="doUpdate";
    $gui->testCasePrefix = $tcaseMgr->tproject_mgr->getTestCasePrefix($argsObj->tproject_id);
    $gui->testCasePrefix .= $tcase_cfg->glue_character;
    $gui->user_feedback = '';
    $gui->testPlanName = $argsObj->tplan_name;
    $gui->items = null;
    $gui->has_tc = 1;  
    
    return $gui;
}


/*
  function: processTestSuite 

  args :
  
  returns: 

*/
function processTestSuite(&$dbHandler,&$argsObj,$keywordsFilter,&$tplanMgr,&$tcaseMgr)
{
    $out=keywordFilteredSpecView($dbHandler,$argsObj,$keywordsFilter,$tplanMgr,$tcaseMgr);
    return $out;
}


/*
  function: doUpdateAllToLatest

  args:

  returns: message

*/
function doUpdateAllToLatest(&$dbObj,$argsObj,&$tplanMgr)
{
  $qty=0;
  $linkedItems=$tplanMgr->get_linked_tcversions($argsObj->tplan_id);
  
  if( is_null($linkedItems) )
  {
     return lang_get('no_testcase_available');  
  }
  
  $items=$tplanMgr->get_linked_and_newest_tcversions($argsObj->tplan_id);
  if( !is_null($items) )
  {
      foreach($items as $key => $value)
      {
         if( $value['newest_tcversion_id'] != $value['tcversion_id'] )
         {
            $newtcversion=$value['newest_tcversion_id'];
            $tcversionID=$value['tcversion_id'];
            $qty++;
            
            // Update link to testplan
            $sql = "UPDATE testplan_tcversions " .
                   " SET tcversion_id={$newtcversion} " .
                   " WHERE tcversion_id={$tcversionID} " .
                   " AND testplan_id={$argsObj->tplan_id}";
            $dbObj->exec_query($sql);
      
            // Update link in executions
            $sql = "UPDATE executions " .
                  " SET tcversion_id={$newtcversion} " .
                  " WHERE tcversion_id={$tcversionID}" .
                  " AND testplan_id={$argsObj->tplan_id}";
            $dbObj->exec_query($sql);
      
            // Update link in cfields values
            $sql = "UPDATE cfield_execution_values " .
                  " SET tcversion_id={$newtcversion} " .
                  " WHERE tcversion_id={$tcversionID}" .
                  " AND testplan_id={$argsObj->tplan_id}";
            $dbObj->exec_query($sql);
         }
      }
  } 
  if( $qty == 0 )
  {
      $msg=lang_get('all_versions_where_latest');  
  }  
  else
  {
      $msg=sprintf(lang_get('num_of_updated'),$qty);
  }

  return $msg;
}


/**
 * 
 *
 */
function processTestCase(&$dbHandler,&$argsObj,$keywordsFilter,&$tplanMgr,&$treeMgr)
{
	$my_path = $treeMgr->get_path($args->id);
	$idx_ts = count($my_path)-1;
	$tsuite_data = $my_path[$idx_ts-1];
	$filters = array('tcase_id' => $args->id);
	$linked_items = $tplanMgr->get_linked_tcversions($argsObj->tplan_id,$filters);		
	$opt = array('write_button_only_if_linked' => 1, 'prune_unlinked_tcversions' => 1);
	$filters = array('keywords' => $argsObj->keyword_id);
	$out = gen_spec_view($dbHandler,'testplan',$argsObj->tplan_id,$tsuite_data['id'],$tsuite_data['name'],
	                     $linked_items,null,$filters,$opt);
	return $out;
}

/**
 * 
 *
 */
function processTestPlan(&$dbHandler,&$argsObj,$keywordsFilter,&$tplanMgr)
{
	$set2update = array('items' => null, 'msg' => '');
    $filters = array('keywords' => $argsObj->keyword_id);
	$linked_tcases = $tplanMgr->get_linked_tcversions($argsObj->tplan_id,$filters);
	$set2update['msg'] = lang_get('testplan_seems_empty');
    if( count($linked_tcases) > 0 )
    {
        $testCaseSet = array_keys($linked_tcases);
    	$set2update['items'] = $tplanMgr->get_linked_and_newest_tcversions($argsObj->tplan_id,$testCaseSet);
		
		$set2update['msg'] = lang_get('no_newest_version_of_linked_tcversions');
		if( !is_null($set2update['items']) && count($set2update['items']) > 0 )
		{
			$itemSet=array_keys($set2update['items']);
			$path_info=$tplanMgr->tree_manager->get_full_path_verbose($itemSet);
			foreach($set2update['items'] as $tcase_id => $value)
			{
				$path=$path_info[$tcase_id];
				unset($path[0]);
				$path[]='';
				$set2update['items'][$tcase_id]['path']=implode(' / ',$path);
			}
		}
    }
    return $set2update;
}


function checkRights(&$db,&$user)
{
	return $user->hasRight($db,'testplan_planning');
}
?>