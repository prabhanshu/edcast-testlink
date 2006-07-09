<?php

class results
{
  var $db;
  var $tp;
  var $mySuiteList;

  function results(&$db, &$tp)
  {
    $this->db = &$db;	
    $this->tp = &$tp;
    
    $this->mySuiteList = array();

    $this->buildSuiteList();
  }

  function getSuiteList(){
    return $this->mySuiteList;
  }

  // map suite ids to arrays of results for those suites
  function buildSuiteList(){
    $linked_tcversions = $this->tp->get_linked_tcversions($_SESSION['testPlanId']);
    //    print count(array_keys($linked_tcversions));
    // print "<BR>";
    while ($testcaseID = key($linked_tcversions)){
      $info = $linked_tcversions[$testcaseID];
      //$notSure = $info[0];
      $testsuite_id = $info[testsuite_id];

      $currentSuite;
      if (!(array_key_exists($testsuite_id, $this->mySuiteList))){
	    $currentSuite = array();
      }
      else {
	$currentSuite = $this->mySuiteList[$testsuite_id];
      }

      //$notSure2 = $info[1];
      //$tc_id = $info[tc_id];
      $tcversion_id = $info[tcversion_id];
      //$notSure3 = $info[3];
      $executed = $info[executed];
      $executionExists = 1;

      if ($tcversion_id != $executed){
	// this test case not been executed in this test plan
	$executionExists = 0;
      }

      // select * from executions where tcversion_id = $executed;

      if ($executionExists) {
	// NOTE TO SELF - this is where we can include the searching of results
	// over multiple test plans - by modifying this select statement slightly
	// to include multiple test plan ids

	$execQuery = $this->db->fetchArrayRowsIntoMap("select * from executions where tcversion_id = $executed AND testplan_id = $_SESSION[testPlanId]", 'id');
	//    print_r($execQuery);
        while($executions_id = key($execQuery)){
	  $notSureA = $execQuery[$executions_id];
	  $exec_row = $notSureA[0];
	  $build_id = $exec_row[build_id];
	  $tester_id = $exec_row[tester_id];
	  $execution_ts = $exec_row[execution_ts];
	  $status = $exec_row[status];
	  $testplan_id = $exec_row[testplan_id];
	  $notes = $exec_row[notes];

	  $infoToSave = array($testcaseID, $tcversion_id, $build_id, $tester_id, $execution_ts, $status, $notes);
	  array_push($currentSuite, $infoToSave);
	  next($execQuery);
	}
      }
      $this->mySuiteList[$testsuite_id] = $currentSuite;
      next($linked_tcversions);
    } 

    // $numberOfKeys = 
    print_r($this->mySuiteList);
  } // end function
} // end class result


?>