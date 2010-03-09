<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *  
 * Filename $RCSfile: xmlrpc.php,v $
 *
 * @version $Revision: 1.83 $
 * @modified $Date: 2010/03/09 06:45:44 $ by $Author: franciscom $
 * @author 		Asiel Brumfield <asielb@users.sourceforge.net>
 * @package 	TestlinkAPI
 * 
 * The Testlink API makes it possible to interact with Testlink  
 * using external applications and services. This makes it possible to report test results 
 * directly from automation frameworks as well as other features.
 * 
 * See examples for additional detail
 * @example sample_clients/java/org/testlink/api/client/sample/TestlinkAPIXMLRPCClient.java java client sample
 * @example sample_clients/php/clientSample.php php client sample
 * @example sample_clients/ruby/clientSample.rb ruby client sample
 * @example sample_clients/python/clientSample.py python client sample
 * 
 *
 * rev : 
 *	20100308 - franciscom - BUGID 3243 - checkPlatformIdentity()
 *	20100205 - franciscom - BUGID 3140 - _checkTCIDAndTPIDValid()	
 *	20091228 - franciscom - checkReqSpecQuality() - refactoring due to req versioning feature
 *	20091212 - franciscom - BUGID 2998 - contribution - getTestSuiteByID()
 *  20091128 - franciscom - getTestCaseIDBy() - added testcasepathname
 *  20091113 - franciscom - work for adding overwrite argument to reportTCResult() started.
 *  20090917 - franciscom - reportTCResult() manages platform info
 *	20090902 - franciscom - test case preconditions field
 *	20090804 - franciscom - deleteExecution() - new method (need more work)
 *	20090801 - franciscom - getTestCasesForTestPlan() allows keyword passed by name
 *	20090727 - franciscom - added contribution BUGID  - reportTCResult() accepts CF info
 *	20090726 - franciscom - added contribution BUGID 2719 - getFullPath()
 *	20090609 - franciscom - createTestPlan() - new method
 *	                        WORK TO BE DONE, limit lenght of any limited string (names, prefix, etc)
 *	
 *	20090521 - franciscom - refactoring to manage DB_TABLE_PREFIX
 *	20090521 - franciscom - getTestCase() - development started
 *	20090426 - franciscom - getLastExecutionResult(), changed return type when there is not execution.
 *	                        getTestCaseAttachments(), test case external id can be used on call
 *	                        BUGID 2441 - getTestProjectByName(), getTestPlanByName() - new methods.
 *	
 *	20090420 - franciscom - BUGID 2158 - full implementation of getTestCaseCustomFieldDesignValue()
 *	20090411 - franciscom - BUGID 2369 - changes in addTestCaseToTestPlan()
 *	20090314 - franciscom - createTestSuite()
 *	20090303 - franciscom - BUGID 2179
 *	20090218 - franciscom - Contribution by JaskaJ - BUGID 2127 - getTestCaseAttachments() Refactored 
 *	                         
 *	20090214 - franciscom - BUGID 2098 - getTestCasesForTestPlan() - added executiontype parameter
 *	20090209 - franciscom - getTestCasesForTestPlan()
 *	                        added summary,steps,expected_results,tsuite_name in returned info
 *	                        reportTCResult() - contribution by hnishiyama - optional bug id 
 *	
 *	20090209 - franciscom - getTestCasesForTestSuite() - refactoring
 *	20090208 - franciscom - reading status from configuration using config_get()
 *	                        fixed bad check on checkBuildID()
 *	20090126 - franciscom - added some contributions by hnishiyama. 
 *	20090125 - franciscom - getLastTestResult() -> getLastExecutionResult()
 *	20090122 - franciscom - assignRequirements()
 *	20090117 - franciscom - createTestProject()
 *	20090116 - franciscom - getFirstLevelTestSuitesForTestProject()
 *	                        getTestCaseIDByName() - added testprojectname param
 *	
 *	20090113 - franciscom - BUGID 1982 - addTestCaseToTestPlan()
 *	20090106 - franciscom - createTestCase() - first implementation
 *	20080409 - azl - implement using the testsuitename param with the getTestCaseIDByName method
 *	20080309 - sbouffard - contribution - BUGID 1420: added getTestCasesForTestPlan (refactored by franciscom)
 *	20080307 - franciscom - now is possible to use test case external or internal ID
 *	                        when calling reportTCResult()
 *	20080306 - franciscom - BUGID 1421
 *	20080305 - franciscom - minor code refactoring
 *	20080103 - franciscom - fixed minor bugs due to refactoring
 *	20080115 - havlatm - 0001296: API table refactoring 
 */

/** 
 * IXR is the class used for the XML-RPC server 
 */
require_once(dirname(__FILE__) . "/../../third_party/xml-rpc/class-IXR.php");
require_once("api.const.inc.php");
require_once(dirname(__FILE__) . "/../../config.inc.php");
require_once(dirname(__FILE__) . "/../functions/common.php");
require_once("APIErrors.php");

// Can we use autoload ?
//require_once(dirname(__FILE__) . "/../functions/testproject.class.php");
//require_once(dirname(__FILE__) . "/../functions/testcase.class.php");
//require_once(dirname(__FILE__) . "/../functions/testsuite.class.php");
//require_once(dirname(__FILE__) . "/../functions/user.class.php");

/**
 * The entry class for serving XML-RPC Requests
 * 
 * See examples for additional detail
 * @example sample_clients/java/org/testlink/api/client/sample/TestlinkAPIXMLRPCClient.java java client sample
 * @example sample_clients/php/clientSample.php php client sample
 * @example sample_clients/ruby/clientSample.rb ruby client sample
 * @example sample_clients/python/clientSample.py python client sample
 * 
 * @author 		Asiel Brumfield <asielb@users.sourceforge.net>
 * @package 	TestlinkAPI 
 * @since 		Class available since Release 1.8.0
 * @version 	1.0
 */
class TestlinkXMLRPCServer extends IXR_Server
{
    public static $version = "1.0";
 
    
    const   OFF=false;
    const   ON=true;
    const   BUILD_GUESS_DEFAULT_MODE=OFF;
    const   SET_ERROR=true;
    	
	/**
	 * The DB object used throughout the class
	 * 
	 * @access private
	 */
	private $dbObj = null;
	private $tables = null;

	private $tcaseMgr =  null;
	private $tprojectMgr = null;
	private $tplanMgr = null;
	private $reqSpecMgr = null;
    private $reqMgr = null;

	/** Whether the server will run in a testing mode */
	private $testMode = false;

	/** userID associated with the devKey provided */
	private $userID = null;
	
	/** UserObject associated with the userID */
	private $user = null;

	/** array where all the args are stored for requests */
	private $args = null;	

	/** array where error codes and messages are stored */
	private $errors = array();

	/** The api key being used to make a request */
	private $devKey = null;

	/** The version of a test case that is being used */
	// This value is setted in following method:
	//   
	private $tcVersionID = null;
	
	
	/**#@+
	 * string for parameter names are all defined statically
	 * @static
 	 */
	public static $devKeyParamName = "devKey";
	public static $testCaseIDParamName = "testcaseid";
	public static $testCaseExternalIDParamName = "testcaseexternalid";
	public static $testPlanIDParamName = "testplanid";
	public static $testProjectIDParamName = "testprojectid";
	public static $testSuiteIDParamName = "testsuiteid";
	public static $statusParamName = "status";
	public static $buildIDParamName = "buildid";
	public static $noteParamName = "notes";
	public static $timeStampParamName = "timestamp";
	public static $guessParamName = "guess";
	public static $deepParamName = "deep";
	public static $testModeParamName = "testmode";
	public static $buildNameParamName = "buildname";
	public static $buildNotesParamName = "buildnotes";
	public static $automatedParamName = "automated";
	public static $testCaseNameParamName = "testcasename";
	public static $keywordIDParamName = "keywordid";
	public static $executedParamName = "executed";
	public static $assignedToParamName = "assignedto";
	public static $executeStatusParamName = "executestatus";
	public static $testSuiteNameParamName = "testsuitename";
	public static $testProjectNameParamName = "testprojectname";
	public static $testCasePrefixParamName = "testcaseprefix";
	public static $customFieldNameParamName = "customfieldname";
	public static $summaryParamName = "summary";
	public static $stepsParamName = "steps";
    public static $expectedResultsParamName = "expectedresults";
    public static $authorLoginParamName = "authorlogin";
    public static $executionTypeParamName = "executiontype";
    public static $importanceParamName = "importance";
    public static $orderParamName = "order";
    public static $internalIDParamName = "internalid";
    public static $checkDuplicatedNameParamName = "checkduplicatedname";
    public static $actionOnDuplicatedNameParamName = "actiononduplicatedname";
    public static $keywordNameParamName = "keywords";
    public static $versionNumberParamName = "version";
    public static $executionOrderParamName = "executionorder";
    public static $urgencyParamName = "urgency";
    public static $requirementsParamName = "requirements";
    public static $detailsParamName = "details";
	public static $bugIDParamName = "bugid";		
	public static $parentIDParamName = "parentid";		
	public static $testPlanNameParamName = "testplanname";
	public static $activeParamName = "active";
    public static $publicParamName = "public";
    public static $nodeIDParamName = "nodeid";
    public static $customFieldsParamName = "customfields";
    public static $executionIDParamName = "executionid";
    public static $preconditionsParamName = "preconditions";
    public static $platformNameParamName = "platformname";
    public static $platformIDParamName = "platformid";
	public static $overwriteParamName = "overwrite";
	public static $testCasePathNameParamName = "testcasepathname";


	// public static $executionRunTypeParamName		= "executionruntype";
		
	
	/**#@-*/
	
	/**
	 * An array containing strings for valid statuses 
	 * Will be initialized using user configuration via config_get()
	 */
    public $statusCode;
    public $codeStatus;
  
	
	/**
	 * Constructor sets up the IXR_Server and db connection
	 */
	public function __construct()
	{		
		$this->dbObj = new database(DB_TYPE);
		$this->dbObj->db->SetFetchMode(ADODB_FETCH_ASSOC);
		$this->_connectToDB();
		
		$this->tcaseMgr=new testcase($this->dbObj);
	    $this->tprojectMgr=new testproject($this->dbObj);
	    $this->tplanMgr=new testplan($this->dbObj);
	    $this->reqSpecMgr=new requirement_spec_mgr($this->dbObj);
        $this->reqMgr=new requirement_mgr($this->dbObj);
		
		$this->tables = $this->tcaseMgr->getDBTables();
		
		$resultsCfg = config_get('results');
        foreach($resultsCfg['status_label_for_exec_ui'] as $key => $label )
        {
            $this->statusCode[$key]=$resultsCfg['status_code'][$key];  
        }
        
        if( isset($this->statusCode['not_run']) )
        {
            unset($this->statusCode['not_run']);  
        }   
        $this->codeStatus=array_flip($this->statusCode);
    	
        
	    
	    $this->methods = array( 'tl.reportTCResult' => 'this:reportTCResult',
	                            'tl.setTestCaseExecutionResult' => 'this:reportTCResult',
	                            'tl.createBuild' => 'this:createBuild',
	                            'tl.createTestCase' => 'this:createTestCase',
	                            'tl.createTestPlan' => 'this:createTestPlan',
	                            'tl.createTestProject' => 'this:createTestProject',
	                            'tl.createTestSuite' => 'this:createTestSuite',
                                'tl.assignRequirements' => 'this:assignRequirements',     
                                'tl.addTestCaseToTestPlan' => 'this:addTestCaseToTestPlan',
	                            'tl.getProjects' => 'this:getProjects',
	                            'tl.getTestProjectByName' => 'this:getTestProjectByName',
	                            'tl.getTestPlanByName' => 'this:getTestPlanByName',
	                            'tl.getProjectTestPlans' => 'this:getProjectTestPlans',
	                            'tl.getBuildsForTestPlan' => 'this:getBuildsForTestPlan',
	                            'tl.getLatestBuildForTestPlan' => 'this:getLatestBuildForTestPlan',	
                                'tl.getLastExecutionResult' => 'this:getLastExecutionResult',
	                            'tl.getTestSuitesForTestPlan' => 'this:getTestSuitesForTestPlan',
	                            'tl.getTestCasesForTestSuite'	=> 'this:getTestCasesForTestSuite',
	                            'tl.getTestCasesForTestPlan' => 'this:getTestCasesForTestPlan',
	                            'tl.getTestCaseIDByName' => 'this:getTestCaseIDByName',
                                'tl.getTestCaseCustomFieldDesignValue' => 'this:getTestCaseCustomFieldDesignValue',
                                'tl.getFirstLevelTestSuitesForTestProject' => 'this:getFirstLevelTestSuitesForTestProject',     
                                'tl.getTestCaseAttachments' => 'this:getTestCaseAttachments',
	                            'tl.getTestCase' => 'this:getTestCase',
                                'tl.getFullPath' => 'this:getFullPath',
                                'tl.getTestSuiteByID' => 'this:getTestSuiteByID',
                                'tl.deleteExecution' => 'this:deleteExecution',
			                    'tl.about' => 'this:about',
			                    'tl.setTestMode' => 'this:setTestMode',
                    			// ping is an alias for sayHello
                    			'tl.ping' => 'this:sayHello', 
                    			'tl.sayHello' => 'this:sayHello',
                    			'tl.repeat' => 'this:repeat'
		                      );				
		
		$this->IXR_Server($this->methods);		
	}	
	
	private function _setArgs($args)
	{
		// TODO: should escape args
		$this->args = $args;
	}
	
	/**
	 * Set the BuildID from one place
	 * 
	 * @param int $buildID
	 * @access private
	 */
	private function _setBuildID($buildID)
	{		
		if(GENERAL_ERROR_CODE != $buildID)
		{			
			$this->args[self::$buildIDParamName] = $buildID;			
			return true;
		}
		else
		{
			$this->errors[] = new IXR_Error(INVALID_BUILDID, INVALID_BUILDID_STR);
			return false;
		}	
	}
	
	
	/**
	 * Set test case internal ID
	 * 
	 * @param int $tcaseID
	 * @access private
	 */
	private function _setTestCaseID($tcaseID)
	{		
			$this->args[self::$testCaseIDParamName] = $tcaseID;			
	}
	
	/**
	 * Set Build Id to latest build id (if test plan has builds)
	 * 
	 * @return boolean
	 * @access private
	 */ 
	private function _setBuildID2Latest()
	{
	    $tplan_id=$this->args[self::$testPlanIDParamName];
        $maxbuildid = $this->tplanMgr->get_max_build_id($tplan_id);
	    $status_ok=($maxbuildid >0);
	    if($status_ok)
	    {
	        $this->_setBuildID($maxbuildid);  
	    } 
	    return $status_ok;
	}	
		
	/**
	 * connect to the db and set up the db object 
	 *
	 * @access private
	 */		
	private function _connectToDB()
	{
		if(true == $this->testMode)
		{
			return $this->dbObj->connect(TEST_DSN, TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS, TEST_DB_NAME);
		}
		else
		{
			return $this->dbObj->connect(DSN, DB_HOST, DB_USER, DB_PASS, DB_NAME);
		}					
	}

	/**
	 * authenticates a user based on the devKey provided 
	 * 
	 * This is the only method that should really be used directly to authenticate
	 *
	 * @return boolean
	 * @access private
	 */
    protected function authenticate($messagePrefix='')
    {   
             	
	    // check that the key was given as part of the args
	    if(!$this->_isDevKeyPresent())
	    {
	    	$this->errors[] = new IXR_ERROR(NO_DEV_KEY, $messagePrefix . NO_DEV_KEY_STR);
	    	return false;
	    }
	    else
	    {
	    	$this->devKey = $this->args[self::$devKeyParamName];
	    }
	    // make sure the key we have is valid
	    if(!$this->_isDevKeyValid($this->devKey))
	    {
	    	$this->errors[] = new IXR_Error(INVALID_AUTH, $messagePrefix . INVALID_AUTH_STR);
	    	return false;			
	    }
	    else
	    {
	    	//Load User
	    	$this->user = tlUser::getByID($this->dbObj,$this->userID);		    	
	    	return true;
	    }				
    }
    
    
    /*
     function: userHasRight

     args :
    
     returns: 
    */
    protected function userHasRight($roleQuestion)
    {
      	$status_ok = true;
    	if(!$this->user->hasRight($this->dbObj,$roleQuestion,$this->tprojectid, $this->tplanid))
    	{
    		$status_ok = false;
    		$this->errors[] = new IXR_Error(INSUFFICIENT_RIGHTS, INSUFFICIENT_RIGHTS_STR);
    	}
    	return $status_ok;
    }

	/**
	 * Helper method to see if the testcasename provided is valid 
	 * 
	 * This is the only method that should be called directly to check the testcasename
	 * 	
	 * @return boolean
	 * @access private
	 */        
    protected function checkTestCaseName()
    {
        $status = true;
    	if(!$this->_isTestCaseNamePresent())
    	{
    	  	$this->errors[] = new IXR_Error(NO_TESTCASENAME, NO_TESTCASENAME_STR);
    	  	$status=false;
    	}
    	else
    	{
    	    $testCaseName = $this->args[self::$testCaseNameParamName];
    	    if(!is_string($testCaseName))
    	    {
    	    	$this->errors[] = new IXR_Error(TESTCASENAME_NOT_STRING, TESTCASENAME_NOT_STRING_STR);
    	    	$status=false;
    	    }
    	}
    	return $status;
    }
    
	/**
	 * Helper method to see if the status provided is valid 
	 * 
	 * This is the only method that should be called directly to check the status
	 * 	
	 * @return boolean
	 * @access private
	 */    
    protected function checkStatus()
    {
		    if( ($status=$this->_isStatusPresent()) )
		    {
		        if( !($status=$this->_isStatusValid($this->args[self::$statusParamName])))
		        {
		        	$this->errors[] = new IXR_Error(INVALID_STATUS, INVALID_STATUS_STR);
		        }    	
        }
        else
        {
            $this->errors[] = new IXR_Error(NO_STATUS, NO_STATUS_STR);
        }
        return $status;
    }       
    
	/**
	 * Helper method to see if the tcid provided is valid 
	 * 
	 * This is the only method that should be called directly to check the tcid
	 * 	
	 * @return boolean
	 * @access private
	 */    
    protected function checkTestCaseID($messagePrefix='')
    {
        $msg = $messagePrefix;
        $status_ok=$this->_isTestCaseIDPresent();
        if( $status_ok)
        {
            $tcaseid = $this->args[self::$testCaseIDParamName];
            if(!$this->_isTestCaseIDValid($tcaseid))
            {
            	$this->errors[] = new IXR_Error(INVALID_TCASEID, $msg . INVALID_TCASEID_STR);
            	$status_ok=false;
            }
        }    	
        else
        {
        	$this->errors[] = new IXR_Error(NO_TCASEID, $msg . NO_TCASEID_STR);
        }
        return $status_ok;
    }
    
	/**
	 * Helper method to see if the tplanid provided is valid
	 * 
	 * This is the only method that should be called directly to check the tplanid
	 * 	
	 * @return boolean
	 * @access private
	 */    
    protected function checkTestPlanID($messagePrefix='')
    {
        $status=true;
    	if(!$this->_isTestPlanIDPresent())
    	{
    	    $msg = $messagePrefix . NO_TPLANID_STR;
    		$this->errors[] = new IXR_Error(NO_TPLANID, $msg);
    		$status=false;
    	}
    	else
    	{    		
    		// See if this TPID exists in the db
		    $tplanid = $this->dbObj->prepare_int($this->args[self::$testPlanIDParamName]);
        	$query = "SELECT id FROM {$this->tables['testplans']} WHERE id={$tplanid}";
        	$result = $this->dbObj->fetchFirstRowSingleColumn($query, "id");         	
        	if(null == $result)
        	{
        	      $msg = $messagePrefix . sprintf(INVALID_TPLANID_STR,$tplanid);
        		  $this->errors[] = new IXR_Error(INVALID_TPLANID, $msg);
        		  $status=false;        		
        	}
		     // tplanid exists and its valid
        	else
        	{
        		  // try to guess the buildid if it isn't already set
		    	    if(!$this->_isBuildIDPresent())
		    	    {
		     	      // can only set the build id for the test plan if guessing is enabled
    				      if(true == $this->checkGuess())
    				      {
    				      	$status = $this->_setBuildID2Latest();
    				      }
		    	    }
		    	    else
		    	      $status=true;
        	}    		    		    	
    	}
        return $status;
    } 
    
	/**
	 * Helper method to see if the TestProjectID provided is valid
	 * 
	 * This is the only method that should be called directly to check the TestProjectID
	 * 	
	 * @return boolean
	 * @access private
	 */    
    protected function checkTestProjectID($messagePrefix='')
    {
    	if(!($status=$this->_isTestProjectIDPresent()))
    	{
    		  $this->errors[] = new IXR_Error(NO_TESTPROJECTID, $messagePrefix . NO_TESTPROJECTID_STR);
    	}
    	else
    	{    		
            // See if this Test Project ID exists in the db
		    $testprojectid = $this->dbObj->prepare_int($this->args[self::$testProjectIDParamName]);
        	$query = "SELECT id FROM {$this->tables['testprojects']} WHERE id={$testprojectid}";
        	$result = $this->dbObj->fetchFirstRowSingleColumn($query, "id");         	
        	if(null == $result)
        	{
        	    $msg = $messagePrefix . sprintf(INVALID_TESTPROJECTID_STR,$testprojectid);
        		$this->errors[] = new IXR_Error(INVALID_TESTPROJECTID, $msg);
        		$status=false;        		
        	}
    	}
    	return $status;
    }  

	/**
	 * Helper method to see if the TestSuiteID provided is valid
	 * 
	 * This is the only method that should be called directly to check the TestSuiteID
	 * 	
	 * @return boolean
	 * @access private
	 */    
    protected function checkTestSuiteID($messagePrefix='')
    {
    	if(!($status=$this->_isTestSuiteIDPresent()))
    	{
    		$this->errors[] = new IXR_Error(NO_TESTSUITEID, $messagePrefix . NO_TESTSUITEID_STR);
    	}
    	else
    	{    		
            // See if this Test Suite ID exists in the db
            $tsuiteMgr = new testsuite($this->dbObj);
	        $node_info = $tsuiteMgr->get_by_id($this->args[self::$testSuiteIDParamName]);
	        if( !($status=!is_null($node_info)) )
  		    {
  		        $msg=$messagePrefix;
  		        $msg .= sprintf(INVALID_TESTSUITEID_STR, $this->args[self::$testSuiteIDParamName]);
 	            $this->errors[] = new IXR_Error(INVALID_TESTSUITEID, $msg);
        	}
    	}
        return $status;
    }          

	/**
	 * Helper method to see if the guess is set
	 * 
	 * This is the only method that should be called directly to check the guess param
	 * 
	 * Guessing is set to true by default
	 * @return boolean
	 * @access private
	 */    
    protected function checkGuess()
    {    	
    	// if guess is set return its value otherwise return true to guess by default
    	return($this->_isGuessPresent() ? $this->args[self::$guessParamName] : self::BUILD_GUESS_DEFAULT_MODE);	
    }   	
    
	/**
	 * Helper method to see if the buildID provided is valid for testplan
	 * 
	 * if build id has not been provided on call, we can use build name if has been
	 * provided.
	 *
	 * This is the only method that should be called directly to check the buildID
	 * 	
	 * @return boolean
	 * @access private
	 */    
    protected function checkBuildID($msg_prefix)
    {
        $tplan_id=$this->args[self::$testPlanIDParamName];
	   	$status=true;
	   	$try_again=false;
      	
      	// First thing is to know is test plan has any build
      	$buildQty = $this->tplanMgr->getNumberOfBuilds($tplan_id);
      	if( $buildQty == 0)
      	{
			$status = false;
			$tplan_info = $this->tplanMgr->get_by_id($tplan_id);
            $msg = $msg_prefix . sprintf(TPLAN_HAS_NO_BUILDS_STR,$tplan_info['name'],$tplan_info['id']);
            $this->errors[] = new IXR_Error(TPLAN_HAS_NO_BUILDS,$msg);
      	} 
	   	
	   	if( $status )
	   	{
	   		if(!$this->_isBuildIDPresent())
	   		{
        	    $try_again=true;
				if($this->_isBuildNamePresent())
				{
        	        $buildInfo=$this->tplanMgr->get_build_by_name($tplan_id,
        	                                                      trim($this->args[self::$buildNameParamName])); 
        	        if( !is_null($buildInfo) )
        	        {
        	            $this->args[self::$buildIDParamName]=$buildInfo['id'];
        	            $try_again=false;
        	        }
				}
			}
	   		
	   		if($try_again)
	   		{
				// this means we aren't supposed to guess the buildid
				if(false == $this->checkGuess())   		
				{
					$this->errors[] = new IXR_Error(BUILDID_NOGUESS, BUILDID_NOGUESS_STR);
					$this->errors[] = new IXR_Error(NO_BUILDID, NO_BUILDID_STR);				
    		    	$status=false;
				}
				else
				{
					$setBuildResult = $this->_setBuildID2Latest();
					if(false == $setBuildResult)
					{
						$this->errors[] = new IXR_Error(NO_BUILD_FOR_TPLANID, NO_BUILD_FOR_TPLANID_STR);
						$status=false;
					}
				}
	   		}
	   		
	   		if( $status)
	   		{
	   		    $buildID = $this->dbObj->prepare_int($this->args[self::$buildIDParamName]);
        	  $buildInfo=$this->tplanMgr->get_build_by_id($tplan_id,$buildID); 
        	  if( is_null($buildInfo) )
        	  {
        	      $tplan_info = $this->tplanMgr->get_by_id($tplan_id);
        	      $msg = sprintf(BAD_BUILD_FOR_TPLAN_STR,$buildID,$tplan_info['name'],$tplan_id);          
				    	  $this->errors[] = new IXR_Error(BAD_BUILD_FOR_TPLAN, $msg);				
				    	  $status=false;
        	  }
        	}
       	} 
		return $status;
    }
     

    /**
	 * Helper method to see if a param is present
	 * 
	 * @param string $pname parameter name 
	 * @param boolean $setError default false
	 *                if true add predefined error code to $this->error[]
	 *
	 * @return boolean
	 * @access private
	 *
	 * 
	 */  	     
	private function _isParamPresent($pname,$messagePrefix='',$setError=false)
	{
	    $status_ok=(isset($this->args[$pname]) ? true : false);
	    if(!$status_ok && $setError)
	    {
	        $msg = $messagePrefix . sprintf(MISSING_REQUIRED_PARAMETER_STR,$pname);
	        $this->errors[] = new IXR_Error(MISSING_REQUIRED_PARAMETER, $msg);				      
        }
        return $status_ok;
	}

    /**
	 * Helper method to see if the status provided is valid 
	 * 	
	 * @return boolean
	 * @access private
	 */  	     
    private function _isStatusValid($status)
    {
    	return(in_array($status, $this->statusCode));
    }           

    /**
	 * Helper method to see if a testcasename is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */          
	 private function _isTestCaseNamePresent()
	 {
		    return (isset($this->args[self::$testCaseNameParamName]) ? true : false);
	 }

    /**
	 * Helper method to see if a testcasename is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */          
	 private function _isTestCaseExternalIDPresent()
	 {
	      $status=isset($this->args[self::$testCaseExternalIDParamName]) ? true : false;
		    return $status;
	 }


    /**
	 * Helper method to see if a timestamp is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */    
    private function _isTimeStampPresent()
    {
    	return (isset($this->args[self::$timeStampParamName]) ? true : false);
    }

    /**
	 * Helper method to see if a buildID is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */    
    private function _isBuildIDPresent()
    {
    	return (isset($this->args[self::$buildIDParamName]) ? true : false);
    }
    
	/**
	 * Helper method to see if a buildname is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */    
    private function _isBuildNamePresent()
    {                                   
        $status=isset($this->args[self::$buildNameParamName]) ? true : false;
    	return $status;
    }
    
	/**
	 * Helper method to see if build notes are given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */    
    private function _isBuildNotePresent()
    {
    	return (isset($this->args[self::$buildNotesParamName]) ? true : false);
    }
    
	/**
	 * Helper method to see if testsuiteid is given as one of the arguments
	 * 	
	 * @return boolean
	 * @access private
	 */    
	private function _isTestSuiteIDPresent()
	{
		return (isset($this->args[self::$testSuiteIDParamName]) ? true : false);
	}    
    
    /**
	 * Helper method to see if a note is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */    
    private function _isNotePresent()
    {
    	return (isset($this->args[self::$noteParamName]) ? true : false);
    }        
    
    /**
	 * Helper method to see if a tplanid is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */    
    private function _isTestPlanIDPresent()
    {    	
    	return (isset($this->args[self::$testPlanIDParamName]) ? true : false);    	
    }

    /**
	 * Helper method to see if a TestProjectID is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */    
    private function _isTestProjectIDPresent()
    {    	
    	return (isset($this->args[self::$testProjectIDParamName]) ? true : false);    	
    }        
    
    /**
	 * Helper method to see if automated is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */    
    private function _isAutomatedPresent()
    {    	
    	return (isset($this->args[self::$automatedParamName]) ? true : false);    	
    }        
    
    /**
	 * Helper method to see if testMode is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */    
    private function _isTestModePresent()
    {
    	return (isset($this->args[self::$testModeParamName]) ? true : false);      
    }
    
    /**
	 * Helper method to see if a devKey is given as one of the arguments 
	 * 	 
	 * @return boolean
	 * @access private
	 */
    private function _isDevKeyPresent()
    {
    	return (isset($this->args[self::$devKeyParamName]) ? true : false);
    }
    
    /**
	 * Helper method to see if a tcid is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */
    private function _isTestCaseIDPresent()
    {
		return (isset($this->args[self::$testCaseIDParamName]) ? true : false);
    }  
    
	/**
	 * Helper method to see if the guess param is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */
    private function _isGuessPresent()
    {
      $status=isset($this->args[self::$guessParamName]) ? true : false;
		  return $status;
    }
    
    /**
	 * Helper method to see if the testsuitename param is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */
    private function _isTestSuiteNamePresent()
    {
		    return (isset($this->args[self::$testSuiteNameParamName]) ? true : false);
    }    
    
	/**
	 * Helper method to see if the deep param is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */
    private function _isDeepPresent()
    {
		return (isset($this->args[self::$deepParamName]) ? true : false);
    }      
    
	/**
	 * Helper method to see if the status param is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access private
	 */
    private function _isStatusPresent()
    {
		return (isset($this->args[self::$statusParamName]) ? true : false);
    }      
    
	/**
	 * Helper method to see if the tcid provided is valid 
	 * 	
	 * @param struct $tcaseid	 
	 * @return boolean
	 * @access private
	 */
    private function _isTestCaseIDValid($tcaseid,$messagePrefix='',$setError=false)
    {
        $status_ok=is_numeric($tcaseid);
    	if($status_ok)
        {
    	    // must be of type 'testcase' and show up in the nodes_hierarchy    	
            $tcaseid = $this->dbObj->prepare_int($tcaseid);
		    $query = " SELECT NH.id AS id " .
		             " FROM {$this->tables['nodes_hierarchy']} NH, " .
		             " {$this->tables['node_types']} NT " .
				     " WHERE NH.id={$tcaseid} AND node_type_id=NT.id " .
				     " AND NT.description='testcase'";
		    $result = $this->dbObj->fetchFirstRowSingleColumn($query, "id");
		    $status_ok = is_null($result) ? false : true; 
        }
        else if($setError)
    	{
            $this->errors[] = new IXR_Error(TCASEID_NOT_INTEGER, 
    		                                $messagePrefix . TCASEID_NOT_INTEGER_STR);
        }
  		return $status_ok;
    }    
    
    /**
	 * Helper method to see if a devKey is valid 
	 * 	
	 * @param string $devKey	 
	 * @return boolean
	 * @access private
	 */    
    private function _isDevKeyValid($devKey)
    {    	       	        
        if(null == $devKey || "" == $devKey)
        {
            return false;
        }
        else
        {   
        	$this->userID = null;
        	$this->devKey = $this->dbObj->prepare_string($devKey);
        	$query = "SELECT id FROM {$this->tables['users']} WHERE script_key='{$this->devKey}'";
        	$this->userID = $this->dbObj->fetchFirstRowSingleColumn($query, "id");
        	    	
        	if(null == $this->userID)
        	{
        		return false;        		
        	}
        	else
        	{
        		return true;
        	}
        }                    	
    }    

    /**
	 * Helper method to set the tcVersion
	 * 
	 * 		 
	 * @return boolean
	 * @access private
	 */        
    private function _setTCVersion()
    {
		// TODO: Implement
    }
    
    /**
	 * Helper method to See if the tcid and tplanid are valid together 
	 * 
	 * @return boolean
	 * @access private
	 */            
    private function _checkTCIDAndTPIDValid($platformInfo=null,$messagePrefix='')
    {  	
    	$tplan_id = $this->args[self::$testPlanIDParamName];
    	$tcase_id = $this->args[self::$testCaseIDParamName];
        $platform_id = !is_null($platformInfo) ? key($platformInfo) : null;
        
    	$info = $this->tcaseMgr->get_linked_versions($tcase_id,"ALL","ALL",$tplan_id,$platform_id);
        $status_ok = !is_null($info);
        if( $status_ok )
        {
            $this->tcVersionID = key($info);
        }
        else
        {
            $tplan_info = $this->tplanMgr->get_by_id($tplan_id);
            $tcase_info = $this->tcaseMgr->get_by_id($tcase_id);
            
            if( is_null($platform_id) )
            {
            	$msg = sprintf(TCASEID_NOT_IN_TPLANID_STR,$tcase_info[0]['name'],
            	               $this->args[self::$testCaseExternalIDParamName],$tplan_info['name'],$tplan_id);          
            	$this->errors[] = new IXR_Error(TCASEID_NOT_IN_TPLANID, $msg);
            }
            else
            {
            	
            	$msg = sprintf(TCASEID_NOT_IN_TPLANID_FOR_PLATFORM_STR,$tcase_info[0]['name'],
            	               $this->args[self::$testCaseExternalIDParamName],
            	               $tplan_info['name'],$tplan_id,$platformInfo[$platform_id],$platform_id);          
            	$this->errors[] = new IXR_Error(TCASEID_NOT_IN_TPLANID_FOR_PLATFORM, $msg);
            }
        }
        return $status_ok;      
    }

	/**
	 * Run all the necessary checks to see if the createBuild request is valid
	 *  
	 * @return boolean
	 * @access private
	 */
	private function _checkCreateBuildRequest($messagePrefix='')
	{		
	    
        $checkFunctions = array('authenticate','checkTestPlanID');
        $status_ok=$this->_runChecks($checkFunctions,$messagePrefix);
        if($status_ok)
        {
            $status_ok=$this->_isParamPresent(self::$buildNameParamName,$messagePrefix,self::SET_ERROR);            
        }       
        
	    return $status_ok;
	}	
	
	/**
	 * Run all the necessary checks to see if the createBuild request is valid
	 *  
	 * @return boolean
	 * @access private
	 */
	private function _checkGetBuildRequest()
	{		
        $checkFunctions = array('authenticate','checkTestPlanID');       
        $status_ok=$this->_runChecks($checkFunctions);       
	    return $status_ok;
	}

	
	/**
	 * Run a set of functions 
	 *  
	 * @return boolean
	 * @access private
	 */
	private function _runChecks($checkFunctions,$messagePrefix='')
	{
      foreach($checkFunctions as $pfn)
      {
          if( !($status_ok = $this->$pfn($messagePrefix)) )
          {
              break; 
          }
      } 
	    return $status_ok;
	}



	/**
	 * Gets the latest build by choosing the maximum build id for a specific test plan 
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["tplanid"]
	 * @return mixed 
	 * 				
	 * @access public
	 */		
	public function getLatestBuildForTestPlan($args)
	{
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
	    $status_ok=true;
	    $this->_setArgs($args);
        $resultInfo=array();

        $checkFunctions = array('authenticate','checkTestPlanID');       
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       

        if( $status_ok )
        {
            $testPlanID = $this->args[self::$testPlanIDParamName];
            $build_id = $this->tplanMgr->get_max_build_id($testPlanID);
         
            if( ($status_ok=$build_id > 0) )
            {
                $builds = $this->tplanMgr->get_builds($testPlanID);  
                $build_info = $builds[$build_id];
            }
            else
            {
                $tplan_info=$this->tplanMgr->get_by_id($testPlanID);
                $msg = $msg_prefix . sprintf(TPLAN_HAS_NO_BUILDS_STR,$tplan_info['name'],$tplan_info['id']);
                $this->errors[] = new IXR_Error(TPLAN_HAS_NO_BUILDS,$msg);
            }
        }
        
        return $status_ok ? $build_info : $this->errors;
	}





    /**
     * _getLatestBuildForTestPlan
     *
     */
    private function _getLatestBuildForTestPlan($args)
	{
        $builds = $this->_getBuildsForTestPlan($args);
        $maxid = -1;
		$maxkey = -1;
		foreach ($builds as $key => $build) {
    		if ($build['id'] > $maxid)
    		{
    			$maxkey = $key;
    			$maxid = $build['id'];
    		}
		}
		$maxbuild = array();
		$maxbuild[] = $builds[$maxkey];

		return $maxbuild;
	}
	
	/**
     * Gets the result of LAST EXECUTION for a particular testcase 
     * on a test plan, but WITHOUT checking for a particular build
     *
     * @param struct $args
     * @param string $args["devKey"]
     * @param int $args["tplanid"]
     * @param int $args["testcaseid"]: optional, if does not is present           
     *                                 testcaseexternalid must be present
     *
     * @param int $args["testcaseexternalid"]: optional, if does not is present           
     *                                         testcaseid must be present
     *
     * @return mixed $resultInfo
     *               if execution found, array with these keys:
     *               id (execution id),build_id,tester_id,execution_ts,
     *               status,testplan_id,tcversion_id,tcversion_number,
     *               execution_type,notes.
     *
     *               if test case has not been execute,
     *               array('id' => -1)
     *
     * @access public
     */
    public function getLastExecutionResult($args)
    {
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
        
        $this->_setArgs($args);
        $resultInfo = array();
        $status_ok=true;
                
        // Checks are done in order
        $checkFunctions = array('authenticate','checkTestPlanID','checkTestCaseIdentity');

        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && 
                   $this->_checkTCIDAndTPIDValid(null,$msg_prefix) &&
                   $this->userHasRight("mgt_view_tc");       

        if( $status_ok )
        {
            // get all, then return last
            $sql = " SELECT * FROM {$this->tables['executions']} " .
                   " WHERE testplan_id = {$this->args[self::$testPlanIDParamName]} " .
                   " AND tcversion_id IN (" .
                   " SELECT id FROM {$this->tables['nodes_hierarchy']} " .
                   " WHERE parent_id = {$this->args[self::$testCaseIDParamName]})" .
                   " ORDER BY id DESC";
                   
            $result = $this->dbObj->fetchFirstRow($sql);

            if(null == $result)
            {
               // has not been executed
               // execution id = -1 => test case has not been runned.
               $resultInfo[]=array('id' => -1);
            } 
            else
            {
               $resultInfo[]=$result;  
            }
        }
        
        return $status_ok ? $resultInfo : $this->errors;
    }




 	/**
	 * Adds the result to the database 
	 *
	 * @return int
	 * @access private
	 */			
	private function _insertResultToDB()
	{
		$build_id = $this->args[self::$buildIDParamName];
		$tester_id =  $this->userID;
		$status = $this->args[self::$statusParamName];
		$testplan_id =	$this->args[self::$testPlanIDParamName];
		$tcversion_id =	$this->tcVersionID;
		$db_now=$this->dbObj->db_now();
		$platform_id = 0;
		
		if( isset($this->args[self::$platformIDParamName]) )
		{
			$platform_id = $this->args[self::$platformIDParamName]; 	
	    }
		
		$notes='';
        $notes_field="";
        $notes_value="";  

		if($this->_isNotePresent())
		{
			$notes = $this->dbObj->prepare_string($this->args[self::$noteParamName]);
		}
		
		if(trim($notes) != "")
		{
		    $notes_field = ",notes";
		    $notes_value = ", '{$notes}'";  
		}
		
		$execution_type = constant("TESTCASE_EXECUTION_TYPE_AUTO");

		$query = "INSERT INTO {$this->tables['executions']} " .
		         " (build_id, tester_id, execution_ts, status, testplan_id, tcversion_id, " .
		         " platform_id, " .
		         " execution_type {$notes_field} ) " .
				 " VALUES({$build_id},{$tester_id},{$db_now},'{$status}',{$testplan_id}," .
				 " {$tcversion_id},{$platform_id}, {$execution_type} {$notes_value})";

		$this->dbObj->exec_query($query);
		return $this->dbObj->insert_id($this->tables['executions']);		
	}
	
	
	/**
	 * Lets you see if the server is up and running
	 *  
	 * @param struct not used	
	 * @return string "Hello!"
	 * @access public
	 */
	public function sayHello($args)
	{
		return 'Hello!';
	}

	/**
	 * Repeats a message back 
	 *
	 * @param struct $args should contain $args['str'] parameter
	 * @return string
	 * @access public
	 */	
	public function repeat($args)
	{
		$this->_setArgs($args);
		$str = "You said: " . $this->args['str'];
		return $str;
	}

	/**
	 * Gives basic information about the API
	 *
	 * @param struct not used
	 * @return string
	 * @access public
	 */	
	public function about($args)
	{
		$this->_setArgs($args);
		$str = " Testlink API Version: " . self::$version . " initially written by Asiel Brumfield\n" .
		       " with contributions by TestLink development Team";
		return $str;				
	}
	
	/**
	 * Creates a new build for a specific test plan
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testplanid"]
	 * @param string $args["buildname"];
	 * @param string $args["buildnotes"];
	 * @return mixed $resultInfo
	 * 				
	 * @access public
	 */		
	public function createBuild($args)
	{
	    $operation = __FUNCTION__;
        $messagePrefix="({$operation}) - ";
		$resultInfo = array();
		$resultInfo[0]["status"] = true;
	    $resultInfo[0]["operation"] = $operation;
		$insertID = '';
		$returnMessage = GENERAL_SUCCESS_STR;

		$this->_setArgs($args);

		// check the tpid
		if($this->_checkCreateBuildRequest($messagePrefix) && 
		   $this->userHasRight("testplan_create_build"))
		{
			$testPlanID = $this->args[self::$testPlanIDParamName];
			$buildName = $this->args[self::$buildNameParamName];					
			$buildNotes = "";
			if($this->_isBuildNotePresent())
			{			
				$buildNotes = $this->dbObj->prepare_string($this->args[self::$buildNotesParamName]);
			}
			
			
			if ($this->tplanMgr->check_build_name_existence($testPlanID,$buildName))
			{
				//Build exists so just get the id of the existing build
				$insertID = $this->tplanMgr->get_build_id_by_name($testPlanID,$buildName);
				$returnMessage = sprintf(BUILDNAME_ALREADY_EXISTS_STR,$buildName,$insertID);
		        $resultInfo[0]["status"] = false;
			
			} else {
				//Build doesn't exist so create one
				// ,$active=1,$open=1);
				$insertID = $this->tplanMgr->create_build($testPlanID,$buildName,$buildNotes);
			}
			
			$resultInfo[0]["id"] = $insertID;	
			$resultInfo[0]["message"] = $returnMessage;
			return $resultInfo;			 	
		}
		else
		{
			return $this->errors;
		}	
	}
	
	/**
	 * Gets a list of all projects
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @return mixed $resultInfo			
	 * @access public
	 */		
	public function getProjects($args)
	{
		$this->_setArgs($args);		
		//TODO: NEED associated RIGHT
		if($this->authenticate())
		{
			return $this->tprojectMgr->get_all();	
		}
		else
		{
			return $this->errors;
		}
    }
	
	/**
	 * Gets a list of test plans within a project
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testprojectid"]
	 * @return mixed $resultInfo
	 * 				
	 * @access public
	 */		
	public function getProjectTestPlans($args)
	{
        $messagePrefix="(" .__FUNCTION__ . ") - ";
        
		$this->_setArgs($args);
		// check the tplanid
		//TODO: NEED associated RIGHT
        $checkFunctions = array('authenticate','checkTestProjectID');       
        $status_ok=$this->_runChecks($checkFunctions,$messagePrefix);       
	
		if($status_ok)
		{
			$testProjectID = $this->args[self::$testProjectIDParamName];
			$info=$this->tprojectMgr->get_all_testplans($testProjectID);
			if( !is_null($info) && count($info) > 0 )
			{
			    $info = array_values($info);
			}
			return $info;	
		}
		else
		{
			return $this->errors;
		} 
	}
	
	/**
	 * Gets a list of builds within a test plan
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testplanid"]
	 * @return 
	 *         if no errors
	 *            no build present => null
	 *            array of builds
	 *         
	 * 				
	 * @access public
	 */		
	public function getBuildsForTestPlan($args)
	{
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
        $this->_setArgs($args);

        $builds=null;
        $status_ok=true;
        $checkFunctions = array('authenticate','checkTestPlanID');       
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       
      
        if( $status_ok )
        {
            $testPlanID = $this->args[self::$testPlanIDParamName];
            $dummy = $this->tplanMgr->get_builds($testPlanID);
		  	    
		  	if( !is_null($dummy) )
		  	{
		  	   $builds=array_values($dummy);
		  	}
        }
        return $status_ok ? $builds : $this->errors;
	}


	/**
	 * List test suites within a test plan alphabetically
	 * 
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testplanid"]
	 * @return mixed $resultInfo
	 */
	 public function getTestSuitesForTestPlan($args)
	 {
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
	 	$this->_setArgs($args);

        $checkFunctions = array('authenticate','checkTestPlanID');       
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       
		if($status_ok)
		{
			$testPlanID = $this->args[self::$testPlanIDParamName];			
			$result = $this->tplanMgr->get_testsuites($testPlanID);
			return 	$result;
		}
		else
		{
			return $this->errors;
		} 
	 }
	
	/**
	 * create a test project
	 * 
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testprojectname"]
	 * @param int $args["testcaseprefix"]
	 * @param int $args["notes"]
     *	 
	 * @return mixed $resultInfo
	 */
	public function createTestProject($args)
	{
	    $this->_setArgs($args);
        $msg_prefix="(" . __FUNCTION__ . ") - ";
	    $checkRequestMethod='_check' . ucfirst(__FUNCTION__) . 'Request';
	
	    if( $this->$checkRequestMethod($msg_prefix) && $this->userHasRight("mgt_modify_product"))
	    {
	        // function create($name,$color,$options,$notes,$active=1,$tcasePrefix='')
	        $options = new stdClass();
	        $options->requirement_mgmt=1;
	        $options->priority_mgmt=1;
	        $options->automated_execution=1;
		     
	        $name=htmlspecialchars($this->args[self::$testProjectNameParamName]);
            $prefix=htmlspecialchars($this->args[self::$testCasePrefixParamName]);
            $notes=htmlspecialchars($this->args[self::$noteParamName]);
      
	        $info=$this->tprojectMgr->create($name,'',$options,$notes,1,$prefix);
		    $resultInfo = array();
		    $resultInfo[]= array("operation" => __FUNCTION__,
			                    "additionalInfo" => null,
			                    "status" => true, "id" => $info, "message" => GENERAL_SUCCESS_STR);
	        return $resultInfo;
	    }
	    else
	    {
	        return $this->errors;
	    }    
      
	}
	
  /**
   * _checkCreateTestProjectRequest
   *
   */
  private function _checkCreateTestProjectRequest($msg_prefix)
	{
      $status_ok=$this->authenticate();
      $name=$this->args[self::$testProjectNameParamName];
      $prefix=$this->args[self::$testCasePrefixParamName];
      
      if( $status_ok )
      {
          $check_op=$this->tprojectMgr->checkNameSintax($name);
          $status_ok=$check_op['status_ok'];     
          if(!$status_ok)
          {     
	           $this->errors[] = new IXR_Error(TESTPROJECTNAME_SINTAX_ERROR, 
	                                           $msg_prefix . $check_op['msg']);
          }
      }
      
      if( $status_ok ) 
      {
          $check_op=$this->tprojectMgr->checkNameExistence($name);
          $status_ok=$check_op['status_ok'];     
          if(!$status_ok)
          {     
	           $this->errors[] = new IXR_Error(TESTPROJECTNAME_EXISTS, 
	                                           $msg_prefix . $check_op['msg']);
          }
      }

      if( $status_ok ) 
      {
          $status_ok=!empty($prefix);
          if(!$status_ok)
          {     
	           $this->errors[] = new IXR_Error(TESTPROJECT_TESTCASEPREFIX_IS_EMPTY, 
	                                           $msg_prefix . $check_op['msg']);
          }
      }

      if( $status_ok ) 
      {
           $info=$this->tprojectMgr->get_by_prefix($prefix);
           if( !($status_ok = is_null($info)) )
           {
              $msg = $msg_prefix . sprintf(TPROJECT_PREFIX_ALREADY_EXISTS_STR,$prefix,$info['name']);
              $this->errors[] = new IXR_Error(TPROJECT_PREFIX_ALREADY_EXISTS,$msg);
           }
      }

  	  return $status_ok;
	}

	
	
	/**
	 * List test cases within a test suite
	 * 
	 * By default test cases that are contained within child suites 
	 * will be returned. 
	 * Set the deep flag to false if you only want test cases in the test suite provided 
	 * and no child test cases.
	 *  
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testsuiteid"]
	 * @param boolean $args["deep"] - optional (default is true)
	 * @param boolean $args["details"] - optional (default is simple)
	 *                                use full if you want to get 
	 *                                summary,steps & expected_results
	 *
	 * @return mixed $resultInfo
	 *
	 *
	 */
	 public function getTestCasesForTestSuite($args)
	 {
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
	    
		$this->_setArgs($args);
		$status_ok=$this->_runChecks(array('authenticate','checkTestSuiteID'),$msg_prefix);       
		
		$details='simple';
		$key2search=self::$detailsParamName;
		if( $this->_isParamPresent($key2search) )
		{
		    $details=$this->args[$key2search];  
		}
			
		if($status_ok && $this->userHasRight("mgt_view_tc"))
		{		
			$testSuiteID = $this->args[self::$testSuiteIDParamName];
            $tsuiteMgr = new testsuite($this->dbObj);

            // BUGID 2179
			if(!$this->_isDeepPresent() || $this->args[self::$deepParamName] )
			{
			    $pfn = 'get_testcases_deep';
			}	
			else
			{
			    $pfn = 'get_children_testcases';
			}
			return $tsuiteMgr->$pfn($testSuiteID,$details);
			
			
		}
		else
		{
			return $this->errors;
		}
	 }

  /**
  * Find a test case by its name
  * 
  * <b>Searching is case sensitive.</b> The test case will only be returned if there is a definite match.
  * If possible also pass the string for the test suite name. 
  *
  * No results will be returned if there are test cases with the same name that match the criteria provided.  
  * 
  * @param struct $args
  * @param string $args["devKey"]
  * @param string $args["testcasename"]
  * @param string $args["testsuitename"] - optional
  * @param string $args["testprojectname"] - optional
  * @param string $args["testcasepathname"] - optional
  *               Full test case path name, starts with test project name
  *               pieces separator -> :: -> default value of getByPathName()
  * @return mixed $resultInfo
  */
  public function getTestCaseIDByName($args)
  {
		$msg_prefix="(" .__FUNCTION__ . ") - ";
		$status_ok=true;
      	$this->_setArgs($args);
        $result = null;
      
      	$checkFunctions = array('authenticate','checkTestCaseName');       
      	$status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && $this->userHasRight("mgt_view_tc");       
      
      	if( $status_ok )
      	{			
          	$testCaseName = $this->args[self::$testCaseNameParamName];
          	$testCaseMgr = new testcase($this->dbObj);
 
          	$keys2check = array(self::$testSuiteNameParamName,self::$testCasePathNameParamName,
          	                    self::$testProjectNameParamName);
			foreach($keys2check as $key)
  		    {
  		        $optional[$key]=$this->_isParamPresent($key) ? trim($this->args[$key]) : '';
  		    }
  
            // 20091128 - franciscom
            if( $optional[self::$testCasePathNameParamName] != '' )
            {
          		$dummy = $testCaseMgr->getByPathName($optional[self::$testCasePathNameParamName]);
          		if( !is_null($dummy) )
          		{
          			$result[0] = $dummy;
          		}
            }
            else
            {
          		$result = $testCaseMgr->get_by_name($testCaseName,$optional[self::$testSuiteNameParamName],
            	                                    $optional[self::$testProjectNameParamName]);
          	}
          	if(0 == sizeof($result))
          	{
          	    $status_ok=false;
          	    $this->errors[] = new IXR_ERROR(NO_TESTCASE_BY_THIS_NAME, 
          	                                    $msg_prefix . NO_TESTCASE_BY_THIS_NAME_STR);
          	    return $this->errors;
          	}
      }
      return $status_ok ? $result : $this->errors; 
  }
	 
	 /**
    * createTestCase
    *
    */
	 public function createTestCase($args)
	 {
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
	    
	    $keywordSet='';
	    $this->_setArgs($args);
        $checkFunctions = array('authenticate','checkTestProjectID','checkTestSuiteID','checkTestCaseName');
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && $this->userHasRight("mgt_modify_tc");

        if( $status_ok )
        {
             $keys2check = array(self::$authorLoginParamName,
                                 self::$summaryParamName,
                                 self::$stepsParamName,
                                 self::$expectedResultsParamName);
        
                foreach($keys2check as $key)
                {
                    if(!$this->_isParamPresent($key))
                    {
                        $msg = $msg_prefix . sprintf(MISSING_REQUIRED_PARAMETER_STR,$key);
                        $this->errors[] = new IXR_Error(MISSING_REQUIRED_PARAMETER, $msg);				      
                    }   
                }
        }                        

        if( $status_ok )
        {
            $author_id = tlUser::doesUserExist($this->dbObj,$this->args[self::$authorLoginParamName]);		    	
            $status_ok = !is_null($author_id);
     	    $this->errors[] = new IXR_Error(NO_USER_BY_THIS_LOGIN, $msg_prefix . NO_USER_BY_THIS_LOGIN_STR);				
        }

        if( $status_ok )
        {
        	$keywordSet=$this->getKeywordSet($this->args[self::$testProjectIDParamName]);
        }

        if( $status_ok )
        {
            // Optional parameters
            $opt=array(self::$importanceParamName => 2,
                       self::$executionTypeParamName => TESTCASE_EXECUTION_TYPE_MANUAL,
                       self::$orderParamName => testcase::DEFAULT_ORDER,
                       self::$internalIDParamName => testcase::AUTOMATIC_ID,
                       self::$checkDuplicatedNameParamName => testcase::DONT_CHECK_DUPLICATE_NAME,
                       self::$actionOnDuplicatedNameParamName => 'generate_new',
                       self::$preconditionsParamName => '');
        
		        foreach($opt as $key => $value)
		        {
		            if($this->_isParamPresent($key))
		            {
		                $opt[$key]=$this->args[$key];      
		            }   
		        }
        }
        
             
        if( $status_ok )
        {
        	// Multiple Test Case Steps Feature - Need WORK
            // $op_result=$this->tcaseMgr->create($this->args[self::$testSuiteIDParamName],
            //                                    $this->args[self::$testCaseNameParamName],
            //                                    $this->args[self::$summaryParamName],
            //                                    $opt[self::$preconditionsParamName],
            //                                    $this->args[self::$stepsParamName],
            //                                    $this->args[self::$expectedResultsParamName],
            //                                    $author_id,$keywordSet,
            //                                    $opt[self::$orderParamName],
            //                                    $opt[self::$internalIDParamName],
            //                                    $opt[self::$checkDuplicatedNameParamName],                        
            //                                    $opt[self::$actionOnDuplicatedNameParamName],
            //                                    $opt[self::$executionTypeParamName],
            //                                    $opt[self::$importanceParamName]);
               
            $op_result=$this->tcaseMgr->create($this->args[self::$testSuiteIDParamName],
                                               $this->args[self::$testCaseNameParamName],
                                               $this->args[self::$summaryParamName],
                                               $opt[self::$preconditionsParamName],
                                               $this->args[self::$stepsParamName],
                                               $author_id,$keywordSet,
                                               $opt[self::$orderParamName],
                                               $opt[self::$internalIDParamName],
                                               $opt[self::$checkDuplicatedNameParamName],                        
                                               $opt[self::$actionOnDuplicatedNameParamName],
                                               $opt[self::$executionTypeParamName],
                                               $opt[self::$importanceParamName]);
               
            
            $resultInfo=array();
   		    $resultInfo[] = array("operation" => $operation, "status" => true, 
		                          "id" => $op_result['external_id'], 
		                          "additionalInfo" => $op_result,
		                          "message" => GENERAL_SUCCESS_STR);
        } 
        return ($status_ok ? $resultInfo : $this->errors);
	 }	
	 
	 /**
	  * Update an existing test case
	  */
	 public function updateTestCase($args)
	 {
	 	// TODO: Implement
	 } 	 	



	 /**
	 * Reports a result for a single test case
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testcaseid"]: optional, if not present           
     *                                 testcaseexternalid must be present
     *
     * @param int $args["testcaseexternalid"]: optional, if does not is present           
     *                                         testcaseid must be present
     *
	 *
	 *
	 * @param int $args["testplanid"] 
     * @param string $args["status"] - status is {@link $validStatusList}
	 * @param int $args["buildid"] - optional.
	 *                               if not present and $args["buildname"] exists
	 *	                             then 
	 *                                    $args["buildname"] will be checked and used if valid
	 *                               else 
	 *                                    build with HIGHEST ID will be used
	 *
	 * @param int $args["buildname"] - optional.
	 *                               if not present Build with higher internal ID will be used
	 *
     *
	 * @param string $args["notes"] - optional
	 * @param bool $args["guess"] - optional defining whether to guess optinal params or require them 
	 * 								              explicitly default is true (guess by default)
	 *
	 * @param string $args["bugid"] - optional
     *
     * @param string $args["platformid"] - optional, if not present platformname must be present
	 * @param string $args["platformname"] - optional, if not present platformid must be present
     *    
     *
     * @param string $args["customfields"] - optional
     *               contains an map with key:Custom Field Name, value: value for CF.
     *               VERY IMPORTANT: value must be formatted in the way it's written to db,
     *               this is important for types like:
     *
     *               DATE: strtotime()
     *               DATETIME: mktime()
     *               MULTISELECTION LIST / CHECKBOX / RADIO: se multipli selezione ! come separatore
     *
     *
     *               these custom fields must be configured to be writte during execution.
     *               If custom field do not meet condition value will not be written
     *
     * @param boolean $args["overwrite"] - optional, if present and true, then last execution
     *                for (testcase,testplan,build,platform) will be overwritten.            
     *
	 * @return mixed $resultInfo 
	 * 				[status]	=> true/false of success
	 * 				[id]		  => result id or error code
	 * 				[message]	=> optional message for error message string
	 * @access public
	 */
	public function reportTCResult($args)
	{		
		$resultInfo = array();
        $operation=__FUNCTION__;
	    $msg_prefix="({$operation}) - ";

		$this->_setArgs($args);              
		$resultInfo[0]["status"] = true;
		
        $checkFunctions = array('authenticate','checkTestCaseIdentity','checkTestPlanID',
                                'checkBuildID','checkStatus');
                                
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       

   	    if($status_ok)
		{			
			// This check is needed only if test plan has platforms
        	$platformSet = $this->tplanMgr->getPlatforms($this->args[self::$testPlanIDParamName],
        	                                              array('outputFormat' => 'map'));  
			$targetPlatform = null;
			if( !is_null($platformSet) )
            {       
	    		$status_ok = $this->checkPlatformIdentity($this->args[self::$testPlanIDParamName],$platformSet,$msg_prefix);
				if($status_ok)
				{
					$targetPlatform[$this->args[self::$platformIDParamName]] = $platformSet[$this->args[self::$platformIDParamName]];
				}
	    	}
	    	
			$status_ok = $status_ok && $this->_checkTCIDAndTPIDValid($targetPlatform,$msg_prefix);
	    }
	
		if($status_ok && $this->userHasRight("testplan_execute"))
		{		
			$executionID = 0;	
			$resultInfo[0]["operation"] = $operation;
    	    $resultInfo[0]["overwrite"] = false;
		    $resultInfo[0]["status"] = true;
			$resultInfo[0]["message"] = GENERAL_SUCCESS_STR;

    	    if($this->_isParamPresent(self::$overwriteParamName))
    	    {
    	    	$executionID = $this->_updateResult();
    	    	$resultInfo[0]["overwrite"] = true;			
    	    }
    	    if($executionID == 0)
            {
            	$executionID = $this->_insertResultToDB();			
            } 

			$resultInfo[0]["id"] = $executionID;	
			
			// Do we need to insert a bug ?
    	    if($this->_isParamPresent(self::$bugIDParamName))
    	    {
    	    	$bugID = $this->args[self::$bugIDParamName];
		    	$resultInfo[0]["bugidstatus"] = $this->_insertExecutionBug($executionID, $bugID);
    	    }
    	    
    	    
    	    if($this->_isParamPresent(self::$customFieldsParamName))
    	    {
    	    	$resultInfo[0]["customfieldstatus"] =  
    	    		$this->_insertCustomFieldExecValues($executionID);   
    	    }
			return $resultInfo;
		}
		else
		{
			return $this->errors;			
		}

	}
	
	
	/**
	 * turn on/off testMode
	 *
	 * This method is meant primarily for testing and debugging during development
	 * @param struct $args
	 * @return boolean
	 * @access private
	 */	
	public function setTestMode($args)
	{
		$this->_setArgs($args);
		
		if(!$this->_isTestModePresent())
		{
			$this->errors[] = new IXR_ERROR(NO_TEST_MODE, NO_TEST_MODE_STR);
			return false;
		}
		else
		{
			// TODO: should probably validate that this is a bool or t/f string
			$this->testMode = $this->args[self::$testModeParamName];
			return true;			
		}
	}	
	
	
	/**
	 * Helper method to see if the testcase identity provided is valid 
	 * Identity can be specified in one of these modes:
	 *
	 * test case internal id
	 * test case external id  (PREFIX-NNNN) 
	 * 
	 * This is the only method that should be called directly to check test case identoty
	 * 	
	 * If everything OK, test case internal ID is setted.
	 *
	 * @return boolean
	 * @access private
	 */    
    protected function checkTestCaseIdentity($messagePrefix='')
    {
        // Three Cases - Internal ID, External ID, No Id        
        $status=true;
        $tcaseID=0;
        $my_errors=array();
		$fromExternal=false;
		$fromInternal=false;

	    if($this->_isTestCaseIDPresent())
	    {
		      $fromInternal=true;
		      $tcaseID = $this->args[self::$testCaseIDParamName];
		      $status = true;
	    }
		elseif ($this->_isTestCaseExternalIDPresent())
		{
            $fromExternal = true;
			$tcaseExternalID = $this->args[self::$testCaseExternalIDParamName]; 
		    $tcaseCfg=config_get('testcase_cfg');
		    $glueCharacter=$tcaseCfg->glue_character;
		    $tcaseID=$this->tcaseMgr->getInternalID($tcaseExternalID,$glueCharacter);
            $status = $tcaseID > 0 ? true : false;
            
            //Invalid TestCase ID
            if( !$status )
            {
              	$my_errors[] = new IXR_Error(INVALID_TESTCASE_EXTERNAL_ID, 
                                             sprintf($messagePrefix . INVALID_TESTCASE_EXTERNAL_ID_STR,$tcaseExternalID));                  
            }
		}
	    if( $status )
	    {
	        $my_errors=null;
	        if($this->_isTestCaseIDValid($tcaseID,$messagePrefix))
	        {
	            $this->_setTestCaseID($tcaseID);  
	        }  
	        else
	        {  
	        	  if ($fromInternal)
	        	  {
	        	  	$my_errors[] = new IXR_Error(INVALID_TCASEID, $messagePrefix . INVALID_TCASEID_STR);
	        	  } 
	        	  elseif ($fromExternal)
	        	  {
	        	  	$my_errors[] = new IXR_Error(INVALID_TESTCASE_EXTERNAL_ID, 
                                                 sprintf($messagePrefix . INVALID_TESTCASE_EXTERNAL_ID_STR,$tcaseExternalID));
	        	  }
	        	  $status=false;
	        }    	
	    }
	    
	    
	    if (!$status)
	    {
            foreach($my_errors as $error_msg)
		    {
		          $this->errors[] = $error_msg; 
		    } 
	    }
	    return $status;
    }   

	 /**
	 * getTestCasesForTestPlan
	 * List test cases linked to a test plan
	 * 
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testplanid"]
	 * @param int $args["testcaseid"] - optional
	 * @param int $args["buildid"] - optional
	 * @param int $args["keywordid"] - optional mutual exclusive with $args["keywordname"]
	 * @param int $args["keywords"] - optional  mutual exclusive with $args["keywordid"]
	 *
	 * @param boolean $args["executed"] - optional
	 * @param int $args["$assignedto"] - optional
	 * @param string $args["executestatus"] - optional
	 * @param array $args["executiontype"] - optional
	 *
	 * @return mixed $resultInfo
	 */
	 public function getTestCasesForTestPlan($args)
	 {

	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
         
        // Optional parameters that are not mutual exclusive
        $opt=array(self::$testCaseIDParamName => null,
                   self::$buildIDParamName => null,
                   self::$keywordIDParamName => null,
                   self::$executedParamName => null,
                   self::$assignedToParamName => null,
                   self::$executeStatusParamName => null,
                   self::$executionTypeParamName => null);
         	
        $optMutualExclusive=array(self::$keywordIDParamName => null,
                                  self::$keywordNameParamName => null); 	
        $this->_setArgs($args);
		
		// Test Case ID, Build ID are checked if present
		if(!$this->_checkGetTestCasesForTestPlanRequest($msg_prefix) && $this->userHasRight("mgt_view_tc"))
		{
			return $this->errors;
		}
		
		$tplanid=$this->args[self::$testPlanIDParamName];
		$tplanInfo=$this->tplanMgr->tree_manager->get_node_hierarchy_info($tplanid);
		
		foreach($opt as $key => $value)
		{
		    if($this->_isParamPresent($key))
		    {
		        $opt[$key]=$this->args[$key];      
		    }   
		}
		
		$keywordSet = null;
        $keywordList=$this->getKeywordSet($tplanInfo['parent_id']);
        if( !is_null($keywordList) )
        {
        	$keywordSet = explode(",",$keywordList);
        }
		
    // public function get_linked_tcversions($id,
    // $tcase_id=null ,
    // $keyword_id=0 ,  --> can be an array!!!!
    // $executed=null ,
    // $assigned_to=null ,
    // $exec_status=null ,
    // $build_id=0 ,
    // $cf_hash = null ,
    // $include_unassigned=false ,
    // $urgencyImportance = null ,
    // $tsuites_id=null ,
    // $exec_type=null ,
    // $details='simple')
    //  
       
        $options = array('executed_only' => $opt[self::$executedParamName], 'details' => 'full');
		$filters = array('tcase_id' => $opt[self::$testCaseIDParamName],
			             'keyword_id' => $opt[self::$keywordIDParamName],
			             'assigned_to' => $opt[self::$assignedToParamName],
			             'exec_status' => $opt[self::$executeStatusParamName],
			             'build_id' => $opt[self::$buildIDParamName],
			             'exec_type' => $opt[self::$executionTypeParamName]);
		
		
		// $recordset=$this->tplanMgr->get_linked_tcversions($tplanid,                                      
		//                                                   $opt[self::$testCaseIDParamName],
        //                                             	  $keywordSet,
		//                                             	  $opt[self::$executedParamName],
        //                                             	  $opt[self::$assignedToParamName],
        //                                             	  $opt[self::$executeStatusParamName],
	 	//                                             	  $opt[self::$buildIDParamName],
	 	//                                             	  null,false,null,null,
	 	//                                             	  $opt[self::$executionTypeParamName],'full');
	 	
		$recordset=$this->tplanMgr->get_linked_tcversions($tplanid,$filters,$options);
		return $recordset;
	 }


	/**
	 * Run all the necessary checks to see if ...
	 *  
	 * @return boolean
	 * @access private
	 */
	private function _checkGetTestCasesForTestPlanRequest($messagePrefix='')
	{
		$status=$this->authenticate();
		if($status)
		{
	        $status &=$this->checkTestPlanID($messagePrefix);
	        
	        if($status && $this->_isTestCaseIDPresent($messagePrefix))
	        {
	            $status &=$this->_checkTCIDAndTPIDValid(null,$messagePrefix);
	        }
	        if($status && $this->_isBuildIDPresent($messagePrefix))  
	        {
	            $status &=$this->checkBuildID($messagePrefix);
	        }
		}
		return $status;
	}
	
  /**
	 * Gets value of a Custom Field with scope='design' for a given Test case
	 *
	 * @param struct $args
	 * @param string $args["devKey"]: used to check if operation can be done.
	 *                                if devKey is not valid => abort.
	 *
	 * @param string $args["testcaseexternalid"]:  
	 * @param string $args["testprojectid"]: 
	 * @param string $args["customfieldname"]: custom field name
	 * @param string $args["details"] optional, changes output information
	 *                                null or 'value' => just value
	 *                                'full' => a map with all custom field definition
	 *                                             plus value and internal test case id
	 *                                'simple' => value plus custom field name, label, and type (as code).
     *
     * @return mixed $resultInfo
	 * 				
	 * @access public
	 */		
    public function getTestCaseCustomFieldDesignValue($args)
	{
        $msg_prefix="(" .__FUNCTION__ . ") - ";
		$this->_setArgs($args);		
        $checkFunctions = array('authenticate','checkTestProjectID','checkTestCaseIdentity');
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       

        if( $status_ok )
        {
		    $status_ok=$this->_isParamPresent(self::$customFieldNameParamName,$msg_prefix,self::SET_ERROR);
        }
        
        
        if($status_ok)
		{
            $ret = $this->checkTestCaseAncestry();
            $status_ok=$ret['status_ok'];
            if($status_ok )
            {
                $status_ok=$this->_checkGetTestCaseCustomFieldDesignValueRequest($msg_prefix);
            }
            else 
            {
                $this->errors[] = new IXR_Error($ret['error_code'], $msg_prefix . $ret['error_msg']); 
            }           
		}
        
		if($status_ok && $this->userHasRight("mgt_view_tc"))
		{
		    $details='value';
		    if( $this->_isParamPresent(self::$detailsParamName) )
		    {
		        $details=$this->args[self::$detailsParamName];  
		    }
	    
		    
            $cf_name=$this->args[self::$customFieldNameParamName];
            $tproject_id=$this->args[self::$testProjectIDParamName];
            $tcase_id=$this->args[self::$testCaseIDParamName];
            
		    $cfield_mgr=$this->tprojectMgr->cfield_mgr;
            $cfinfo=$cfield_mgr->get_by_name($cf_name);
            $cfield=current($cfinfo);
            $filters=array('cfield_id' => $cfield['id']);
            $cfieldSpec=$this->tcaseMgr->get_linked_cfields_at_design($tcase_id,null,$filters,$tproject_id);
            // $cf_map=$cfield_mgr->string_custom_field_value($cfieldSpec[$cfield['id']],$tcase_id);
            
            switch($details)
            {
                case 'full':
                    $retval = $cfieldSpec[$cfield['id']]; 
                break;
                
                case 'simple':
                    $retval = array('name' => $cf_name, 'label' => $cfieldSpec[$cfield['id']]['label'], 
                                    'type' => $cfieldSpec[$cfield['id']]['type'], 
                                    'value' => $cfieldSpec[$cfield['id']]['value']);
                break;
                
                case 'value':
                default:
                    $retval=$cfieldSpec[$cfield['id']]['value'];
                break;
                
            }
            return $retval;
		}
		else
		{
			return $this->errors;
		} 
  }
  
  /**
	 * Run all the necessary checks to see if ...
	 *  
     * - Custom Field exists ?
     * - Can be used on a test case ?
     * - Custom Field scope includes 'design' ?
     * - is linked to testproject that owns test case ?
     * 
	 * @return boolean
	 * @access private
	 */
    private function _checkGetTestCaseCustomFieldDesignValueRequest($messagePrefix='')
	{		
	    // $status_ok=$this->authenticate($messagePrefix);
        $cf_name=$this->args[self::$customFieldNameParamName];

  	    //  $testCaseIDParamName = "testcaseid";
	    //  public static $testCaseExternalIDParamName = "testcaseexternalid";
  
        // Custom Field checks:
        // - Custom Field exists ?
        // - Can be used on a test case ?
        // - Custom Field scope includes 'design' ?
        // - is linked to testproject that owns test case ?
        //
 
        // - Custom Field exists ?
        $cfield_mgr=$this->tprojectMgr->cfield_mgr; // ($this->dbObj);
        $cfinfo=$cfield_mgr->get_by_name($cf_name);
        if( !($status_ok=!is_null($cfinfo)) )
        {
	         $msg = sprintf(NO_CUSTOMFIELD_BY_THIS_NAME_STR,$cf_name);
	         $this->errors[] = new IXR_Error(NO_CUSTOMFIELD_BY_THIS_NAME, $messagePrefix . $msg);
        }
        // $this->errors[] = current($cfinfo);
        // $status_ok=false;
      
        // - Can be used on a test case ?
        if( $status_ok )
        {
            $cfield=current($cfinfo);
            $status_ok = (strcasecmp($cfield['node_type'],'testcase') == 0 );
            if( !$status_ok )
            {
	             $msg = sprintf(CUSTOMFIELD_NOT_APP_FOR_NODE_TYPE_STR,$cf_name,'testcase',$cfield['node_type']);
	             $this->errors[] = new IXR_Error(CUSTOMFIELD_NOT_APP_FOR_NODE_TYPE, $messagePrefix . $msg);
            }
        }
 
        // - Custom Field scope includes 'design' ?
        if( $status_ok )
        {
            $status_ok = ($cfield['show_on_design'] || $cfield['enable_on_design']);
            if( !$status_ok )
            {
	             $msg = sprintf(CUSTOMFIELD_HAS_NOT_DESIGN_SCOPE_STR,$cf_name);
	             $this->errors[] = new IXR_Error(CUSTOMFIELD_HAS_NOT_DESIGN_SCOPE, $messagePrefix . $msg);
            }
        }

        // - is linked to testproject that owns test case ?
        if( $status_ok )
        {
            $allCF = $cfield_mgr->get_linked_to_testproject($this->args[self::$testProjectIDParamName]);
            $status_ok=!is_null($allCF) && isset($allCF[$cfield['id']]) ;
            if( !$status_ok )
            {
                $tproject_info = $this->tprojectMgr->get_by_id($this->args[self::$testProjectIDParamName]);
	            $msg = sprintf(CUSTOMFIELD_NOT_ASSIGNED_TO_TESTPROJECT_STR,
	                           $cf_name,$tproject_info['name'],$this->args[self::$testProjectIDParamName]);
	            $this->errors[] = new IXR_Error(CUSTOMFIELD_NOT_ASSIGNED_TO_TESTPROJECT, $messagePrefix . $msg);
            }
             
        }
      
        return $status_ok;
  }



  	/**
	 * getKeywordSet()
	 *  
	 * @param int tproject_id
	 *            
	 * @return string that represent a list of keyword id (comma is character separator)
	 *
	 * @access private
	 */
	private function getKeywordSet($tproject_id)
	{ 
    	$kMethod=null;
    	$keywordSet=null;
    	if($this->_isParamPresent(self::$keywordNameParamName))
    	{
    	    $kMethod='getValidKeywordSetByName';
    	    $accessKey=self::$keywordNameParamName;
    	}
		else if ($this->_isParamPresent(self::$keywordIDParamName))
		{
		    $kMethod='getValidKeywordSetById';
		    $accessKey=self::$keywordIDParamName;
		}
		if( !is_null($kMethod) )
		{
    	    $keywordSet=$this->$kMethod($tproject_id,$this->args[$accessKey]);
		}
    	
		return $keywordSet;
	}
	


  	/**
	 * getValidKeywordSetByName()
	 *  
	 * @return string that represent a list of keyword id (comma is character separator)
	 *
	 * @access private
	 */
	private function getValidKeywordSetByName($tproject_id,$keywords)
	{ 
		return $this->getValidKeywordSet($tproject_id,$keywords,true);
	}
	
 	/**
 	 * 
 	 * @param $tproject_id the testprojectID the keywords belong
 	 * @param $keywords array of keywords or keywordIDs
 	 * @param $byName set this to true if $keywords is an array of keywords, false if it's an array of keywordIDs
 	 * @return string that represent a list of keyword id (comma is character separator)
 	 */
	private function getValidKeywordSet($tproject_id,$keywords,$byName)
	{
		$keywordSet = '';
		$keywords = trim($keywords);
		if($keywords != "")
	  	{
	    	$a_keywords = explode(",",$keywords);
	        $items_qty = count($a_keywords);
	        for($idx = 0; $idx < $items_qty; $idx++)
	        {
				$a_keywords[$idx] = trim($a_keywords[$idx]);
	        }
	        $itemsSet = implode("','",$a_keywords);
	        $sql = " SELECT keyword,id FROM {$this->tables['keywords']} " .
	               " WHERE testproject_id = {$tproject_id} ";
	        
	        if ($byName)
	        {
	        	$sql .= " AND keyword IN ('{$itemsSet}')";
	        }
	        else
	        {
	        	$sql .= " AND id IN ({$itemsSet})";
	        }
         	
	        $keywordMap = $this->dbObj->fetchRowsIntoMap($sql,'keyword');
	        if(!is_null($keywordMap))
	        {
	        	$a_items = null;
	            for($idx = 0; $idx < $items_qty; $idx++)
	            {
	            	if(isset($keywordMap[$a_keywords[$idx]]))
	                {
	                    $a_items[] = $keywordMap[$a_keywords[$idx]]['id'];  
	                }
	            }
	            if( !is_null($a_items))
	            {
	                $keywordSet = implode(",",$a_items);
	            }    
	    	}
		}  
		return $keywordSet;
 	}
 	
  /**
	 * getValidKeywordSetById()
	 *  
	 * @return string that represent a list of keyword id (comma is character separator)
	 *
	 * @access private
	 */
    private function  getValidKeywordSetById($tproject_id,$keywords)
    {
		return getValidKeywordSet($tproject_id,$keywords,false);
    }


  // 20090126 - franciscom
  // check version > 0 - contribution 
  protected function checkTestCaseVersionNumber()
  {
        $status=true;
        if(!($status=$this->_isParamPresent(self::$versionNumberParamName)))
        {
            $msg = sprintf(MISSING_REQUIRED_PARAMETER_STR,self::$versionNumberParamName);
            $this->errors[] = new IXR_Error(MISSING_REQUIRED_PARAMETER, $msg);				      
        }
        else
        {
            $version=$this->args[self::$versionNumberParamName];
            if( !($status=is_int($version)) )
            {
            	$this->errors[] = new IXR_Error(PARAMETER_NOT_INT, PARAMETER_NOT_INT_STR);
            }
            else 
            {
                if( !($status = ($version > 0)) )
                {
                    $this->errors[] = new IXR_Error(VERSION_NOT_VALID, 
                                                    sprintf(VERSION_NOT_VALID_STR,$version));  
                }
            }
        }
        return $status;
  }

	 /**
	  * Add a test case version to a test plan 
	  *
	  * @param args['testprojectid']
	  * @param args['testplanid']
	  * @param args['testcaseexternalid']
	  * @param args['version']
	  * @param args['executionorder'] - OPTIONAL
	  * @param args['urgency'] - OPTIONAL
	  *
	  */
	 public function addTestCaseToTestPlan($args)
	 {
	    $operation=__FUNCTION__;
	    $messagePrefix="({$operation}) - ";
	    $this->_setArgs($args);
	    $op_result=null;
	    $additional_fields='';
        $checkFunctions = array('authenticate','checkTestProjectID','checkTestCaseVersionNumber',
                                'checkTestCaseIdentity','checkTestPlanID');
        
        $status_ok=$this->_runChecks($checkFunctions,$messagePrefix) && $this->userHasRight("testplan_planning");       
       
        // Test Plan belongs to test project ?
        if( $status_ok )
        {
           $tproject_id=$this->args[self::$testProjectIDParamName];
           $tplan_id=$this->args[self::$testPlanIDParamName];
           
           $sql=" SELECT id FROM {$this->tables['testplans']}" .
                " WHERE testproject_id={$tproject_id} AND id = {$tplan_id}";         
            
           $rs=$this->dbObj->get_recordset($sql);
        
           if( count($rs) != 1 )
           {
              $status_ok=false;
              $tplan_info = $this->tplanMgr->get_by_id($tplan_id);
              $tproject_info = $this->tprojectMgr->get_by_id($tproject_id);
              $msg = sprintf(TPLAN_TPROJECT_KO_STR,$tplan_info['name'],$tplan_id,
                                                   $tproject_info['name'],$tproject_id);  
              $this->errors[] = new IXR_Error(TPLAN_TPROJECT_KO,$msg_prefix . $msg); 
           }
                      
        } 
       
        // Test Case belongs to test project ?
        if( $status_ok )
        {
            $ret = $this->checkTestCaseAncestry();
            if( !$ret['status_ok'] )
            {
                $this->errors[] = new IXR_Error($ret['error_code'], $msg_prefix . $ret['error_msg']); 
            }           
        }
        
        // Does this Version number exist for this test case ?     
        if( $status_ok )
        {
            $tcase_id=$this->args[self::$testCaseIDParamName];
            $version_number=$this->args[self::$versionNumberParamName];
            $sql = " SELECT TCV.version,TCV.id " . 
                   " FROM {$this->tables['nodes_hierarchy']} NH, {$this->tables['tcversions']} TCV " .
                   " WHERE NH.parent_id = {$tcase_id} " .
                   " AND TCV.version = {$version_number} " .
                   " AND TCV.id = NH.id ";
        
           $target_tcversion=$this->dbObj->fetchRowsIntoMap($sql,'version');
           if( !is_null($target_tcversion) && count($target_tcversion) != 1 )
           {
              $status_ok=false;
              $tcase_info=$this->tcaseMgr->get_by_id($tcase_id);
              $msg = sprintf(TCASE_VERSION_NUMBER_KO_STR,$version_number,$tcase_external_id,$tcase_info[0]['name']);  
              $this->errors[] = new IXR_Error(TCASE_VERSION_NUMBER_KO,$msg_prefix . $msg); 
           }                  
                   
        }     

       if( $status_ok )
       {
           // Optional parameters
           $additional_fields=null;
           $additional_values=null;
           $opt_fields=array(self::$urgencyParamName => 'urgency', self::$executionOrderParamName => 'node_order');
           $opt_values=array(self::$urgencyParamName => null, self::$executionOrderParamName => 1);
		       foreach($opt_fields as $key => $field_name)
		       {
		           if($this->_isParamPresent($key))
		           {
		                   $additional_values[]=$this->args[$key];
		                   $additional_fields[]=$field_name;              
		           }   
		           else
		           {
                   if( !is_null($opt_values[$key]) )
                   {
		                   $additional_values[]=$opt_values[$key];
		                   $additional_fields[]=$field_name;              
		               }
               }
		       }
       }
       
       if( $status_ok )
       {
          // Other versions must be unlinked, because we can only link ONE VERSION at a time
          // 20090411 - franciscom
          // As implemented today I'm going to unlink ALL linked versions, then if version
          // I'm asking to link is already linked, will be unlinked and then relinked.
          // May be is not wise, IMHO this must be refactored, and give user indication that
          // requested version already is part of Test Plan.
          // 
          $sql = " SELECT TCV.version,TCV.id " . 
                 " FROM {$this->tables['nodes_hierarchy']} NH, {$this->tables['tcversions']} TCV " .
                 " WHERE NH.parent_id = {$tcase_id} " .
                 " AND TCV.id = NH.id ";
                 
          $all_tcversions=$this->dbObj->fetchRowsIntoMap($sql,'id');
          $id_set = array_keys($all_tcversions);
          if( count($id_set) > 0 )
          {
              $in_clause=implode(",",$id_set);
              $sql=" DELETE FROM {$this->tables['testplan_tcversions']} " .
                   " WHERE testplan_id={$tplan_id}  AND tcversion_id IN({$in_clause}) ";
           		$this->dbObj->exec_query($sql);
          }
          
          $fields="testplan_id,tcversion_id,author_id,creation_ts";
          if( !is_null($additional_fields) )
          {
             $dummy = implode(",",$additional_fields);
             $fields .= ',' . $dummy; 
          }
          
          $sql_values="{$tplan_id},{$target_tcversion[$version_number]['id']}," .
                      "{$this->userID},{$this->dbObj->db_now()}";
          if( !is_null($additional_values) )
          {
             $dummy = implode(",",$additional_values);
             $sql_values .= ',' . $dummy; 
          }

          $sql=" INSERT INTO {$this->tables['testplan_tcversions']} ({$fields}) VALUES({$sql_values})"; 
          $this->dbObj->exec_query($sql);
          
          $op_result['operation']=$operation;
          $op_result['feature_id']=$this->dbObj->insert_id($this->tables['testplan_tcversions']);
          $op_result['status']=true;
          $op_result['message']='';
       }
       
       return ($status_ok ? $op_result : $this->errors);
	 }	

  
   /*
    function: 

    args:
    
    returns: 
   */
   public function getFirstLevelTestSuitesForTestProject($args)
   {
        $msg_prefix="(" .__FUNCTION__ . ") - ";
	    $status_ok=true;
	    $this->_setArgs($args);

        $checkFunctions = array('authenticate','checkTestProjectID');
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);

        if( $status_ok )
        {
            $result = $this->tprojectMgr->get_first_level_test_suites($this->args[self::$testProjectIDParamName]);
            if( is_null($result) )
            {
                $status_ok=false;
                $tproject_info = $this->tprojectMgr->get_by_id($this->args[self::$testProjectIDParamName]);
                $msg=$msg_prefix . sprintf(TPROJECT_IS_EMPTY_STR,$tproject_info['name']);
                $this->errors[] = new IXR_ERROR(TPROJECT_IS_EMPTY,$msg); 
            } 
        }
        return $status_ok ? $result : $this->errors;       
   }
   

   /*
    function: assignRequirements
              we can assign multiple requirements.
              Requirements can belong to different Requirement Spec
             
	  @param struct $args
	  @param string $args["devKey"]
	  @param int $args["testcaseexternalid"]
	  @param int $args["testprojectid"] 
      @param string $args["requirements"] 
                  array(array('req_spec' => 1,'requirements' => array(2,4)),
                        array('req_spec' => 3,'requirements' => array(22,42))
    returns: 
   */
   public function assignRequirements($args)
   {
        $operation=__FUNCTION__;
        $msg_prefix="({$operation}) - ";
	    $status_ok=true;
	    $this->_setArgs($args);
        $resultInfo=array();
        $checkFunctions = array('authenticate','checkTestProjectID','checkTestCaseIdentity');       
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);

        if( $status_ok )
        {
            $ret = $this->checkTestCaseAncestry();
            $status_ok=$ret['status_ok'];
            if( !$status_ok )
            {
                $this->errors[] = new IXR_Error($ret['error_code'], $msg_prefix . $ret['error_msg']); 
            }           
        }
       
        if( $status_ok )
        {
            $ret = $this->checkReqSpecQuality();
            $status_ok=$ret['status_ok'];
            if( !$status_ok )
            {
                $this->errors[] = new IXR_Error($ret['error_code'], $msg_prefix . $ret['error_msg']); 
            }           
        }
       
        if($status_ok)
        {
            // assignment
            // Note: when test case identity is checked this args key is setted
            //       this does not means that this mut be present on method call.
            //
            $tcase_id=$this->args[self::$testCaseIDParamName];
            foreach($this->args[self::$requirementsParamName] as $item)
            {
                foreach($item['requirements'] as $req_id)
                {
                     $this->reqMgr->assign_to_tcase($req_id,$tcase_id);
                }          
            }
   		      $resultInfo[] = array("operation" => $operation,
   		 	                        "status" => true, "id" => -1, 
   		                            "additionalInfo" => '',
		 	                        "message" => GENERAL_SUCCESS_STR);
        }
        
        return ($status_ok ? $resultInfo : $this->errors);
  }


  /*
    function: checkTestCaseAncestry
              check if a test case belongs to test project

    args:
    
    returns: 

  */
  protected function checkTestCaseAncestry($messagePrefix='')
  {
      $ret=array('status_ok' => true, 'error_msg' => '' , 'error_code' => 0);
      $tproject_id=$this->args[self::$testProjectIDParamName];
      $tcase_id=$this->args[self::$testCaseIDParamName];
      $tcase_external_id=$this->args[self::$testCaseExternalIDParamName];
      $tcase_tproject_id=$this->tcaseMgr->get_testproject($tcase_id);
      
      if($tcase_tproject_id != $tproject_id)
      {
          $status_ok=false;
          $tcase_info=$this->tcaseMgr->get_by_id($tcase_id);
          $tproject_info = $this->tprojectMgr->get_by_id($tproject_id);
          $msg = $messagePrefix . sprintf(TCASE_TPROJECT_KO_STR,$tcase_external_id,$tcase_info[0]['name'],
                                          $tproject_info['name'],$tproject_id);  
          $ret=array('status_ok' => false, 'error_msg' => $msg , 'error_code' => TCASE_TPROJECT_KO);                                               
      } 
      return $ret;
  } // function end


  /*
    function: checkReqSpecQuality
              checks:
              Requirements Specification is present on system
              Requirements Specification belongs to test project

    args:
    
    returns: 

  */
  protected function checkReqSpecQuality()
  {
      $ret=array('status_ok' => true, 'error_msg' => '' , 'error_code' => 0);
      $tproject_id=$this->args[self::$testProjectIDParamName];
      $nodes_types = $this->tprojectMgr->tree_manager->get_available_node_types();
          
      foreach($this->args[self::$requirementsParamName] as $item)
      {
          // does it exist ?
          $req_spec_id=$item['req_spec'];
          $reqspec_info=$this->reqSpecMgr->get_by_id($req_spec_id);      
          if(is_null($reqspec_info))
          {
              $status_ok=false;
              $msg = sprintf(REQSPEC_KO_STR,$req_spec_id);
              $error_code=REQSPEC_KO;
              break;  
          }       
          
          // does it belongs to test project ?
          $a_path=$this->tprojectMgr->tree_manager->get_path($req_spec_id);
          $req_spec_tproject_id=$a_path[0]['parent_id'];
          if($req_spec_tproject_id != $tproject_id)
          {
              $status_ok=false;
              $tproject_info = $this->tprojectMgr->get_by_id($tproject_id);
              $msg = sprintf(REQSPEC_TPROJECT_KO_STR,$reqspec_info['title'],$req_spec_id,
                                                     $tproject_info['name'],$tproject_id);  
              $error_code=REQSPEC_TPROJECT_KO;
              break;  
          }
          
          // does this specification have requirements ?
          $my_requirements = $this->tprojectMgr->tree_manager->get_subtree_list($req_spec_id,$nodes_types['requirement']);
          $status_ok = (trim($my_requirements) != "");
          if(!$status_ok)
          {
              $msg = sprintf(REQSPEC_IS_EMPTY_STR,$reqspec_info['title'],$req_spec_id);
              $error_code = REQSPEC_IS_EMPTY;
              break;
          }
          
          // if everything is OK, analise requirements
          if( $status_ok )
          {
              $dummy=array_flip(explode(",",$my_requirements));
              foreach($item['requirements'] as $req_id)
              {
                  if( !isset($dummy[$req_id]) )
                  {
                      $status_ok=false;
                      $req_info = $this->reqMgr->get_by_id($req_id,requirement_mgr::LATEST_VERSION);
                      
                      if( is_null($req_info) )
                      {
                          $msg = sprintf(REQ_KO_STR,$req_id);
                          $error_code=REQ_KO;
                      }
                      else 
                      {  
                      	  $req_info = $req_inf[0];
                          $msg = sprintf(REQ_REQSPEC_KO_STR,$req_info['req_doc_id'],$req_info['title'],$req_id,
                                         $reqspec_info['title'],$req_spec_id);
                          $error_code=REQ_REQSPEC_KO;
                      }
                      break;
                  }      
              }
          }
          
          if( !$status_ok )
          {
              break;
          }
      }

      if(!$status_ok)
      {
          $ret=array('status_ok' => false, 'error_msg' => $msg , 'error_code' => $error_code);                                               
      } 
      return $ret;
  }

	/**
	 * Insert record into execution_bugs table
	 * @param  int    $executionID	 
	 * @param  string $bugID
	 * @return boolean
	 * @access private
     * contribution by hnishiyama
	**/
	private function _insertExecutionBug($executionID, $bugID)
	{
		// Check for existence of executionID
		$sql="SELECT id FROM {$this->tables['executions']} WHERE id={$executionID}";
		$rs=$this->dbObj->fetchRowsIntoMap($sql,'id');
        $status_ok = !(is_null($rs) || $bugID == '');		
		if($status_ok)
		{
            $safeBugID=$this->dbObj->prepare_string($bugID);
       	    $sql="SELECT execution_id FROM {$this->tables['execution_bugs']} " .  
		         "WHERE execution_id={$executionID} AND bug_id='{$safeBugID}'";
        
            if( is_null($this->dbObj->fetchRowsIntoMap($sql, 'execution_id')) )
            {
            	$sql = "INSERT INTO {$this->tables['execution_bugs']} " .
                       "(execution_id,bug_id) VALUES({$executionID},'{$safeBugID}')";
                $result = $this->dbObj->exec_query($sql); 
                $status_ok=$result ? true : false ;
            }
		}
		return $status_ok;
	}


/**
 * 
 *
 */
private function _getBugsForExecutionId($execution_id)
{
    $rs=null;
    if( !is_null($execution_id) && $execution_id <> '' )
    {
        $sql = "SELECT execution_id,bug_id, B.name AS build_name " .
               "FROM {$this->tables['execution_bugs']} ," .
               " {$this->tables['executions']} E, {$this->tables['builds']} B ".
               "WHERE execution_id={$execution_id} " .
               "AND   execution_id=E.id " .
               "AND   E.build_id=B.id " .
               "ORDER BY B.name,bug_id";
        $rs=$this->dbObj->fetchRowsIntoMap($sql,'bug_id');
    }
    return $rs;   
}



/**
 * Gets attachments for specified test case.
 * The attachment file content is Base64 encoded. To save the file to disk in client,
 * Base64 decode the content and write file in binary mode. 
 * 
 * @param struct $args
 * @param string $args["devKey"] Developer key
 * @param int $args["testcaseid"]: optional, if does not is present           
 *                                 testcaseexternalid must be present
 *
 * @param int $args["testcaseexternalid"]: optional, if does not is present           
 *                                         testcaseid must be present
 * 
 * @return mixed $resultInfo
 */
public function getTestCaseAttachments($args)
{
	$this->_setArgs($args);
	$attachments=null;
	$checkFunctions = array('authenticate','checkTestCaseIdentity');       
    $status_ok=$this->_runChecks($checkFunctions) && $this->userHasRight("mgt_view_tc");
	
	if($status_ok)
	{		
	    $tcase_id = $this->args[self::$testCaseIDParamName];
		$attachmentRepository = tlAttachmentRepository::create($this->dbObj);
		$attachmentInfos = $attachmentRepository->getAttachmentInfosFor($tcase_id,"nodes_hierarchy");
		
		if ($attachmentInfos)
		{
			foreach ($attachmentInfos as $attachmentInfo)
			{
				$aID = $attachmentInfo["id"];
				$content = $attachmentRepository->getAttachmentContent($aID, $attachmentInfo);
				
				if ($content != null)
				{
					$attachments[$aID]["id"] = $aID;
					$attachments[$aID]["name"] = $attachmentInfo["file_name"];
					$attachments[$aID]["file_type"] = $attachmentInfo["file_type"];
					$attachments[$aID]["title"] = $attachmentInfo["title"];
					$attachments[$aID]["date_added"] = $attachmentInfo["date_added"];
					$attachments[$aID]["content"] = base64_encode($content);
				}
			}
		}
	}
  return $status_ok ? $attachments : $this->errors;
}


    /**
	 * create a test suite
	 * 
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testprojectid"]
	 * @param string $args["testsuitename"]
	 * @param string $args["details"]
	 * @param int $args["parentid"] optional, if do not provided means test suite must be top level.
	 * @param int $args["order"] optional. Order inside parent container
	 * @param int $args["checkduplicatedname"] optional, default true.
	 *                                          will check if there are siblings with same name.
     *
     * @param int $args["actiononduplicatedname"] optional
     *                                            applicable only if $args["checkduplicatedname"]=true
	 *                                            what to do if already a sibling exists with same name.
	 *	 
	 * @return mixed $resultInfo
	 */
    public function createTestSuite($args)
	{
	    $result=array();
	    $this->_setArgs($args);
	    $operation=__FUNCTION__;
        $msg_prefix="({$operation}) - ";
        $checkFunctions = array('authenticate','checkTestSuiteName','checkTestProjectID');
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && $this->userHasRight("mgt_modify_tc");
      
        if( $status_ok )
        {
            // Optional parameters
            $opt=array(self::$orderParamName => testsuite::DEFAULT_ORDER,
                       self::$checkDuplicatedNameParamName => testsuite::CHECK_DUPLICATE_NAME,
                       self::$actionOnDuplicatedNameParamName => 'block');
            
		    foreach($opt as $key => $value)
		    {
		        if($this->_isParamPresent($key))
		        {
		            $opt[$key]=$this->args[$key];      
		        }   
		    }
        }

        if($status_ok)
        {
            $parent_id = $args[self::$testProjectIDParamName];  
            $tprojectInfo=$this->tprojectMgr->get_by_id($args[self::$testProjectIDParamName]);
            $tsuiteMgr = new testsuite($this->dbObj);
  		    if( $this->_isParamPresent(self::$parentIDParamName) )
  		    {
  		        $parent_id = $args[self::$parentIDParamName];

                // if parentid exists it must:
                // be a test suite id 
  		        $node_info = $tsuiteMgr->get_by_id($args[self::$parentIDParamName]);
  		        if( !($status_ok=!is_null($node_info)) )
  		        {
                   $msg=sprintf(INVALID_PARENT_TESTSUITEID_STR,
                                $args[self::$parentIDParamName],$args[self::$testSuiteNameParamName]);
                   $this->errors[] = new IXR_Error(INVALID_PARENT_TESTSUITEID,$msg_prefix . $msg);
                }
              
                if($status_ok)
                {
                   // Must belong to target test project
                   $root_node_id=$tsuiteMgr->getTestProjectFromTestSuite($args[self::$parentIDParamName],null);
                  
                   if( !($status_ok = ($root_node_id == $args[self::$testProjectIDParamName])) )
                   {
                     $msg=sprintf(TESTSUITE_DONOTBELONGTO_TESTPROJECT_STR,$args[self::$parentIDParamName],
                                  $tprojectInfo['name'],$args[self::$testProjectIDParamName]);
                     $this->errors[] = new IXR_Error(TESTSUITE_DONOTBELONGTO_TESTPROJECT,$msg_prefix . $msg);
                   }
                }
  		    } 
      }
      
      if($status_ok)
      {
          $op=$tsuiteMgr->create($parent_id,$args[self::$testSuiteNameParamName],
                                 $args[self::$detailsParamName],$opt[self::$orderParamName],
                                 $opt[self::$checkDuplicatedNameParamName],
                                 $opt[self::$actionOnDuplicatedNameParamName]);
          
          if( ($status_ok = $op['status_ok']) )
          {
              $op['status'] = $op['status_ok'] ? true : false;
              $op['operation'] = $operation;
              $op['additionalInfo'] = '';
              $op['message'] = $op['msg'];
              unset($op['msg']);
              unset($op['status_ok']);
              $result[]=$op;  
          }
          else
          {
              $op['msg']=sprintf($op['msg'],$args[self::$testSuiteNameParamName]);
              $this->errors=$op;   
          }
      }
      
			return $status_ok ? $result : $this->errors;
	}


	/**
	 * test suite name provided is valid 
	 * 
	 * 	
	 * @return boolean
	 * @access private
	 */        
    protected function checkTestSuiteName($messagePrefix='')
    {
        $status_ok=isset($this->args[self::$testSuiteNameParamName]) ? true : false;
        if($status_ok)
        {
    	      $name = $this->args[self::$testSuiteNameParamName];
    	      if(!is_string($name))
    	      {
                $msg=$messagePrefix . TESTSUITENAME_NOT_STRING_STR;
    	      	$this->errors[] = new IXR_Error(TESTSUITENAME_NOT_STRING, $msg);
    	      	$status_ok=false;
    	      }
        }
        else
        {
       	  	$this->errors[] = new IXR_Error(NO_TESTSUITENAME, $messagePrefix . NO_TESTSUITENAME_STR);
        }
        return $status_ok;
    }




    /**
     * Gets info about target test project
     *
     * @param struct $args
     * @param string $args["devKey"]
     * @param string $args["testprojectname"]     
     * @return mixed $resultInfo			
     * @access public
     */		
    public function getTestProjectByName($args)
    {
        $msg_prefix="(" .__FUNCTION__ . ") - ";
   	    $status_ok=true;
    	$this->_setArgs($args);		
    	if($this->authenticate())
    	{
    	    $status_ok=false; 
            if( $this->_isParamPresent(self::$testProjectNameParamName,$msg_prefix,self::SET_ERROR) )
            {
                $name=trim($this->args[self::$testProjectNameParamName]);
                $check_op=$this->tprojectMgr->checkNameExistence($name);
                $not_found=$check_op['status_ok'];     
                $status_ok=!$not_found;
                if($not_found)      
                {
                    $status_ok=false;
                    $msg = $msg_prefix . sprintf(TESTPROJECTNAME_DOESNOT_EXIST_STR,$name);
                    $this->errors[] = new IXR_Error(TESTPROJECTNAME_DOESNOT_EXIST, $msg);
                }
            }
    	}
        if($status_ok)
        {
            $info=$this->tprojectMgr->get_by_name($name);            
        }
        return $status_ok ? $info : $this->errors;
    }


    /**
     * Gets info about target test project
     *
     * @param struct $args
     * @param string $args["devKey"]
     * @param string $args["testprojectname"]     
     * @param string $args["testplanname"]     
     * @return mixed $resultInfo			
     * @access public
     */		
    public function getTestPlanByName($args)
    {
        $msg_prefix="(" .__FUNCTION__ . ") - ";
   	    $status_ok=true;
    	$this->_setArgs($args);		
    	if($this->authenticate())
    	{
            $keys2check = array(self::$testPlanNameParamName,
                                self::$testProjectNameParamName);
            foreach($keys2check as $key)
            {
                $names[$key]=$this->_isParamPresent($key,$msg_prefix,self::SET_ERROR) ? trim($this->args[$key]) : '';
                if($names[$key]=='')
                {
                    $status_ok=false;    
                    breack;
                }
            }
        }
    	
    	if($status_ok)
    	{
            // need to check name existences
            $name=$names[self::$testProjectNameParamName];
            $check_op=$this->tprojectMgr->checkNameExistence($name);
            $not_found=$check_op['status_ok'];     
            $status_ok=!$not_found;
            if($not_found)      
            {
                $status_ok=false;
                $msg = $msg_prefix . sprintf(TESTPROJECTNAME_DOESNOT_EXIST_STR,$name);
                $this->errors[] = new IXR_Error(TESTPROJECTNAME_DOESNOT_EXIST, $msg);
            }
    	    else
    	    {
    	        $tprojectInfo=current($this->tprojectMgr->get_by_name($name));
    	    }
    	}
    	
    	if($status_ok)
    	{
    	    $name=trim($names[self::$testPlanNameParamName]);
            $info = $this->tplanMgr->get_by_name($name,$tprojectInfo['id']);
            if( !($status_ok=!is_null($info)) )
            {
                $msg = $msg_prefix . sprintf(TESTPLANNAME_DOESNOT_EXIST_STR,$name,$tprojectInfo['name']);
                $this->errors[] = new IXR_Error(TESTPLANNAME_DOESNOT_EXIST, $msg);
            
            }
        }

        return $status_ok ? $info : $this->errors;
    }


/**
* get test case specification using external ir internal id
* 
* @param struct $args
* @param string $args["devKey"]
* @param int $args["testcaseid"]: optional, if does not is present           
*                                 testcaseexternalid must be present
*
* @param int $args["testcaseexternalid"]: optional, if does not is present           
*                                         testcaseid must be present
* @param int $args["version"]: optional, if does not is present max version number will be
*                                        retuned
*
* @return mixed $resultInfo
*/
public function getTestCase($args)
{
    $msg_prefix="(" .__FUNCTION__ . ") - ";
    $status_ok=true;
    $this->_setArgs($args);
    
    $checkFunctions = array('authenticate','checkTestCaseIdentity');       
    $status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && $this->userHasRight("mgt_view_tc");       
    $version_id=testcase::LATEST_VERSION;
    $version_number=-1;

    if( $status_ok )
    {			
        // check optional arguments
        if( $this->_isParamPresent(self::$versionNumberParamName) )
        {
            if( ($status_ok=$this->checkTestCaseVersionNumber()) )
            {
                $version_id=null;
                $version_number=$this->args[self::$versionNumberParamName];
            }
        }
    }
    
    if( $status_ok )
    {			
        $testCaseMgr = new testcase($this->dbObj);
        $id=$this->args[self::$testCaseIDParamName];
        
        $result = $testCaseMgr->get_by_id($id,$version_id,'ALL','ALL',$version_number);            
        if(0 == sizeof($result))
        {
            $status_ok=false;
            $this->errors[] = new IXR_ERROR(NO_TESTCASE_FOUND, 
                                            $msg_prefix . NO_TESTCASE_FOUND_STR);
            return $this->errors;
        }
    }

    return $status_ok ? $result : $this->errors; 
}



	/**
	 * create a test plan
	 * 
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testplanname"]
	 * @param int $args["testprojectname"]
	 * @param string $args["notes"], optional
	 * @param string $args["active"], optional default value 1
	 * @param string $args["public"], optional default value 1
     *	 
	 * @return mixed $resultInfo
	 */
	public function createTestPlan($args)
	{
	    $this->_setArgs($args);
	    $status_ok=true;    
        $msg_prefix="(" . __FUNCTION__ . ") - ";

    	if($this->authenticate() && $this->userHasRight("mgt_modify_product"))
    	{
            $keys2check = array(self::$testPlanNameParamName,
                                self::$testProjectNameParamName);
            foreach($keys2check as $key)
            {
                $names[$key]=$this->_isParamPresent($key,$msg_prefix,self::SET_ERROR) ? trim($this->args[$key]) : '';
                if($names[$key]=='')
                {
                    $status_ok=false;    
                    breack;
                }
            }
        }

        if( $status_ok )
        {
            $name=trim($this->args[self::$testProjectNameParamName]);
            $check_op=$this->tprojectMgr->checkNameExistence($name);
            $status_ok=!$check_op['status_ok'];     
            if($status_ok) 
            {
                $tprojectInfo=current($this->tprojectMgr->get_by_name($name));
            }
            else     
            {
                $status_ok=false;
                $msg = $msg_prefix . sprintf(TESTPROJECTNAME_DOESNOT_EXIST_STR,$name);
                $this->errors[] = new IXR_Error(TESTPROJECTNAME_DOESNOT_EXIST, $msg);
            }
        }

        if( $status_ok )
        {
    	    $name=trim($names[self::$testPlanNameParamName]);
            $info = $this->tplanMgr->get_by_name($name,$tprojectInfo['id']);
            $status_ok=is_null($info);
            
            if( !($status_ok=is_null($info)))
            {
                $msg = $msg_prefix . sprintf(TESTPLANNAME_ALREADY_EXISTS_STR,$name,$tprojectInfo['name']);
                $this->errors[] = new IXR_Error(TESTPLANNAME_ALREADY_EXISTS, $msg);
            }
        }

        if( $status_ok )
        {
            $keys2check = array(self::$activeParamName => 1,self::$publicParamName => 1,
                                self::$noteParamName => '');
  		    foreach($keys2check as $key => $value)
  		    {
  		        $optional[$key]=$this->_isParamPresent($key) ? trim($this->args[$key]) : $value;
  		    }
            $retval = $this->tplanMgr->create(htmlspecialchars($name),
                                              htmlspecialchars($optional[self::$noteParamName]),
                                              $tprojectInfo['id'],$optional[self::$activeParamName],
                                              $optional[self::$publicParamName]);

		    $resultInfo = array();
		    $resultInfo[]= array("operation" => __FUNCTION__,"additionalInfo" => null,
			                     "status" => true, "id" => $retval, "message" => GENERAL_SUCCESS_STR);
        }

        return $status_ok ? $resultInfo : $this->errors;
	} // public function createTestPlan


	/**
	 * Gets full path from the given node till the top using nodes_hierarchy_table
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param mixed $args["nodeID"] node id      
	 * @return mixed $resultInfo			
	 * @access public
	 */		
	public function getFullPath($args)
	{
	  	$this->_setArgs($args);
	  	$operation=__FUNCTION__;
	    $msg_prefix="({$operation}) - ";
	    $checkFunctions = array('authenticate');
	    $status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && 
	               $this->_isParamPresent(self::$nodeIDParamName,$msg_prefix,self::SET_ERROR) ;
	  
	    if( $status_ok )
	    {
	        $nodeID=$this->args[self::$nodeIDParamName];
	    	if( !is_int($nodeID) || $nodeID <= 0 )
	    	{
	            $msg = $msg_prefix . sprintf(NODEID_IS_NOT_INTEGER_STR);
	            $this->errors[] = new IXR_Error(NODEID_IS_NOT_INTEGER, $msg);
	            $status_ok=false;
	        } 
	    }
	    
	    if( $status_ok )
	    {
	        $full_path = $this->tprojectMgr->tree_manager->get_full_path_verbose($nodeID);
	        if(is_null($full_path))
	        {
	            $msg = $msg_prefix . sprintf(NODEID_DOESNOT_EXIST_STR,$nodeID);
	            $this->errors[] = new IXR_Error(NODEID_DOESNOT_EXIST, $msg);
	            $status_ok=false;
	        }
		}
	    return $status_ok ? $full_path : $this->errors;
	}

    /**
 	 * 
     *
     */
	private function _insertCustomFieldExecValues($executionID)
	{
		// // Check for existence of executionID   
		$status_ok=true;
		$sql="SELECT id FROM {$this->tables['executions']} WHERE id={$executionID}";
		$rs=$this->dbObj->fetchRowsIntoMap($sql,'id');
		// 
        $cfieldSet=$this->args[self::$customFieldsParamName];
        $tprojectID=$this->tcaseMgr->get_testproject($this->args[self::$testCaseIDParamName]);
        $tplanID=$this->args[self::$testPlanIDParamName];
        $cfieldMgr=$this->tprojectMgr->cfield_mgr;        
        $cfieldsMap = $cfieldMgr->get_linked_cfields_at_execution($tprojectID, 1,'testcase',
                                                                  null,null,null,'name');
        // 
        // // 
		// // id, name, label, type,
		// // possible_values, default_value
		// // valid_regexp,
		// // length_min, length_max,
		// // show_on_design, enable_on_design,
		// // show_on_execution, enable_on_execution,
		// // show_on_testplan_design, enable_on_testplan_design
		// // display_order, location 	1
		// // value 	asasa
		// // node_id        
        // 
        // // $cfield[$key]=array("type_id"  => $value['type'],
        // //                    "cf_value" => '');
        // 
        // 
        // // $this->tcVersionID;
        // // new dBug($cfieldsMap);
        $status_ok = !(is_null($rs) || is_null($cfieldSet) || count($cfieldSet) == 0);		
        $cfield4write = null;
        if( $status_ok && !is_null($cfieldsMap) )
        {
        	foreach($cfieldSet as $name => $value)
        	{
             	if( isset($cfieldsMap[$name]) )
             	{
         	    	$cfield4write[$cfieldsMap[$name]['id']] = array("type_id"  => $cfieldsMap[$name]['type'],
                                                              "cf_value" => $value);
		       	}
             }	
             if( !is_null($cfield4write) )
             {
             	$cfieldMgr->execution_values_to_db($cfield4write,$this->tcVersionID,$executionID,$tplanID,
                                                    null,'write-through');
             }
        }        
		return $status_ok;
	}



	 /**
	 * delete an execution
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["executionid"]
	 *
	 * @return mixed $resultInfo 
	 * 				[status]	=> true/false of success
	 * 				[id]		  => result id or error code
	 * 				[message]	=> optional message for error message string
	 * @access public
	 */	
	 public function deleteExecution($args)
	 {		
		$resultInfo = array();
        $operation=__FUNCTION__;
	    $msg_prefix="({$operation}) - ";
		$execCfg = config_get('exec_cfg');

		$this->_setArgs($args);              
		$resultInfo[0]["status"] = false;
		
        $checkFunctions = array('authenticate','checkExecutionID');       
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       
	
	    // Important userHasRight sets error object
	    //
        $status_ok = ($status_ok && $this->userHasRight("testplan_execute"));	
		if($status_ok)
		{			
			if( $execCfg->can_delete_execution )  
			{
				$this->tcaseMgr->deleteExecution($args[self::$executionIDParamName]);			
    	    	$resultInfo[0]["status"] = true;
				$resultInfo[0]["id"] = $args[self::$executionIDParamName];	
				$resultInfo[0]["message"] = GENERAL_SUCCESS_STR;
				$resultInfo[0]["operation"] = $operation;
			}
			else
			{
				$status_ok = false;
    		    $this->errors[] = new IXR_Error(CFG_DELETE_EXEC_DISABLED, 
    		                                    CFG_DELETE_EXEC_DISABLED_STR);
			}
		}

		return $status_ok ? $resultInfo : $this->errors;
	}

	/**
	 * Helper method to see if an execution id exists on DB
	 * no checks regarding other data like test case , test plam, build, etc are done
	 * 
	 * 
	 * 	
	 * @return boolean
	 * @access private
	 */        
    protected function checkExecutionID()
    {
        // need to be implemented - franciscom
        $status=true;
    	return $status;
    }



	/**
	 * Helper method to see if the platform identity provided is valid 
	 * This is the only method that should be called directly to check platform identity
	 * 	
	 * If everything OK, platform id is setted.
	 *
	 * @return boolean
	 * @access private
	 */    
    protected function checkPlatformIdentity($tplanID,$platformInfo=null,$messagePrefix='')
    {
        $status=true;
        $platformID=0;
        $myErrors=array();

        $name_exists = $this->_isParamPresent(self::$platformNameParamName,$messagePrefix);
        $id_exists = $this->_isParamPresent(self::$platformIDParamName,$messagePrefix);
        $status = $name_exists | $id_exists;
        // for debug - file_put_contents('c:\checkPlatformIdentity.txt', $status ? 1:0);                            

        if(!$status)
        {
        	$pname = self::$platformNameParamName . ' OR ' . self::$platformIDParamName; 
	        $msg = $messagePrefix . sprintf(MISSING_REQUIRED_PARAMETER_STR, $pname);
	        $this->errors[] = new IXR_Error(MISSING_REQUIRED_PARAMETER, $msg);				      
		}        
        
        if($status)
        {
      		// get test plan name is useful for error messages
       		$tplanInfo = $this->tplanMgr->get_by_id($tplanID);
       		if(is_null($platformInfo))
       		{
       			$platformInfo = $this->tplanMgr->getPlatforms($tplanID,array('outputFormat' => 'map'));  
       		}

            if(is_null($platformInfo))
            {
        		$status = false;
   				$msg = sprintf($messagePrefix . TESTPLAN_HAS_NO_PLATFORMS_STR,$tplanInfo['name']);
   				$this->errors[] = new IXR_Error(TESTPLAN_HAS_NO_PLATFORMS, $msg);
            }
            
        }
         
        if( $status )
        {
        	if($name_exists)
        	{ 
        		// file_put_contents('c:\checkPlatformIdentity.txt', $this->args[self::$platformNameParamName]);                            
        		// file_put_contents('c:\checkPlatformIdentity.txt', serialize($platformInfo));                            
        		// $this->errors[]=$platformInfo;
        		$status = in_array($this->args[self::$platformNameParamName],$platformInfo);
            }
            else
            {
            	$status = isset($platformInfo[$this->args[self::$platformIDParamName]]);
            }
            
        	if( !$status )
        	{
        		// Platform does not exist in target testplan
   			    $msg = sprintf($messagePrefix . PLATFORM_NOT_LINKED_TO_TESTPLAN_STR,
                               $this->args[self::$platformNameParamName],$tplanInfo['name']);
   				$this->errors[] = new IXR_Error(PLATFORM_NOT_LINKED_TO_TESTPLAN, $msg);
        	}	
        }
        
        if($status)
        {
        	if($name_exists)
        	{ 
 	       		$dummy = array_flip($platformInfo);
        		$this->args[self::$platformIDParamName] = $dummy[$this->args[self::$platformNameParamName]];
        	}
        }
	    return $status;
    }   



	private function _updateResult()
	{
		
		$platform_id = 0;
		$exec_id = 0;
		$build_id = $this->args[self::$buildIDParamName];
		$tester_id =  $this->userID;
		$status = $this->args[self::$statusParamName];
		$testplan_id =	$this->args[self::$testPlanIDParamName];
		$tcversion_id =	$this->tcVersionID;
    	$tcase_id = $this->args[self::$testCaseIDParamName];
    	$db_now=$this->dbObj->db_now();
    
    	if( isset($this->args[self::$platformIDParamName]) )
		{
			$platform_id = $this->args[self::$platformIDParamName]; 	
		}

		$last_exec = $this->tcaseMgr->get_last_execution($tcase_id,testcase::ALL_VERSIONS,
		                                                 $testplan_id,$build_id,$platform_id);
    	
    	if( !is_null($last_exec) )
    	{
    		$last_exec = current($last_exec);
			$execution_type = constant("TESTCASE_EXECUTION_TYPE_AUTO");
            $exec_id = $last_exec['execution_id'];
			$notes = '';
    		$notes_update = '';
			
			if($this->_isNotePresent())
			{
				$notes = $this->dbObj->prepare_string($this->args[self::$noteParamName]);
			}
			
			if(trim($notes) != "")
			{
			    $notes_update = ",notes='{$notes}'";  
			}
    		
			$sql = " UPDATE {$this->tables['executions']} " .
			       " SET tester_id={$tester_id}, execution_ts={$db_now}," . 
			       " status='{$status}', execution_type= {$execution_type} " . 
			       " {$notes_update}  WHERE id = {$exec_id}";
			
            $this->dbObj->exec_query($sql);
    	}
		return $exec_id;
	}	

   /**
     * Return a TestSuite by ID
     *
     * @param
     * @param struct $args
     * @param string $args["devKey"]
     * @param int $args["testsuiteid"]
     * @return mixed $resultInfo
     * 
     * @access public
     */
    public function getTestSuiteByID($args)
    { 
        $operation=__FUNCTION__;
        $msg_prefix="({$operation}) - ";

        $this->_setArgs($args);
        $status_ok=$this->_runChecks(array('authenticate','checkTestSuiteID'),$msg_prefix);

        $details='simple';
        $key2search=self::$detailsParamName;
        if( $this->_isParamPresent($key2search) )
        { 
            $details=$this->args[$key2search];
        }

        if($status_ok && $this->userHasRight("mgt_view_tc"))
        { 
            $testSuiteID = $this->args[self::$testSuiteIDParamName];
            $tsuiteMgr = new testsuite($this->dbObj);
            return $tsuiteMgr->get_by_id($testSuiteID);

        }
        else
        { 
            return $this->errors;
        }
    }

} // class end

/**
 * Where the Server object is initialized
 * 
 * @see __construct()
 */
$XMLRPCServer = new TestlinkXMLRPCServer();
?>
