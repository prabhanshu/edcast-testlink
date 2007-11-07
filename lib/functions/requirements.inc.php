<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later. 
 *  
 * @filesource $RCSfile: requirements.inc.php,v $
 * @version $Revision: 1.60 $
 * @modified $Date: 2007/11/07 07:33:02 $ by $Author: franciscom $
 *
 * @author Martin Havlat <havlat@users.sourceforge.net>
 * 
 * Functions for support requirement based testing 
 *
 * Revisions:
 *
 * 20070710 - franciscom - BUGID 939
 * 20070705 - franciscom - improved management of arrReqStatus
 * 20070617 - franciscom - removed include of deprecated file
 * 20070310 - franciscom - changed return type createRequirement()
 */
////////////////////////////////////////////////////////////////////////////////


require_once("print.inc.php");

// 20070705 - franciscom
$arrReqStatus = init_labels(config_get('req_status'));


$g_reqImportTypes = array( "csv" => "CSV",
							             "csv_doors" => "CSV (Doors)",
							             "XML" => "XML",
						              );

$g_reqFormatStrings = array (
							"csv" => lang_get('req_import_format_description1'),
							"csv_doors" => lang_get('req_import_format_description2'),
							"XML" => lang_get('the_format_req_xml_import')
							); 		


/** 
 * get list of all SRS for a test project
 * 
 * 
 * @return associated array List of titles according to IDs
 * 
 * @author Martin Havlat 
 *
 * rev :
 *      20070104 - franciscom - added [$get_not_empy]
 **/
function getOptionReqSpec(&$db,$testproject_id,$get_not_empty=0)
{
  $additional_table='';
  $additional_join='';
  if( $get_not_empty )
  {
		$additional_table=", requirements REQ ";
		$additional_join=" AND SRS.id = REQ.srs_id ";
	}
  $sql = " SELECT SRS.id,SRS.title " .
         " FROM req_specs SRS " . $additional_table .
         " WHERE testproject_id={$testproject_id} " .
         $additional_join . 
		     " ORDER BY title";
	return $db->fetchColumnsIntoMap($sql,'id','title');
}


/** 
 * collect information about current list of Requirements in req. Specification
 *  
 * @param string $srs_id ID of req. specification
 * @param string range = ["all" (default), "assigned"] (optional)
 * 			"unassign" is not implemented because requires subquery 
 * 			which is not available in MySQL 4.0.x
 * @param string Test case ID - required if assigned or unassigned scope is used
 * @return assoc_array list of requirements
 * 
 * @author Martin Havlat 
 */
function getRequirements(&$db,$srs_id, $range = 'all', $testcase_id = null)
{
  // 20070327 - franciscom - node_order 
  $order_by=" ORDER BY node_order,req_doc_id,title";
	if ($range == 'all')
	{
		$sql = "SELECT * FROM requirements " .
		       " WHERE srs_id={$srs_id}"; 
	}	       
	else if ($range == 'assigned')
	{
		$sql = "SELECT requirements.* " .
		       " FROM requirements,req_coverage " .
		       " WHERE srs_id={$srs_id} " . 
		       " AND req_coverage.req_id=requirements.id " .
		       " AND req_coverage.testcase_id={$testcase_id}";
	}
	$sql .= $order_by;

	return $db->get_recordset($sql);
}

/** 
 * function allows to obtain unassigned requirements 
 * 
 * @author Martin Havlat 
 **/
// MHT: I'm not able find a simple SQL (subquery is not supported 
// in MySQL 4.0.x); probably temporary table should be used instead of the next
function array_diff_byId ($arrAll, $arrPart)
{
	// solve empty arrays
	if (!count($arrAll) || is_null($arrAll))
	{
		return(null);
	}
	if (!count($arrPart) || is_null($arrPart)) 
	{
		return $arrAll;
	}

	$arrTemp = array();
	$arrTemp2 = array();

	// converts to associated arrays
	foreach ($arrAll as $penny) {
		$arrTemp[$penny['id']] = $penny;
	}
	foreach ($arrPart as $penny) {
		$arrTemp2[$penny['id']] = $penny;
	}
	
	// exec diff
	$arrTemp3 = array_diff_assoc($arrTemp, $arrTemp2);
	
	$arrTemp4 = null;
	// convert to numbered array
	foreach ($arrTemp3 as $penny) {
		$arrTemp4[] = $penny;
	}
	return $arrTemp4;
}

/**
 * get analyse based on requirements and test specification
 * 
 * @param integer $srs_id
 * @return array Coverage in three internal arrays: covered, uncovered, nottestable REQ
 * @author martin havlat
 */
function getReqCoverage_general(&$db,$srs_id)
{
  $order_by=" ORDER BY req_doc_id,title";
	$output = array(
					'covered' => array(), 
					'uncovered' => array(), 
					'nottestable' => array()
					);
	
	// get requirements
	$sql_common = "SELECT id,title,req_doc_id " .
	              " FROM requirements WHERE srs_id={$srs_id}";
	$sql = $sql_common . " AND status='" . VALID_REQ . "' {$order_by}";
	$arrReq = $db->get_recordset($sql);

	// get not-testable requirements
	$sql = $sql_common . " AND status='" . NON_TESTABLE_REQ . "' {$order_by}";
	$output['nottestable'] = $db->get_recordset($sql);
	
	// get coverage
	if (sizeof($arrReq))
	{
		foreach ($arrReq as $req) 
		{
			// collect TC for REQ
			$arrCoverage = getTc4Req($db,$req['id']);
	
			if (count($arrCoverage) > 0) {
				// add information about coverage
				$req['coverage'] = $arrCoverage;
				$output['covered'][] = $req;
			} else {
				$output['uncovered'][] = $req;
			}
		}
	}	
	return $output;
}

/**
 * get requirement coverage metrics
 * 
 * @param integer $srs_id
 * @return array results
 * @author havlatm
 */
function getReqMetrics_general(&$db,$srs_id)
{
	$output = array();
	
	// get nottestable REQs
	$sql = "SELECT count(*) AS cnt " .
	       " FROM requirements WHERE srs_id=" . $srs_id . 
			   " AND status='" . TL_REQ_STATUS_NOT_TESTABLE . "'";
			   
	$output['notTestable'] = $db->fetchFirstRowSingleColumn($sql,'cnt');

	$sql = "SELECT count(*) AS cnt FROM requirements WHERE srs_id=" . $srs_id;
	$output['total'] = $db->fetchFirstRowSingleColumn($sql,'cnt');
	tLog('Count of total REQ in DB for srs_id:'.$srs_id.' = '.$output['total']);

	$sql = "SELECT total_req FROM req_specs WHERE id=" . $srs_id;
	$output['expectedTotal'] = $db->fetchFirstRowSingleColumn($sql,'total_req');
	tLog(' Redefined Count of total REQ in DB for srs_id:'.$srs_id.' = '.$output['total']);
	
	if ($output['expectedTotal'] == 0)
	{
		$output['expectedTotal'] = $output['total'];
	}
	
	$sql = "SELECT DISTINCT requirements.id FROM requirements, req_coverage WHERE" .
				" requirements.srs_id=" . $srs_id .
				" AND requirements.id=req_coverage.req_id";
	$result = $db->exec_query($sql);
	if (!empty($result))
	{
		$output['covered'] = $db->num_rows($result);
	}

	$output['uncovered'] = $output['expectedTotal'] - $output['covered'] - $output['notTestable'];

	return $output;
}

/** 
 * collect information about one Requirement
 *  
 * @param string $req_id ID of req.
 * @return assoc_array list of requirements
 */
function getReqData(&$db,$req_id)
{
	$sql = "SELECT * FROM requirements WHERE id=" . $req_id;

	return $db->fetchFirstRow($sql);
}

/** collect coverage of Requirement 
 * @param string $req_id ID of req.
 * @return assoc_array list of test cases [id, title]
 */
function getTc4Req(&$db,$req_id)
{
	$sql = "SELECT nodes_hierarchy.id,nodes_hierarchy.name 
	        FROM nodes_hierarchy, req_coverage
			    WHERE req_coverage.testcase_id = nodes_hierarchy.id
			    AND  req_coverage.req_id={$req_id}"; 
	return selectData($db,$sql);
}


/** collect coverage of Requirement for Test Suite
 * @param string $req_id ID of req.
 * @param string $idPlan ID of Test Plan
 * @return assoc_array list of test cases [id, title]
 * @author martin havlat
 */
function getSuite4Req(&$db,$req_id, $idPlan)
{
	$sql = "SELECT testcase.id,testcase.title FROM testcase,req_coverage,category," .
				"component WHERE component.projid=" . $idPlan .
				" AND category.compid=component.id AND category.id=testcase.catid" .
				" AND testcase.mgttcid = req_coverage.testcase_id AND req_id=" . 
				$req_id . " ORDER BY title";
	
	return $db->get_recordset($sql);
}

/** 
 * collect coverage of TC
 *  
 * @param string $testcase_id ID of req.
 * @param string SRS ID (optional)
 * @return assoc_array list of test cases [id, title]
 */
function getReq4Tc(&$db,$testcase_id, $srs_id = 'all')
{
	$sql = "SELECT requirements.id,requirements.title FROM requirements, req_coverage " .
			"WHERE req_coverage.testcase_id=" . $testcase_id . 
			" AND req_coverage.req_id=requirements.id";
	// if only for one specification is required
	if ($srs_id != 'all') {
		$sql .= " AND requirements.srs_id=" . $srs_id;
	}

	return $db->get_recordset($sql);
}

/**
 *
 * rev :
 *       20070131 - franciscom - interface changes - added srs_id
 **/
function check_req_basic_data(&$db,$title,$reqdoc_id,$srs_id,$id = null)
{
	$req_cfg = config_get('req_cfg');
	
	$ret['status_ok'] = 1;
	$ret['msg'] = '';
	
	if (!strlen($title))
	{
		$ret['status_ok'] = 0;
		$ret['msg'] = lang_get("warning_empty_req_title");
	}
	
	if (!strlen($reqdoc_id))
	{
		$ret['status_ok'] = 0;
		$ret['msg'] .=  " " . lang_get("warning_empty_reqdoc_id");
	}
	
	if($ret['status_ok'])
	{
		$ret['msg'] = 'ok';
		
		if($req_cfg->reqdoc_id->is_system_wide)
		{
			// req doc id MUST BE unique inside the whole DB
			$rs = getReqByReqdocId($db,$reqdoc_id);
		}
		else
		{   
			// req doc id MUST BE unique inside an SRS
			$rs = getReqByReqdocIdAndSRS($db,$reqdoc_id,$srs_id);
		}
		
		if(!is_null($rs) && (is_null($id) || !isset($rs[$id])))
		{
			$ret['msg'] = lang_get("warning_duplicate_reqdoc_id");
			$ret['status_ok'] = 0;  		  
		}
	} 
	
	return $ret;
}

/** 
 * creates a new Requirement 
 * 
 * @param string $reqdoc_id
 * @param string $title
 * @param string $scope
 * @param integer $srs_id
 * @param integer $user_id
 
 * @param char $status
 * @param char $type
 * 
 * @author Martin Havlat
 *
 * 20061015 - franciscom - interface changes
 * 
 **/
function createRequirement(&$db,$reqdoc_id,$title, $scope, $srs_id, $user_id, 
                           $status = TL_REQ_STATUS_VALID, $type = TL_REQ_STATUS_NOT_TESTABLE)
{
  
	$result['status_ok'] = 1;
	$result['msg'] = 'ok';
	
	$field_size = config_get('field_size');

	$reqdoc_id = trim_and_limit($reqdoc_id,$field_size->req_docid);
	$title = trim_and_limit($title,$field_size->req_title);
		
	$result = check_req_basic_data($db,$title,$reqdoc_id,$srs_id);
	if($result['status_ok'])
	{
		$db_now = $db->db_now();
		$sql = "INSERT INTO requirements (srs_id, req_doc_id, title, scope, status, type, author_id, creation_ts)" .
				   " VALUES (" . $srs_id . ",'" . $db->prepare_string($reqdoc_id) .  
				   "','" . $db->prepare_string($title) . "','" . $db->prepare_string($scope) . 
				   "','" . $db->prepare_string($status) . "','" . $db->prepare_string($type) .
				   "'," . $db->prepare_string($user_id) . ", {$db_now})";

		if (!$db->exec_query($sql))
		{
			$result['status_ok'] = 0;
		 	$result['msg'] = lang_get('error_inserting_req');
		} 	
	}
	
	return $result; 
}


/** 
 * update Requirement 
 * 
 *
 * @param integer $id
 * @param string $reqdoc_id
 * @param string $title
 * @param string $scope
 * @param integer $user_id
 
 * @param string $status
 * @param string $type
 *
 * 
 * 
 * @author Martin Havlat 
 *
 * rev :
 *       20070131 - interface changes
 **/
function updateRequirement(&$db,$id, $reqdoc_id,$title, $scope, $user_id, 
                           $status, $type,$skip_controls=0)
{
	$result = 'ok';
	$db_now = $db->db_now();
	$field_size=config_get('field_size');
	
	// get SRSid, needed to do controls
	$rs=getReqData($db,$id);
  $srs_id=$rs['srs_id'];
	
	$reqdoc_id=trim_and_limit($reqdoc_id,$field_size->req_docid);
	$title=trim_and_limit($title,$field_size->req_title);

  $chk=check_req_basic_data($db,$title,$reqdoc_id,$srs_id,$id);
 
	if($chk['status_ok'] || $skip_controls)
	{
		$sql = "UPDATE requirements SET title='" . $db->prepare_string($title) . 
				"', scope='" . $db->prepare_string($scope) . "', status='" . 
				$db->prepare_string($status) . 
				"', type='" . $db->prepare_string($type) . 
				"', modifier_id='" . $db->prepare_string($user_id) . 
				"', req_doc_id='" . $db->prepare_string($reqdoc_id) .
				"', modification_ts={$db_now} " .
				" WHERE id={$id}";
			
		if (!$db->exec_query($sql))
		 	$result = lang_get('error_updating_req');
	}
	else
	{
	  $result=$chk['msg']; 
	}
	
	return $result; 
}

/** 
 * delete Requirement
 *  
 * @param integer $id
 * 
 * @author Martin Havlat 
 **/
function deleteRequirement(&$db,$id)
{
	// delete dependencies with test specification
	$sql = "DELETE FROM req_coverage WHERE req_id=" . $id;
	$result = $db->exec_query($sql); 
	if ($result)
	{
		$sql = "DELETE FROM requirements WHERE id=" . $id;
		$result = $db->exec_query($sql); 
	}
	if ($result)
		$result = deleteAttachmentsFor($db,$id,"requirements");

	if (!$result)
		$result = lang_get('error_deleting_req');
	else
		$result = 'ok';
		
	return $result; 
}

/** 
 * print Requirement Specification 
 *
 * @param integer $srs_id
 * @param string $prodName
 * @param string $user_id
 * @param string $base_href
 *
 * @author Martin Havlat
 *  
 * @version 1.2 - 20050905
 * @author Francisco Mancardi
 *
 * @version 1.1 - 20050830
 * @author Francisco Mancardi
 *
 **/
function printSRS(&$db,&$tproject,$srs_id, $prodName, $testproject_id, $user_id, $base_href)
{
	$arrSpec = $tproject->getReqSpec($testproject_id,$srs_id);
	
	$title = $arrSpec[0]['title'];
	$output =  printHeader($title,$base_href);
	$output .= printFirstPage($db,$title,$prodName,'',$user_id);
	$output .= "<h2>" . lang_get('scope') . "</h2>\n<div>" . $arrSpec[0]['scope'] . "</div>\n";
	$output .= printRequirements($db,$srs_id);
	$output .= "\n</body>\n</html>";

	return $output;
}

/** 
 * print Requirement for SRS 
 * 
 * @param integer $srs_id
 * 
 * @author Martin Havlat 
 * 20051125 - scs - added escaping of req names
 * 20051202 - scs - fixed 241
 **/
function printRequirements(&$db,$srs_id)
{
	$arrReq = getRequirements($db,$srs_id);
	
	$output = "<h2>" . lang_get('reqs') . "</h2>\n<div>\n";
	if (count($arrReq))
	{
		foreach ($arrReq as $REQ)
		{
			$output .= '<h3>' .htmlspecialchars($REQ["req_doc_id"]). " - " . 
						htmlspecialchars($REQ['title']) . "</h3>\n<div>" . 
						$REQ['scope'] . "</div>\n";
		}
	}
	else
		$output .= '<p>' . lang_get('none') . '</p>';

	$output .= "\n</div>";

	return $output;
}


/** 
 * assign requirement and test case
 * @param integer test case ID
 * @param integer requirement ID
 * @return integer 1 = ok / 0 = problem
 * 
 * @author Martin Havlat 
 */
function assignTc2Req(&$db,$testcase_id, $req_id)
{
	$output = 0;
	tLog("assignTc2Req TC:" . $testcase_id . ' and REQ:' . $req_id);
	
	if ($testcase_id && $req_id)
	{
		$sql = 'SELECT COUNT(*) AS num_cov FROM req_coverage WHERE req_id=' . $req_id . 
				' AND testcase_id=' . $testcase_id;
		$result = $db->exec_query($sql);

    	$row = $db->fetch_array($result);
		if ($row['num_cov'] == 0)
		{
			// create coverage dependency
			$sqlReqCov = 'INSERT INTO req_coverage (req_id,testcase_id) VALUES ' .
					"(" . $req_id . "," . $testcase_id . ")";
			$resultReqCov = $db->exec_query($sqlReqCov);
			// collect results
			if ($db->affected_rows() == 1)
			{
				$output = 1;
				tLog('Dependency was created between TC:' . $testcase_id . ' and REQ:' . $req_id, 'INFO');
			}
			else
			{
				tLog("Dependency wasn't created between TC:" . $testcase_id . ' and REQ:' . $req_id, 'ERROR');
			}
		}
		else
		{
			$output = 1;
			tLog('Dependency already exists between TC:' . $testcase_id . ' and REQ:' . $req_id, 'INFO');
		}
	}
	else
		tLog('Wrong input values', 'ERROR');

	return $output;
}


/** 
 * UNassign requirement and test case
 * @param integer test case ID
 * @param integer requirement ID
 * @return integer 1 = ok / 0 = problem
 * 
 * @author Martin Havlat 
 */
function unassignTc2Req(&$db,$testcase_id, $req_id)
{
	$output = 0;
	tLog("unassignTc2Req TC:" . $testcase_id . ' and REQ:' . $req_id);

	// create coverage dependency
	$sqlReqCov = 'DELETE FROM req_coverage WHERE req_id=' . $req_id . 
			' AND testcase_id=' . $testcase_id;
	$resultReqCov = $db->exec_query($sqlReqCov);

	if ($db->affected_rows() == 1)
	{
		$output = 1;
		tLog('Dependency was deleted between TC:' . $testcase_id . ' and REQ:' . $req_id, 'INFO');
	}
	else
	{
		tLog("Dependency wasn't deleted between TC:" . $testcase_id . ' and REQ:' . $req_id .
				"\n" . $sqlReqCov, 'ERROR');
	}

	return $output;
}



/** 
 * function generate testcases with name and summary for requirements
 * @author Martin Havlat 
 *
 * @param numeric prodID
 * @param array or integer list of REQ id's 
 * @return string Result description
 * 
 *
 * @author Francisco Mancardi - reduce global coupling
 * @author Francisco Mancardi
 * interface changes added $srs_id
 * use new configuration parameter
 * 20051025 - MHT - corrected introduced bug with insert TC
 *
 * 20060110 - fm - user_id
 */
function createTcFromRequirement(&$db,&$tproject,$mixIdReq, $testproject_id, $srs_id, $user_id)
{
 	define('DEFAULT_TC_ORDER',0);
  define('AUTOMATIC_ID',0);
  define('NO_KEYWORDS','');
  

	$g_req_cfg = config_get('req_cfg');
	$g_field_size = config_get('field_size');
	$auto_testsuite_name = $g_req_cfg->default_testsuite_name;
  
  $empty_steps='';
  $empty_results='';
  
  $tree_mgr=New tree($db);
  $tcase_mgr=New testcase($db);
  
  $node_descr_type=$tree_mgr->get_available_node_types();
  
	tLog( __FUNCTION__ . ' started:' . $mixIdReq.','.$testproject_id.','.$srs_id.','.$user_id);
	$output = null;
	if (is_array($mixIdReq)) {
		$arrIdReq = $mixIdReq;
	} else {
		$arrIdReq = array($mixIdReq);
	}
	if ( $g_req_cfg->use_req_spec_as_testsuite_name )
	{
	  // SRS Title
	  $arrSpec = $tproject->getReqSpec($testproject_id,$srs_id);
	  $auto_testsuite_name = substr($arrSpec[0]['title'],0,$g_field_size->testsuite_name);
	}
	
	// find container with the following characteristics:
	// 1. testproject_id is its father
	// 2. has the searched name
	
	// 20070710 - franciscom - BUGID 939          
	$sql="SELECT id FROM nodes_hierarchy NH " .
	     " WHERE name='" . $db->prepare_string($auto_testsuite_name) . "' " .
	     " AND parent_id=" . $testproject_id . " " .
	     " AND node_type_id=" . $node_descr_type['testsuite'];
	             
	          
	$result = $db->exec_query($sql);
  if ($db->num_rows($result) == 1) {
		$row = $db->fetch_array($result);
		$tsuite_id = $row['id'];
	}
	else {
		// not found -> create
		tLog('test suite:' . $auto_testsuite_name . ' was not found.');
    $tsuite_mgr=New testsuite($db);
    $new_tsuite=$tsuite_mgr->create($testproject_id,$auto_testsuite_name,$g_req_cfg->testsuite_details);
    $tsuite_id=$new_tsuite['id'];
	}

	tLog(__FUNCTION__ . ':  test suite id=' . $tsuite_id);


	//create TC
	foreach ($arrIdReq as $execIdReq) 
	{
		//get data
		tLog('proceed: $execIdReq=' . $execIdReq);
		$reqData = getReqData($db,$execIdReq);

		tLog('$reqData:' . implode(',',$reqData));
		
		// create TC
	  $tcase=$tcase_mgr->create($tsuite_id,$reqData['title'],
	                            $g_req_cfg->testcase_summary_prefix . $reqData['scope'] , 
	                            $empty_steps,$empty_results,$user_id,NO_KEYWORDS,
	                            DEFAULT_TC_ORDER,AUTOMATIC_ID,
		                          config_get('check_names_for_duplicates'),
		                          config_get('action_on_duplicate_name'));

		// create coverage dependency
		if (!assignTc2Req($db,$tcase['id'], $reqData['id'])) {
			$output = 'Test case: ' . $reqData['title'] . "was not created </br>";
		}
	}

	return (!$output) ? 'ok' : $output;
}





function exportReqDataToXML($reqData)
{
	$rootElem = "<requirements>{{XMLCODE}}</requirements>";
	$elemTpl = "\t".'<requirement><docid><![CDATA['."\n||DOCID||\n]]>".'</docid><title><![CDATA['."\n||TITLE||\n]]>".'</title>'.
					'<description><![CDATA['."\n||DESCRIPTION||\n]]>".'</description>'.
					'</requirement>'."\n";
	$info = array (
							"||DOCID||" => "req_doc_id",
							"||TITLE||" => "title",
							"||DESCRIPTION||" => "scope",
						);
	return exportDataToXML($reqData,$rootElem,$elemTpl,$info);
}

/** 
 * trim string and limit to N chars
 * @param string
 * @param int [len]: how many chars return
 *
 * @return string trimmed string
 *
 * @author Francisco Mancardi - 20050905 - refactoring
 *
 */
function trim_and_limit($s, $len=100)
{
  $s=trim($s);
	if (strlen($s) > $len ) {
		$s = substr($s, 0, $len);
	}
	return($s);
}

/** collect information about one Requirement from REQ Title
 * @param string $title of req.
 * @param [boolean $ignore_case]
 * @return assoc_array list of requirements
 */
function getReqDataByTitle(&$db,$title,$ignore_case=0)
{
	$output = array();
	
	$sql = "SELECT * FROM requirements ";
	
	if($ignore_case)
	{
	  $sql .= " WHERE UPPER(title)='" . strupper($db->prepare_string($title)) . "'";
	}
	else
	{
	   $sql .= " WHERE title='" . $db->prepare_string($title) . "'";
	}       
	       
	$result = $db->exec_query($sql);
	if (!empty($result)) {
		$output = $db->fetch_array($result);
	}
	
	return $output;
}

/** Process CVS file contents with requirements into TL 
 *  and creates an array with reports 
 *  @return array_of_strings list of particular REQ data with resolution comment
 *
 *
 **/
function executeImportedReqs(&$db,$arrImportSource, $map_cur_reqdoc_id, 
                             $conflictSolution, $emptyScope, $idSRS, $userID)
{
	define('SKIP_CONTROLS',1);
	$field_size = config_get('field_size');

	foreach ($arrImportSource as $data)
	{
		$docID = trim_and_limit($data['req_doc_id'],$field_size->req_docid);
		$title = trim_and_limit($data['title'],$field_size->req_title);
		$scope = $data['description'];
		
		if (($emptyScope == 'on') && empty($scope))
		{
			// skip rows with empty scope
			$status = lang_get('req_import_result_skipped');
		}
		else
		{
			if ($map_cur_reqdoc_id && array_search($docID, $map_cur_reqdoc_id))
			{
				// process conflict according to choosen solution
				tLog('Conflict found. solution: ' . $conflictSolution);
				if ($conflictSolution == 'overwrite')
				{
					$row_curr_data = getReqByReqdocId($db,$docID);
					$req_id = key($row_curr_data);
					$status = updateRequirement($db,$req_id,$docID,$title,$scope,$userID,
							                            $row_curr_data[$req_id]['status'],
							                            $row_curr_data[$req_id]['type'],SKIP_CONTROLS);
							                            
					if ($status == 'ok') {
						$status = lang_get('req_import_result_overwritten');
					}
				} 
				elseif ($conflictSolution == 'skip') {
					// no work
					$status = lang_get('req_import_result_skipped');
				}

				else
				{
					$status = 'Error';
				}

			} else {
				// no conflict - just add requirement
				$status = createRequirement ($db, $docID, $title, $scope, $idSRS, $userID,
				                             TL_REQ_STATUS_VALID, TL_REQ_STATUS_NOT_TESTABLE);
			}
			$arrImport[] = array($docID,$title, $status['msg']);
		}
	}
	
	return $arrImport;
}

/*
20061014 - franciscom -
algorithm changes, now is the docid the attribute that must be unique
*/
function compareImportedReqs($arrImportSource, $map_cur_reqdoc_id)
{
	$arrImport = null;
	if (sizeof($arrImportSource))
	{
		foreach ($arrImportSource as $data)
		{
			$status = lang_get('ok');
			$req_doc_id = $data['req_doc_id'];
			
			if ($map_cur_reqdoc_id &&  in_array($req_doc_id, $map_cur_reqdoc_id,true))
			{
				$status = lang_get('conflict');
				tLog('REQ: '. $data['req_doc_id'] . "\n CONTENT: ".$data['description']);
			}
			$arrImport[] = array($data['req_doc_id'],
								           trim($data['title']), 
								           $data['description'], $status);
		}
	}
	
	return $arrImport;
}

/** get Titles of existing requirements */
function getReqTitles(&$db,$idSRS)
{
	// collect existing req titles in the SRS
	$arrCurrentReq = getRequirements($db,$idSRS);
	$arrReqTitles = null;
	if (count($arrCurrentReq))
	{ 
		// only if some reqs exist
		foreach ($arrCurrentReq as $data)
		{
			$arrReqTitles[$data['id']] = $data['title'];
		}
	}
	
	return $arrReqTitles;
}


// 20061014 - franciscom
function getReqDocIDs(&$db,$srs_id)
{
	$arrCurrentReq = getRequirements($db,$srs_id);
	$result = null;
	if (count($arrCurrentReq))
	{ 
		// only if some reqs exist
		foreach ($arrCurrentReq as $data)
		{
			$result[$data['id']] = $data['req_doc_id'];
		}
	}
	
	return($result);
}



/**
 * load imported data from file and parse it to array
 * @return array_of_array each inner array include fields title and scope (and more)
 */
function loadImportedReq($CSVfile, $importType)
{
	$fileName = $CSVfile;
	switch($importType)
	{
		case 'csv':
			$pfn = "importReqDataFromCSV";
			break;
		case 'csv_doors':
			$pfn = "importReqDataFromCSVDoors";
			break;
		case 'XML':
			$pfn = "importReqDataFromXML";
			break;
	}
	if ($pfn)
	{
		$data = $pfn($fileName);
		return $data;
	}
	return;
	
}


function importReqDataFromCSV($fileName)
{
  $field_size=config_get('field_size');  
  $delimiter=',';
  
  // CSV line format
	$destKeys = array("req_doc_id",
					          "title",       
					          "description");

  // lenght will be adjusted to these values
  $field_length = array("req_doc_id" => $field_size->req_docid,
					              "title" => $field_size->req_title);
					          
	$reqData = importCSVData($fileName,$destKeys,$delimiter,count($destKeys));
	
	// 20061015 - franciscom
	// adjust value length to field length to avoid problems during inset
	if ($reqData)
	{
		foreach($reqData as $key => $value)
		{
	     foreach($field_length as $fkey => $len)
		   {
	       $reqData[$key][$fkey]=trim_and_limit($reqData[$key][$fkey],$len); 	      
		   }
		}
	}
	return $reqData;
}


function importReqDataFromCSVDoors($fileName)
{
  $delimiter=',';
  $bWithHeader = true;
  $bDontSkipHeader = false;
  
	$destKeys = array("Object Identifier" => "title",
					          "Object Text" => "description",
					          "Created By",
					          "Created On",
					          "Last Modified By",
					          "Last Modified On");
				
	$reqData = importCSVData($fileName,$destKeys,$delimiter,0,$bWithHeader,$bDontSkipHeader);
	
	return $reqData;
}

/*
20061015 - franciscom - added trim_and_limit

*/
function importReqDataFromXML($fileName)
{
	$dom = domxml_open_file($fileName);
	$xmlReqs = null;
  $field_size=config_get('field_size');  


	if ($dom)
		$xmlReqs = $dom->get_elements_by_tagname("requirement");
	
	$xmlData = null;
	$num_elem=sizeof($xmlReqs);
	
	for($i = 0;$i < $num_elem ;$i++)
	{
		$xmlReq = $xmlReqs[$i];
		if ($xmlReq->node_type() != XML_ELEMENT_NODE)
			continue;
		$xmlData[$i]['req_doc_id'] = trim_and_limit(getNodeContent($xmlReq,"docid"),$field_size->req_docid);
		$xmlData[$i]['title'] = trim_and_limit(getNodeContent($xmlReq,"title"),$field_size->req_title);
		$xmlData[$i]['description'] = getNodeContent($xmlReq,"description");
	}
	
	return $xmlData;
}


function doImport(&$db,$userID,$idSRS,$fileName,$importType,$emptyScope,$conflictSolution,$bImport)
{
	$arrImportSource = loadImportedReq($fileName, $importType);
	
	$arrImport = null;
	if (count($arrImportSource))
	{
		// $arrReqTitles = getReqTitles($db,$idSRS);
		$map_cur_reqdoc_id = getReqDocIDs($db,$idSRS);
		
		if ($bImport)
		{
			$arrImport = executeImportedReqs($db,$arrImportSource, $map_cur_reqdoc_id, 
		                                   $conflictSolution, $emptyScope, $idSRS, $userID);
		}
		else
		{
			$arrImport = compareImportedReqs($arrImportSource, $map_cur_reqdoc_id);
		}	
	}
	return $arrImport;
}

function exportReqDataToCSV($reqData)
{
	$sKeys = array(
					"req_doc_id",
					"title",
					"scope",
				   );
	return exportDataToCSV($reqData,$sKeys,$sKeys,0,',');
}


function getReqCoverage($reqs,$execMap,&$coveredReqs)
{
	$arrCoverage = array(
						"passed" => array(),
						"failed" => array(),
						"blocked" => array(),
						"not_run" => array(),
					);
	$coveredReqs = null;
	if (sizeof($reqs))
	{
		foreach($reqs as $id => $tc)
		{
			$n = sizeof($tc);
			$nPassed = 0;
			$nBlocked = 0;
			$nFailed = 0;
			$req = array("id" => $id,
						 "title" => "",
						 );
			if (sizeof($tc))
				$coveredReqs[$id] = 1;
			for($i = 0;$i < sizeof($tc);$i++)
			{
				$tcInfo = $tc[$i];	
				if (!$i)
					$req['title'] = $tcInfo['title'];
				$execTc = $tcInfo['testcase_id'];
				if ($execTc)
					$req['tcList'][] = array(
												"tcID" => $execTc,
												"title" => $tcInfo['title']
											); 
				
				
				$exec = 'n';
				if (isset($execMap[$execTc]) && sizeof($execMap[$execTc]))
				{
					$execInfo = end($execMap[$execTc]);
					$exec = isset($execInfo['status']) ? $execInfo['status'] : 'n';
				}
				if ($exec == 'p')
					$nPassed++;		
				else if ($exec == 'b')
					$nBlocked++;		
				else if ($exec == 'f')
					$nFailed++;					
			}
			if ($nFailed)
				$arrCoverage['failed'][] = $req;			
			else if ($nBlocked)
				$arrCoverage['blocked'][] = $req;			
			else if (!$nPassed)
				$arrCoverage['not_run'][] = $req;
			else if ($nPassed == $n)
				$arrCoverage['passed'][] = $req;
			else 
				$arrCoverage['failed'][] = $req;
		}
	}
	return $arrCoverage;
}

function getLastExecutions(&$db,$tcs,$tpID)
{	
	$execMap = array();
	if (sizeof($tcs))
	{
		$tcase_mgr = new testcase($db);
		foreach($tcs as $tcID => $tcInfo)
		{
			$tcversion_id = $tcInfo['tcversion_id'];
		    $execMap[$tcID] = $tcase_mgr->get_last_execution($tcID,$tcversion_id,$tpID,ANY_BUILD,GET_NO_EXEC);
		}
	}
	return $execMap;
}

// 20061009 - franciscom
function getReqByReqdocId(&$db,$reqdoc_id)
{
	$sql = "SELECT * FROM requirements " .
	       " WHERE req_doc_id='" . $db->prepare_string($reqdoc_id) . "'";

	return($db->fetchRowsIntoMap($sql,'id'));
}

// 20061223 - franciscom
function getReqByReqdocIdAndSRS(&$db,$reqdoc_id,$srs_id)
{
	$sql = "SELECT * FROM requirements " .
	       " WHERE req_doc_id='" . $db->prepare_string($reqdoc_id) . "'" .
	       " AND srs_id={$srs_id}";

	return($db->fetchRowsIntoMap($sql,'id'));
}


/**
 * Function-Documentation
 *
 * @param type $title documentation
 * @param type $result [ref] documentation
 * @return type documentation
 *
 * @author Andreas Morsing <schlundus@web.de>
 * @since 12.03.2006, 22:04:20
 *
 **/
function checkRequirementTitle($title,&$result)
{
	$bSuccess = 0;
	if (!strlen($title))
		$result = lang_get("warning_empty_req_title");
	else
		$bSuccess = 1;
		
	return $bSuccess;
}


// 20061014 - franciscom
function check_syntax($fileName,$importType)
{
	switch($importType)
	{
		case 'csv':
			$pfn = "check_syntax_csv";
			break;

		case 'csv_doors':
			$pfn = "check_syntax_csv_doors";
			break;

		case 'XML':
			$pfn = "check_syntax_xml";
			break;
	}
	if ($pfn)
	{
		$data = $pfn($fileName);
		return $data;
	}
	return;
}

function check_syntax_xml($fileName)
{
  $ret=array();
  $ret['status_ok']=1;
  $ret['msg']='ok';
  
  //@ -> shhhh!!!! silence please
  if (!$dom = @domxml_open_file($fileName)) 
  {
    $ret['status_ok']=0;
    $ret['msg']=lang_get('file_is_not_xml');
  }  
  return($ret);
}


function check_syntax_csv($fileName)
{
  $ret=array();
  $ret['status_ok']=1;
  $ret['msg']='ok';
  return($ret);
}



// Must be implemented !!!
function check_syntax_csv_doors($fileName)
{
  $ret=array();
  $ret['status_ok']=1;
  $ret['msg']='ok';
  
  return($ret);
}


// 20061224 - francisco.mancardi@gruppotesi.com
function get_srs_by_id(&$db,$srs_id)
{
	$output=null;
	
	$sql = "SELECT * FROM req_specs WHERE id={$srs_id}";
	$output = $db->fetchRowsIntoMap($sql,'id');
	return($output);
}

/*
  function: 

  args :
          $nodes: array with req_id in order
  returns: 

*/
function set_req_order(&$db,$srs_id,$nodes)
{

	foreach($nodes as $order => $node_id)
	{
		$order = abs(intval($order));
		$node_id = intval($node_id);
	  $sql = "UPDATE requirements SET node_order = {$order}
	      	    WHERE id = {$node_id}";
	  $result = $db->exec_query($sql);
	}

}

?>
