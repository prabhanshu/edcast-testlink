<?php
 /**
 * A sample client implementation in php
 * 
 * @author 		Asiel Brumfield <asielb@users.sourceforge.net>
 * @package 	TestlinkAPI
 * @link      http://testlink.org/api/
 *
 * rev: 20080305 - franciscom - refactored
 */
 
 /** 
  * Need the IXR class for client
  */
require_once dirname(__FILE__) . '/../../../../third_party/xml-rpc/class-IXR.php';

// substitute your server URL Here
define("SERVER_URL", "http://localhost/w3/tl/tl18/head_20080303/lib/api/xmlrpc.php");

// substitute your Dev Key Here
define("DEV_KEY", "f2a979d533cdd9761434bba60a88e4d8");

$tcaseStatusCode['passed']='p';
$tcaseStatusCode['blocked']='b';
$tcaseStatusCode['failed']='f';

// Substitute for tcid and tpid that apply to your project
$testPlanID=95;
$testCaseID=86;
$buildID=5;

$response = reportResult($testCaseID,$testPlanID,$buildID,$tcaseStatusCode['passed']);

echo "result was: ";
// Typically you'd want to validate the result here and probably do something more useful with it
print_r($response);


/*
  function: 

  args:
  
  returns: 

*/
function reportResult($tcaseid, $tplanid, $buildid, $status)
{

	$client = new IXR_Client(SERVER_URL);
 
	$data = array();
	$data["devKey"] = constant("DEV_KEY");
	$data["tcid"] = $tcaseid;
	$data["tpid"] = $tplanid;
	$data["buildid"] = $buildid;
	$data["status"] = $status;

	if(!$client->query('tl.reportTCResult', $data))
	{
		echo "something went wrong - " . $client->getErrorCode() . " - " . $client->getErrorMessage();			
	}
	else
	{
		return $client->getResponse();
	}
}
?>