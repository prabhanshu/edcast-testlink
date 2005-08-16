<?
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * @filesource $RCSfile: results.inc.php,v $
 * @version $Revision: 1.2 $
 * @modified $Date: 2005/08/16 18:00:55 $
 * 
 * @author 	Martin Havlat 
 * @author 	Chad Rosen (original report definition)
 *
 * Functions for Test Reporting and Metrics
 *
 * @author 20050807 - fm
 * refactoring:  
 * getPlanTCNumber($idPlan); removed deprecated: $_SESSION['project']
 * getTestSuiteReport(); added new parameter
 *
 * @author 20050428 - fm
 * use g_tc_status instead of MAGIC CONSTANTS 'f','b', ecc
 * refactoring of sql (using base_sql)
 *   
 */
require_once('../../config.inc.php');
require_once("common.php");

/**
* Function send header which initiate MS excel
*/
function sendXlsHeader()
{
	header("Content-Disposition: inline; filename=testReport.xls");
	header("Content-Description: PHP Generated Data");
	header("Content-type: application/vnd.ms-excel; name='My_Excel'");
	flush();
}

/**
* Function get Test results Status from character
*
* @param string Test Id
* @param string Build Number
* @return string Status  
*/
function getStatus($tcId, $build)
{
	$sqlStatus = "select status from results where results.tcid='" . 
			$tcId . "' and results.build='" . $build  . "'";
	$resultStatus = do_mysql_query($sqlStatus);
	$myrowStatus = mysql_fetch_row($resultStatus);

	return $myrowStatus[0];
}


/**
* Function get Test results Status from character
* @param string $status Status character; e.g. p -> Passed
* @return string Status  
*
* 20050425 - fm
*/
function getStatusName($status)
{
	global $g_tc_status;
	
	$desc = '???';
	if (in_array($status,$g_tc_status))
		$desc = array_search($status,$g_tc_status);
	
	return $desc;
}


/**
* Function returns number of Test Cases in the Test Plan
* @param string $idPlan Test Plan ID; e.g. $_SESSION['testPlanId']
* @return string Count of test set 
*
* Rev :
*      20050807 - fm 
*      Refactoring: 
*      sql modified to use $idPlan instead of deprecated $_SESSION['project']
*            
*/
function getPlanTCNumber($idPlan)
{
	$sql = "select count(testcase.id) from project,component,category,testcase where " .
			"project.id =" . $idPlan . " and project.id = component.projid " .
			"and component.id = category.compid and category.id = testcase.catid";
	$sumResult = do_mysql_query($sql);
	$sumTCs = mysql_fetch_row($sumResult); 

	return $sumTCs[0];
}

/**
* Function returns number of Test Cases in the Test Plan
* @return string Link of Test ID + Title 
*/
function getTCLink($rights, $result, $id, $title, $build)
{
	$title = htmlspecialchars($title);
	$suffix = $result . '">' . $id . ": <b>" . $title. "</b></a>";

	// a hyper link to the execution pages
	if ($rights)
		$testTitle = '<a href="lib/execute/execSetResults.php?keyword=All&level=testcase&owner=All&build='. $build . '&id=' . $suffix;
	else // link to test specification
		$testTitle = '<a href="lib/testcases/archiveData.php?edit=testcase&data=' . $suffix;
		
	return $testTitle;
}


/**
* Function collect build results 
*
* @param string $idPlan Test Plan ID; e.g. $_SESSION['project'] 
* @param string $build Build number
* @return array $totalPassed, $totalFailed, $totalBlocked
*/
function getPlanStatus($idPlan, $build)
{
	global $g_tc_status;

	// MHT 200507 improved SQL
	$base_sql = " SELECT count(results.tcid) FROM component,category,testcase,results " .
			" WHERE component.projid =" . $idPlan . " AND component.id = category.compid " .
			" AND category.id = testcase.catid " . " AND testcase.id = results.tcid " .
			" AND build = '" . $build . "' ";

	//Get the total # of passed testcases for the project and build
	$sql = $base_sql . " AND status = '" . $g_tc_status['passed'] . "'";
	$passedResult = do_mysql_query($sql);
	$passedTCs = mysql_fetch_row($passedResult);
	$totalPassed = $passedTCs[0];

	//Get the total # of failed testcases for the project
	$sql = $base_sql . " AND status = '" . $g_tc_status['failed'] . "'";
	$failedResult = do_mysql_query($sql);
	$failedTCs = mysql_fetch_row($failedResult);
	$totalFailed = $failedTCs[0];

	//Get the total # of blocked testcases for the project
	$sql = $base_sql . " AND status = '" . $g_tc_status['blocked'] . "'";
	$blockedResult = do_mysql_query($sql);
	$blockedTCs = mysql_fetch_row($blockedResult);
	$totalBlocked = $blockedTCs[0];

	return array($totalPassed, $totalFailed, $totalBlocked);
}

/**
* Function generates stats based on Test Suites
*
* @param $idPlan Test Plan ID
* @param string build ID (optional)
* @return array (component.name, $totalTCs, $pass, $fail, $blocked,
*				$notRunTCs, $percentComplete)
* @todo calculate results in db via select count; optimalize SQL requests
*
* Rev :
*       20050807 - fm
*       Added $idPlan to remove Global Coupling via $_SESSION
* 
*/
function getTestSuiteReport($idPlan, $build = 'all')
{
	global $g_tc_status;
  
	$arrOutput = array();
	
	// get particular test suites (components)
	// MHT 200507 improved SQL
	//
	// 20050807 - fm 
	// $idPlan
	$sql = "SELECT name, id FROM component WHERE component.projid = " . $idPlan;
	$result = do_mysql_query($sql);

	while ($myrow = mysql_fetch_row($result)) {

		$testCaseArray = null;
		//Code to grab the entire amount of test cases per project
		// MHT 200507 improved SQL
		//
		// 20050807 - fm
		// $idPlan
		//
		$sql = "SELECT COUNT(testcase.id) FROM component,category,testcase " .
				"WHERE component.projid = " . $idPlan . 
				" AND component.id=" . $myrow[1] . 
				" AND component.id = category.compid AND category.id = testcase.catid";
		$totalTCResult = do_mysql_query($sql);
		$totalTCs = mysql_fetch_row($totalTCResult);

		//Code to grab the results of the test case execution
		if ($build == 'all') {
			
			// 20050807 - fm
			// $idPlan
			$sql = "select tcid,status from results,project,component,category,testcase " .
				"where project.id = '" . $idPlan . 
				"' and component.id='" . $myrow[1] . 
				"' and project.id = component.projid and " .
				"component.id = category.compid and category.id = testcase.catid and " .
				"testcase.id = results.tcid order by build";
		} else {
			// 20050807 - fm
			// $idPlan
			$sql = "select tcid,status from results,project,component,category,testcase " .
				"where project.id = '" . $idPlan . 
				"' and results.build='" . $build . 
				"' and component.id='" . $myrow[1] . 
				"' and project.id = component.projid " .
				" and component.id = category.compid and category.id = testcase.catid" .
				" and testcase.id = results.tcid";
		}
		$totalResult = do_mysql_query($sql);

		//Setting the results to an array.. Only taking the most recent results and displaying them
		while($totalRow = mysql_fetch_row($totalResult)){
			// This is a test.. I've got a problem if the user goes and sets a previous p,f,b 
			// value to a 'n' value. 
			// The program then sees the most recent value as an not run. 
			// I think we want the user to then see the most recent p,f,b value
			if($totalRow[1] != $g_tc_status['not_run']){
				$testCaseArray[$totalRow[0]] = $totalRow[1];
			}
		}

		//This is the code that determines the pass,fail,blocked amounts
		$arrayCounter = 0; //Counter

		//Initializing variables
		$pass = 0;
		$fail = 0;
		$blocked = 0;
		$notRun = 0;

		//I had to write this code so that the loop before would work.. 
		//I'm sure there is a better way to do it but hell if I know how to figure it out..
		if(count($testCaseArray) > 0){
			foreach($testCaseArray as $tc){

				if($tc == $g_tc_status['passed']){
					$pass++;
				}elseif($tc == $g_tc_status['failed']){
					$fail++;
				}elseif($tc == $g_tc_status['blocked']){
					$blocked++;
				}
				unset($testCaseArray);
			}//end foreach
		}//end if

		//This loop will cycle through the arrays and count the amount of p,f,b,n
		if($totalTCs[0] == 0){
			$percentComplete= 0;
		}else{
			$percentComplete = ($pass + $fail + $blocked) / $totalTCs[0]; //Getting total percent complete
			$percentComplete = round((100 * ($percentComplete)),2); //Rounding the number so it looks pretty
		}
		
		$notRunTCs = $totalTCs[0] - ($pass + $fail + $blocked); //Getting the not run TCs

		array_push($arrOutput, array($myrow[0], $totalTCs[0], $pass, $fail, $blocked,
				$notRunTCs, $percentComplete));
	}
	return $arrOutput;
}

/**
* Function generates stats based on Keywords
*
* @param string build ID (optional)
* @return array $keyword, $totalTCs, $pass, $fail, $blocked,
*				$notRunTCs, $percentComplete
*/

function getKeywordsReport($build = 'all')
{
	global $g_tc_status;
  
	$arrOutput = array();
	// MHT 200507 improved SQL
	$sqlKeyword = "SELECT DISTINCT(keywords) FROM component, category, testcase WHERE" .
			" component.projid = " .  $_SESSION['testPlanId'] . " AND component.id = category.compid" .
			" AND category.id = testcase.catid ORDER BY keywords";
	$resultKeyword = do_mysql_query($sqlKeyword);

	//Loop through each of the testcases
	$keyArray = null;
	while ($myrowKeyword = mysql_fetch_row($resultKeyword))
	{
		$keyArray .= $myrowKeyword[0].",";
	}
	//removed quotes and separate the list
	$keyArray = explode(",",$keyArray);

	//I need to make sure there are elements in the result 2 array. I was getting an error when I didn't check
	if(count($keyArray))
		$keyArray = array_unique ($keyArray);

	
	foreach($keyArray as $key=>$word)
	{
		$testCaseArray = null;
		//For some reason I'm getting a space.. Now I'll ignore any spaces
		if($word != ""){
				
			//Code to grab the entire amount of test cases per project
			$keyWord = $word;
			$word = mysql_escape_string($word);
			$sql = "select count(testcase.id) from project,component,category,testcase where project.id = '" . $_SESSION['testPlanId'] . "' and project.id = component.projid and component.id = category.compid and category.id = testcase.catid AND (testcase.keywords LIKE '%,{$word},%' OR testcase.keywords LIKE '{$word},%')";
			$totalTCResult = do_mysql_query($sql);
			$totalTCs = mysql_fetch_row($totalTCResult);

			//Code to grab the results of the test case execution
			if ($build == 'all') {
				$sql = "select tcid,status from results,project,component,category,testcase" .
					" where project.id = '" . $_SESSION['testPlanId'] . 
					"' and project.id = component.projid" .
					" and component.id = category.compid" .
					" and category.id = testcase.catid and testcase.id = results.tcid" .
					" AND (keywords LIKE '%,{$word},%' OR keywords LIKE '{$word},%') order by build";
			} else {
				$sql = "select tcid,status from results,project,component,category,testcase" .
					" where project.id = '" . $_SESSION['testPlanId'] . 
					"' and results.build = '" . $build . 
					"' and project.id = component.projid" .
					" and component.id = category.compid" .
					" and category.id = testcase.catid and testcase.id = results.tcid" .
					" AND (keywords LIKE '%,{$word},%' OR keywords LIKE '{$word},%')";
			}
			$totalResult = do_mysql_query($sql);

			//Setting the results to an array.. Only taking the most recent results and displaying them
			while($totalRow = mysql_fetch_row($totalResult)){

				//This is a test.. I've got a problem if the user goes and sets a previous p,f,b value to a 'n' value. The program then sees the most recent value as an not run. I think we want the user to then see the most recent p,f,b value
				if($totalRow[1] != $g_tc_status['not_run']){
					$testCaseArray[$totalRow[0]] = $totalRow[1];
				}
			}

			//This is the code that determines the pass,fail,blocked amounts

			//Initializing variables
			$arrayCounter = 0; //Counter
			$pass = 0;
			$fail = 0;
			$blocked = 0;
			$notRun = 0;

			//I had to write this code so that the loop before would work.. I'm sure there is a better way to do it but hell if I know how to figure it out..
			if(count($testCaseArray) > 0){
				foreach($testCaseArray as $tc){
					if($tc == $g_tc_status['passed']){
						$pass++;
					}elseif($tc == $g_tc_status['failed']){
						$fail++;
					}elseif($tc == $g_tc_status['blocked']){
						$blocked++;
					}
				}//end for each
			}//end if

			//destroy the testCaseArray variable
			unset($testCaseArray);

			$notRunTCs = $totalTCs[0] - ($pass + $fail + $blocked); //Getting the not run TCs
				
			if($totalTCs[0] == 0){ //if we try to divide by 0 we get an error
				$percentComplete = 0;
			}else{
				$percentComplete = ($pass + $fail + $blocked) / $totalTCs[0]; //Getting total percent complete
				$percentComplete = round((100 * ($percentComplete)),2); //Rounding the number so it looks pretty
			}		

			array_push($arrOutput, array($keyWord, $totalTCs[0], $pass, $fail, $blocked,
					$notRunTCs, $percentComplete));
		}
	}
	return $arrOutput;
}

/**
* Function generates Metrics based on owner
*
* @return array $owner, $totalTCs, $pass, $fail, $blocked,
*				$notRunTCs, $percentComplete
*/
function getOwnerReport()
{
	global $g_tc_status;

	$testCaseArray = null;
	$arrOutput = array();
	$sql = "select category.owner, category.id from project,component, category where project.id = '" . $_SESSION['testPlanId'] . "' and project.id = component.projid and component.id = category.compid group by owner";
	$result = do_mysql_query($sql);

	while ($myrow = mysql_fetch_row($result)) {
		//Code to grab the entire amount of test cases per project
		$sql = "select count(testcase.id) from project,component,category,testcase where project.id = '" . $_SESSION['testPlanId'] . "' and project.id = component.projid and category.owner ='" . $myrow[0] . "' and component.id = category.compid and category.id = testcase.catid";
		$totalTCResult = do_mysql_query($sql);
		$totalTCs = mysql_fetch_row($totalTCResult);

		//Code to grab the results of the test case execution
		$sql = "select tcid,status from results,project,component,category,testcase " .
				"where project.id = '" . $_SESSION['testPlanId'] . 
				"' and category.owner='" . $myrow[0] . 
				"' and project.id = component.projid and component.id = category.compid" .
				" and category.id = testcase.catid and testcase.id = results.tcid" .
				" order by build";
		$totalResult = do_mysql_query($sql);

		//Setting the results to an array.. Only taking the most recent results and displaying them
		while($totalRow = mysql_fetch_row($totalResult)){
			//This is a test.. I've got a problem if the user goes and sets a previous p,f,b value to a 'n' value. The program then sees the most recent value as an not run. I think we want the user to then see the most recent p,f,b value
			if($totalRow[1] != $g_tc_status['not_run']){
				$testCaseArray[$totalRow[0]] = $totalRow[1];
			}
		}

		//This is the code that determines the pass,fail,blocked amounts

		//Initializing variables
		$arrayCounter = 0; //Counter
		$pass = 0;
		$fail = 0;
		$blocked = 0;
		$notRun = 0;

		//I had to write this code so that the loop before would work.. I'm sure there is a better way to do it but hell if I know how to figure it out..

		if(count($testCaseArray) > 0){
			//This loop will cycle through the arrays and count the amount of p,f,b,n
			foreach($testCaseArray as $tc){

				if($tc == $g_tc_status['passed']){
					$pass++;
				}elseif($tc == $g_tc_status['failed']){
					$fail++;
				}elseif($tc == $g_tc_status['blocked']){
					$blocked++;
				}
			}//end foreach
		}//end if

		//destroy the testCaseArray variable
		unset($testCaseArray);
		
		$notRunTCs = $totalTCs[0] - ($pass + $fail + $blocked); //Getting the not run TCs
		
		if($totalTCs[0] == 0){ //if we try to divide by 0 we get an error
			$percentComplete = 0;
		}else{
			$percentComplete = ($pass + $fail + $blocked) / $totalTCs[0]; //Getting total percent complete
			$percentComplete = round((100 * ($percentComplete)),2); //Rounding the number so it looks pretty
		}		

		array_push($arrOutput, array($myrow[0], $totalTCs[0], $pass, $fail, $blocked,
				$notRunTCs, $percentComplete));
	}
	return $arrOutput;
}

/**
* Function generates Metrics based on priority
*
* @param string build ID (optional)
* @return array 
*/
// MHT 200507 GENERAL REFACTORIZATION (use array through the function); SQL improve
function getPriorityReport($build = 'all')
{
	global $g_tc_status;
  
	// grabs the defined priority 
	$priority = getPriorityDefine();
	
	//Initializing variables
	$arrAvailablePriority = array('a','b','c');
	$myResults = array ( 
		'a' => array('priority' => 'A', 'total' => 0, 'pass' => 0, 'fail' => 0, 'blocked' => 0, 'milestone' => '-', 'status' => '-'),
		'b' => array('priority' => 'B', 'total' => 0, 'pass' => 0, 'fail' => 0, 'blocked' => 0, 'milestone' => '-', 'status' => '-'),
		'c' => array('priority' => 'C', 'total' => 0, 'pass' => 0, 'fail' => 0, 'blocked' => 0, 'milestone' => '-', 'status' => '-'),
		'milestone' => 'None', 
		'deadline' => 'None'
	);
	
	//Begin code to display the component
	$sql = "SELECT category.risk, category.id, category.importance " .
			"FROM project,component, category WHERE project.id = '" . 
			$_SESSION['testPlanId'] . "' AND project.id = component.projid " .
			"AND component.id = category.compid";
	$result = do_mysql_query($sql);
	
	while ($myrow = mysql_fetch_row($result)) {
	
		$testCaseArray = null;

		$priStatus = $myrow[2] . $myrow[0]; //Concatenate the importance and priority together
		tLog('Category ID=' . $myrow[1] . ' has priority ' . $priStatus . ' and status ' . $priority[$priStatus]);
		
		//Code to grab the entire amount of test cases per project
		$sql = "SELECT count(testcase.id) FROM component,category,testcase WHERE " .
				"component.projid = " . $_SESSION['testPlanId'] . " AND category.id=" . 
				$myrow[1] .	" AND component.id = category.compid AND category.id = testcase.catid";
		$totalTCResult = do_mysql_query($sql);
		$totalTCs = mysql_fetch_row($totalTCResult);
	
		//Code to grab the results of the test case execution
		if ($build == 'all'){
			$sql = "SELECT tcid,status FROM results,component,category,testcase" .
				" WHERE component.projid = " . $_SESSION['testPlanId'] . 
				" AND category.id=" . $myrow[1] .
				" AND component.id = category.compid AND category.id = testcase.catid" .
				" AND testcase.id = results.tcid ORDER BY build";
		} else {
			$sql = "SELECT tcid,status FROM results,project,component,category,testcase" .
				" WHERE component.projid = " . $_SESSION['testPlanId'] .
				" AND results.build='" . $build . "' AND category.id=" . $myrow[1] . 
				" AND component.id = category.compid AND category.id = testcase.catid" .
				" AND testcase.id = results.tcid";
		}
		$totalResult = do_mysql_query($sql);
	
		//Setting the results to an array.. Only taking the most recent results and displaying them
		while($totalRow = mysql_fetch_row($totalResult)){
	
			//This is a test.. I've got a problem if the user goes and sets a previous p,f,b value to a 'n' value. The program then sees the most recent value as an not run. I think we want the user to then see the most recent p,f,b value
			if($totalRow[1] != $g_tc_status['not_run']){
				$testCaseArray[$totalRow[0]] = $totalRow[1];
			}
		}
	
		//This is the code that determines the pass,fail,blocked amounts
		$arrayCounter = 0; //Counter
	
		//Initializing variables
		$pass = 0;
		$fail = 0;
		$blocked = 0;
		$notRun = 0;
	
		//I had to write this code so that the loop before would work.. I'm sure there is a better way to do it but hell if I know how to figure it out..
		if(count($testCaseArray) > 0)	{
			//This loop will cycle through the arrays and count the amount of p,f,b,n
			foreach($testCaseArray as $tc) {
				if($tc == $g_tc_status['passed']){
					$pass++;
				} elseif($tc == $g_tc_status['failed']) {
					$fail++;
				} elseif($tc == $g_tc_status['blocked']) {
					$blocked++;
				}
			}//end foreach
		}//end if
		unset($testCaseArray);
	
		//This next section figures out how many priority A,B or C test cases there and adds them together
		$myResults[$priority[$priStatus]]['total'] = $myResults[$priority[$priStatus]]['total'] + $totalTCs[0];
		$myResults[$priority[$priStatus]]['pass'] = $myResults[$priority[$priStatus]]['pass'] + $pass;
		$myResults[$priority[$priStatus]]['fail'] = $myResults[$priority[$priStatus]]['fail'] + $fail;
		$myResults[$priority[$priStatus]]['blocked'] = $myResults[$priority[$priStatus]]['blocked'] + $blocked;
		
	}
	
	foreach ($arrAvailablePriority as $i)
	{
		$myResults[$i]['withStatus'] = $myResults[$i]['pass'] + $myResults[$i]['fail'] + 
				$myResults[$i]['blocked'];
		//Getting the not run TCs
		$myResults[$i]['notRun'] = $myResults[$i]['total'] - ($myResults[$i]['withStatus']); 
		
		if($myResults[$i]['total'] == 0)
		{
			$myResults[$i]['percentComplete'] = 0;
	
		}else
		{
			$myResults[$i]['percentComplete'] = round((100 * ($myResults[$i]['withStatus'] / $myResults[$i]['total'])),2); //Rounding the number so it looks pretty
		}
	}

	//This next section gets the milestones information
	$sql = "select name,date,A,B,C from milestone where projid='" . 
			$_SESSION['testPlanId'] . "' and to_days(date) >= to_days(now()) " .
			"order by date limit 1";
	$result = do_mysql_query($sql); //Run the query
	$numRows = mysql_num_rows($result); //How many rows
	
	//Check to see if there are any milestone rows
	if($numRows > 0){
	
		$currentMilestone = mysql_fetch_row($result);
	
		$myResults['milestone'] = $currentMilestone[0];
		$myResults['deadline'] = $currentMilestone[1];
		$myResults['a']['milestone'] = $currentMilestone[2]; // $MA
		$myResults['b']['milestone'] = $currentMilestone[3];
		$myResults['c']['milestone'] = $currentMilestone[4];
	
		//This next section figures out if the status is red yellow or green..
		//Check to see if milestone is set to zero. Will cause division error
		foreach ($arrAvailablePriority as $i)
		{
			//	MHT 200507	removed from condition:		 ||| $myResults[$i]['total'] == 0) {
			if(intval($myResults[$i]['milestone']) > 0) 
			{
				$relStatus = $myResults[$i]['percentComplete'] / $myResults[$i]['milestone'];
				if($relStatus >= 0.9) {
					$myResults[$i]['status'] = "<font color='#669933'>GREEN</font>";
				}
				elseif($relStatus >= 0.8) {
					$myResults[$i]['status'] = '<font color="#FFCC00">YELLOW</font>';
				}
				else{
					$myResults[$i]['status'] = '<font color="#FF0000">RED</font>';
				}
			}
		} 
	}

	// MHT: smarty template maintains this as ordered array
	return array($myResults['a'], $myResults['b'], $myResults['c'], 'milestone' => $myResults['milestone'], 
		'deadline' => $myResults['deadline']);

} // priority


/**
* Function return array of defined priorities that the user has assigned for the current Test Plan
*
* @return array 
*/
// MHT 200507 refactorization, improved sql query
function getPriorityDefine()
{
	$sql = "SELECT  riskImp,priority FROM priority WHERE priority.projid = " . $_SESSION['testPlanId'];
	return selectOptionData($sql);
}

// MHT 200507 refactorization
function getPriority($priorityStatus, $dependencies)
{
	return $dependencies[$priorityStatus];
}

/**
* Function generates Build Metrics based on category
*
* @param string build ID 
* @return array 
*/
function getBuildMetricsCategory($build)
{
	global $g_tc_status;
	
	$arrOutput = array();
	// grabs the defined priority 
	$dependencies = getPriorityDefine();

	//get Component
	$sql = "select component.name, component.id from project,component" .
			" where project.id = '" . $_SESSION['testPlanId'] . 
			"' and project.id = component.projid";
	$result = do_mysql_query($sql);

	while ($myrow = mysql_fetch_row($result)) {

		//Proceed each of the categories for the components
		$categoryQuery = "select category.name, category.id, risk, importance from project,component,category where project.id = '" . $_SESSION['testPlanId'] . "' and project.id = component.projid and component.id = category.compid and component.id =" . $myrow[1];
		$categoryResult = do_mysql_query($categoryQuery);
	
		while ($categoryRow = mysql_fetch_row($categoryResult)) {
			
			$catAllSql = "select count(testcase.id) from project,component,category,testcase where project.id = '" . $_SESSION['testPlanId'] . "' and project.id = component.projid and component.id = category.compid and category.id = testcase.catid and component.id ='" . $myrow[1] . "' and category.id='" . $categoryRow[1] . "'";
			$catTotalResult = do_mysql_query($catAllSql);
			$totalRow = mysql_fetch_row($catTotalResult);
			
			// 20050425 - fm
			$base_sql = "SELECT count(testcase.id) " .
		            "FROM project,component,category,testcase,results " .
			          "WHERE project.id = '" . $_SESSION['testPlanId'] . "' " .
			          "AND project.id = component.projid " .
			          "AND component.id = category.compid " .
			          "AND category.id = testcase.catid " .
			          "AND component.id ='" . $myrow[1] . "' " .
			          "AND testcase.id = results.tcid " .
			          "AND results.build='" . $build . "' " .
			          "AND category.id='" . $categoryRow[1] . "' ";
			
			
			//Passed TCs per category
			$sql = $base_sql . " and results.status='" . $g_tc_status['passed'] ."'";
			$passedResult = do_mysql_query($sql);
			$passedRow = mysql_fetch_row($passedResult);
	
			//Failed TCs per category
			$sql = $base_sql . " and results.status='" . $g_tc_status['failed'] ."'";
			$failedResult = do_mysql_query($sql);
			$failedRow = mysql_fetch_row($failedResult);

			//Blocked TCs per category
			$sql = $base_sql . " and results.status='" . $g_tc_status['blocked'] ."'";
			$blockedResult = do_mysql_query($sql);
			$blockedRow = mysql_fetch_row($blockedResult);
	
	
			//Not Run TCs per category
			$notRun = $totalRow[0] - ($passedRow[0] + $failedRow[0] + $blockedRow[0]);
			if($totalRow[0] == 0) { //if we try to divide by 0 we get an error
				$percentComplete = 0;
			} else {
				$percentComplete = ($passedRow[0] + $failedRow[0] + $blockedRow[0]) / $totalRow[0]; //Getting total percent complete
				$percentComplete = round((100 * ($percentComplete)),2); //Rounding the number so it looks pretty
			}
	
			//Determining Priority from risk and importance
			$priorityStatus = $categoryRow[3] . $categoryRow[2];
			$priority = getPriority($priorityStatus, $dependencies);
	

			//save
			array_push($arrOutput, array($myrow[0] . ' / ' . $categoryRow[0], 
					$categoryRow[2], $categoryRow[3] , $priority, $totalRow[0], 
					$passedRow[0], $failedRow[0], $blockedRow[0], $notRun, 
					$percentComplete));
	
		}
	}//END WHILE
	return $arrOutput;

} // END function getMetricsCategory

/**
* Function generates Build Metrics based on component
*
* @param string build ID 
* @return array 
*/
function getBuildMetricsComponent($build)
{
	global $g_tc_status;

	$arrOutput = array();

	//get Component
	$sql = "select component.name, component.id from project,component" .
			" where project.id = '" . $_SESSION['testPlanId'] . 
			"' and project.id = component.projid";
	$result = do_mysql_query($sql);

	while ($myrow = mysql_fetch_row($result)) {
		$componentName = $myrow[0];
				
		//How many TCs per component
		$sql = "select count(testcase.id) from project,component,category,testcase" .
				" where project.id = '" . $_SESSION['testPlanId'] . 
				"' and project.id = component.projid and component.id = category.compid" .
				" and category.id = testcase.catid and component.id ='" . $myrow[1] . "'";
		$totalResult = do_mysql_query($sql);
		$totalRow = mysql_fetch_row($totalResult);
		
		//Passed TCs per component
		$base_sql = "select count(testcase.id) " .
		       "from project,component,category,testcase,results " . 
		       "where project.id = '" . $_SESSION['testPlanId'] . "' " .
		       "and project.id = component.projid " .
		       "and component.id = category.compid " .
		       "and category.id = testcase.catid " .
		       "and component.id ='" . $myrow[1] . "' " .
		       "and testcase.id = results.tcid " .
		       "and results.build='" . $build . "' " ;
		
		$sql = $base_sql .  " and results.status='" . $g_tc_status['passed'] . "'";
		$passedResult = do_mysql_query($sql);
		$passedRow = mysql_fetch_row($passedResult);
	
		//Failed TCs per component
		$sql = $base_sql .  " and results.status='" . $g_tc_status['failed'] . "'";
		$failedResult = do_mysql_query($sql);
		$failedRow = mysql_fetch_row($failedResult);
	
		//Blocked TCs per component
		$sql = $base_sql .  " and results.status='" . $g_tc_status['blocked'] . "'";
		$blockedResult = do_mysql_query($sql);
		$blockedRow = mysql_fetch_row($blockedResult);

		//Not Run TCs per component
		$notRun = $totalRow[0] - ($passedRow[0] + $failedRow[0] + $blockedRow[0]);
		if($totalRow[0] == 0) { //if we try to divide by 0 we get an error
			$percentComplete = 0;
		} else {
			$percentComplete = ($passedRow[0] + $failedRow[0] + $blockedRow[0]) / $totalRow[0]; //Getting total percent complete
			$percentComplete = round((100 * ($percentComplete)),2); //Rounding the number so it looks pretty
		}

		// save	
		array_push($arrOutput, array($componentName, $totalRow[0], 
					$passedRow[0], $failedRow[0], $blockedRow[0], $notRun, 
					$percentComplete));
	
	}//END WHILE
	return $arrOutput;

} // END function getMetricsComponent


/** @todo add build relation */
function getBugsReport($build = 'all')
{
	global $g_bugInterfaceOn;
	global $g_bugInterface;
	
	$arrOutput = array();

	$sql = "select title, component.name, category.name, testcase.id, mgttcid" .
			" from project,component,category,testcase where project.id='" . 
			$_SESSION['testPlanId'] . "' and project.id=component.projid" .
			" and component.id=category.compid and category.id=testcase.catid" .
			" order by testcase.id";
	$result = do_mysql_query($sql);
	while ($myrow = mysql_fetch_row($result)) {
		$bugString = null;
		$sqlBugs = "select bug from bugs where tcid='" . $myrow[3] . "'";
		$resultBugs = do_mysql_query($sqlBugs);
		while ($myrowBug = mysql_fetch_row($resultBugs))
		{
			if (!is_null($bugString))
				$bugString .= ","; 
			$bugID = $myrowBug[0];
			if($g_bugInterfaceOn)
				$bugString .= $g_bugInterface->buildViewBugLink($bugID);
			else
				$bugString .= $bugID;
		}
		// save
		array_push($arrOutput, array($myrow[1] . ' / ' . $myrow[2], 
				$myrow[4] . ': ' . htmlspecialchars($myrow[0]), $bugString));

		if($bugString != "") {
			unset($bugString);
		}
	}

	return $arrOutput;
}

/**
* get % completed TCs
*
* @param integer $total
* @param integer $run = $totalPassed + $totalFailed + $totalBlocked
* @return real $percentageCompleted
*/
function getPercentageCompleted($total, $run)
{
	if($total == 0)	{
		$percentComplete = 0;
	} else {
		//rounded total percent completed
		$percentComplete = round((100 * $run / $total ),2); 
	}
	return $percentComplete;
}


/**
* create Test Suite list (Component)
*
* @return array associated $id => $name
*/
function listTPComponent()
{
	$suites = array();
	//Gather all of the current projects components for the dropdown box
	$sqlCom = "select component.id,component.name from component, project" .
			" where component.projid='" . $_SESSION['testPlanId'] . 
			"' and project.id='" . $_SESSION['testPlanId'] . "'";
	$result = do_mysql_query($sqlCom);

	//loop through all of the components and build the options for the select box			
	while ($myrow = mysql_fetch_row($result)){
		$suites[$myrow[0]] = $myrow[1];
	}
	return $suites;
}


// ---- FUNCTIONS FOR MAIL -------------------------------------------------------------
/**
* this function takes all of the priority info and puts it in a variable.. 
*/
function reportGeneralStatus()
{
	$arrData = getPriorityReport();
	// array('A', $totalA, $AStatus, $passA, $failA, $blockedA, $notRunTCsA, $percentCompleteA, $MA),
	
	$msgBody = null;
	foreach ($arrData['values'] as $priority)
	{
		$msgBody .= " Priority " . $priority[0] . " Test Cases\n\n";
		$msgBody .= " Total: " . $priority[1] . "\n";
		$msgBody .= " Passed: " . $priority[3] . "\n";
		$msgBody .= " Failed: " . $priority[4] . "\n";
		$msgBody .= " Blocked: " . $priority[5] . "\n";
		$msgBody .= " Not Run: " . $priority[6] . "\n";
		$msgBody .= " Percentage complete: " . $priority[7] . "\n";
		if ($priority[2] != '-')
		{
			$msgBody .= " Percentage complete against current Milestone: " . $priority[8] . "\n";
			$msgBody .= " Status against current Milestone: " . $priority[2] . "\n\n";
		}
	}

	return $msgBody;
}


function reportBuildStatus($build,$buildName)
{
	global $g_tc_status;
	
	$sql = "select count(testcase.id) from project,component,category,testcase where project.id =" . 
	       $_SESSION['testPlanId'] . " and project.id = component.projid and component.id = category.compid and category.id = testcase.catid";
	$sumResult = do_mysql_query($sql);
	$sumTCs = mysql_fetch_row($sumResult); 
	$total = $sumTCs[0];

	$base_sql = "SELECT count(results.tcid) " .
              "FROM project,component,category,testcase,results " .
              "WHERE project.id =" . $_SESSION['testPlanId'] . 
              " AND project.id = component.projid " .
              " AND component.id = category.compid " .
              " AND category.id = testcase.catid " .
              " AND testcase.id = results.tcid " .
              " AND build = '" . $build . "' " ;
              
              
  
	//Get the total # of passed testcases for the project and build
	$sql = $base_sql . " AND status ='" . $g_tc_status['passed'] . "'";
	$passedResult = do_mysql_query($sql);
	$passedTCs = mysql_fetch_row($passedResult);
	$totalPassed = $passedTCs[0];

	//Get the total # of failed testcases for the project
	$sql = $base_sql . " AND status ='" . $g_tc_status['failed'] . "'";
	$failedResult = do_mysql_query($sql);
	$failedTCs = mysql_fetch_row($failedResult);
	$totalFailed = $failedTCs[0];

	//Get the total # of blocked testcases for the project
	$sql = $base_sql . " AND status ='" . $g_tc_status['blocked'] . "'";
	$blockedResult = do_mysql_query($sql);
	$blockedTCs = mysql_fetch_row($blockedResult);
	$totalBlocked = $blockedTCs[0];

	//total # of testcases not run
	$run = $totalPassed + $totalFailed + $totalBlocked;
	$notRun = $total - $run;
	$percentComplete = getPercentageCompleted($total, $run);

	
	$msgBody = lang_get("trep_status_for_build").": " . $buildName . "\n\n";
	$msgBody .= lang_get("trep_total").": " . $total . "\n";
	$msgBody .= lang_get("trep_passed").": " . $totalPassed . "\n";
	$msgBody .= lang_get("trep_failed").": " . $totalFailed . "\n";
	$msgBody .= lang_get("trep_blocked").": " . $totalBlocked . "\n";
	$msgBody .= lang_get("trep_not_run").": " . $notRun . "\n";
	$msgBody .= lang_get("trep_comp_perc").": " . $percentComplete. "%\n\n";

	
	return $msgBody;
}

function reportSuiteBuildStatus($comID, $build,$buildName)
{
	global  $g_tc_status;  
	
	$sql = "select count(testcase.id) from project,component,category,testcase where project.id =" . 
	       $_SESSION['testPlanId'] . " and project.id = component.projid and component.id = category.compid and category.id = testcase.catid and component.id=" . $comID;
	$sumResult = do_mysql_query($sql);
	$sumTCs = mysql_fetch_row($sumResult); 
	$total = $sumTCs[0];

	//Get the total # of passed testcases for the project and build
	$base_sql = "SELECT count(results.tcid) " .
	            "FROM project,component,category,testcase,results " .
	            "WHERE project.id =" . $_SESSION['testPlanId'] . 
	            " AND project.id = component.projid " .
	            " AND component.id = category.compid " .
	            " AND category.id = testcase.catid " .
	            " AND testcase.id = results.tcid " .
	            " AND build = '" . $build . "'" .
	            " AND component.id=" . $comID;
	            
	            
	
	$sql = $base_sql . " AND status ='" . $g_tc_status['passed'] . "'";
	$passedResult = do_mysql_query($sql);
	$passedTCs = mysql_fetch_row($passedResult);
	$totalPassed = $passedTCs[0];

	//Get the total # of failed testcases for the project
	$sql = $base_sql . " AND status ='" . $g_tc_status['failed'] . "'";
	$failedResult = do_mysql_query($sql);
	$failedTCs = mysql_fetch_row($failedResult);
	$totalFailed = $failedTCs[0];

	//Get the total # of blocked testcases for the project
	$sql = $base_sql . " AND status ='" . $g_tc_status['blocked'] . "'";
	$blockedResult = do_mysql_query($sql);
	$blockedTCs = mysql_fetch_row($blockedResult);
	$totalBlocked = $blockedTCs[0];

	//total # of testcases not run
	$run = $totalPassed + $totalFailed + $totalBlocked;
	$notRun = $total - $run;
	$percentComplete = getPercentageCompleted($total, $run);

	$sqlCOMName = "select component.name from component where id=" . $comID;
	$resultCOMName = do_mysql_query($sqlCOMName);
	$COMName = mysql_fetch_row($resultCOMName);

	$msgBody = lang_get("trep_status_for_ts") . " " . $COMName[0] . " in Build: " . $buildName . "\n\n";
	$msgBody .= lang_get("trep_total").": " . $total . "\n";
	$msgBody .= lang_get("trep_passing").": " . $totalPassed . "\n";
	$msgBody .= lang_get("trep_failing").": " . $totalFailed . "\n";
	$msgBody .= lang_get("trep_blocked").": " . $totalBlocked . "\n";
	$msgBody .= lang_get("trep_not_run").": " . $notRun . "\n";
	$msgBody .= lang_get("trep_comp_perc").": " . $percentComplete. "%\n\n";
	
	return $msgBody;
}


function reportSuiteStatus($comID)
{
	global $g_tc_status;
  
	//Code to grab the entire amount of test cases per project
	$sql = "select count(testcase.id) from project,component,category,testcase where project.id = '" . 
	       $_SESSION['testPlanId'] . "' and component.id='" . $comID . "' and project.id = component.projid and component.id = category.compid and category.id = testcase.catid";
	$totalTCResult = do_mysql_query($sql);
	$totalTCs = mysql_fetch_row($totalTCResult);

	//Code to grab the results of the test case execution
	$sql = "select tcid,status from results,project,component,category,testcase where project.id = '" . 
	       $_SESSION['testPlanId'] . "' and component.id='" . $comID . "' and project.id = component.projid and component.id = category.compid and category.id = testcase.catid and testcase.id = results.tcid order by build";
	$totalResult = do_mysql_query($sql);

	//Setting the results to an array.. Only taking the most recent results and displaying them
	while($totalRow = mysql_fetch_row($totalResult))	{
		//This is a test.. I've got a problem if the user goes and sets a previous p,f,b value to a 'n' value. The program then sees the most recent value as an not run. I think we want the user to then see the most recent p,f,b value
		if($totalRow[1] != $g_tc_status['not_run']){
			$testCaseArray[$totalRow[0]] = $totalRow[1];
		}
	}

	//This is the code that determines the pass,fail,blocked amounts
	//Initializing variables
	$arrayCounter = 0;
	$pass = 0;
	$fail = 0;
	$blocked = 0;
	$notRun = 0;

	//I had to write this code so that the loop before would work.. I'm sure there is a better way to do it but hell if I know how to figure it out..
	
	if(count($testCaseArray) > 0) {
		foreach($testCaseArray as $tc) {
			if($tc == $g_tc_status['passed']) {
				$pass++;
			} elseif($tc == $g_tc_status['failed']) {
				$fail++;
			} elseif($tc ==  $g_tc_status['blocked']) {
				$blocked++;
			}

			unset($testCaseArray);
		}//end foreach
	}//end if

	$run = $pass + $fail + $blocked;
	$notRun = $totalTCs[0] - $run;
	$percentComplete = getPercentageCompleted($totalTCs[0], $run);

	//Grab the component's name
	$sqlCOMName = "select component.name from component where id=" . $comID;
	$resultCOMName = do_mysql_query($sqlCOMName);
	$COMName = mysql_fetch_row($resultCOMName);

	$msgBody = lang_get("trep_status_for_ts") .": ". $COMName[0] . "\n\n";
	$msgBody .= lang_get("trep_total").": " . $totalTCs[0] . "\n";
	$msgBody .= lang_get("trep_passed").": " . $pass . "\n";
	$msgBody .= lang_get("trep_failed").": " . $fail . "\n";
	$msgBody .= lang_get("trep_blocked").": " . $blocked . "\n";
	$msgBody .= lang_get("trep_not_run").": " . $notRun . "\n";
	$msgBody .= lang_get("trep_comp_perc").": " . $percentComplete. "%\n\n";

	return $msgBody;
}
?>