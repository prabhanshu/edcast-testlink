<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 *  
 * @filesource $RCSfile: print.inc.php,v $
 * @version $Revision: 1.35 $
 * @modified $Date: 2007/12/20 20:36:35 $ by $Author: schlundus $
 *
 * @author	Martin Havlat <havlat@users.sourceforge.net>
 * 
 * Functions for support printing of documents. 
 *
 * rev :
 *      20071014 - franciscom - renderTestCaseForPrinting() added printing of test case version
 *      20070509 - franciscom - changes in renderTestSpecTreeForPrinting() interface
 */

require_once("requirement_mgr.class.php");


/** 
 * print HTML header 
 * Standard: HTML 4.01 trans (because is more flexible to bugs in user data)
 */
function printHeader($title, $base_href, $cssTemplate = TL_DOC_BASIC_CSS)
{
	$output = "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>\n";
	$output .= "<html>\n<head>\n";
	$output .= '<meta http-equiv="Content-Type" content="text/html; charset='.TL_TPL_CHARSET.'" />';
	$output .= '<title>' . htmlspecialchars($title). "</title>\n";
	$output .= '<link type="text/css" rel="stylesheet" href="' . $base_href . $cssTemplate . '" />';
	$output .= '<style type="text/css" media="print">.notprintable { display:none;}</style>';
	$output .= "\n</head>\n<body>\n";

	return $output;
}

/** 
  print HTML - initial page of document 
*/
function printFirstPage(&$db,$item_type,$title, $tproject_info, $userID,$tplan_info=null)
{
	$g_date_format = config_get('date_format');
	$tproject_name = htmlspecialchars($tproject_info['name']);
	$tproject_notes = $tproject_info['notes'];
	
	$author = null;
	$user = tlUser::getById($db,$userID);
	if ($user)
		$author = htmlspecialchars($user->getDisplayName());
	$title = htmlspecialchars($title);
	
	$output = '<div>';
	$output .= '<div class="groupBtn" style="text-align:right">' .
	           '<input class="notprintable" type="button" name="print" value="' . 
	           lang_get('btn_print').'" onclick="javascript: print();" style="margin-left:2px;" /></div>';

	$output .= '<div class="pageheader">'. lang_get('testproject') . ' ' . $tproject_name ."</div>\n";
	
	if (TL_DOC_COMPANY != '' ||  TL_DOC_COMPANY_LOGO != '' )
	{
		$output .= '<br /><center><table class="company">';

	  	if (TL_DOC_COMPANY != '' )
	  	{
			$output .= '<tr><td id="company_name">'. htmlspecialchars(TL_DOC_COMPANY) ."</td></tr>";
		}
		$output .= '<tr><td/></tr>'; 
		  
	  	if (TL_DOC_COMPANY_LOGO != '' )
		{
			$output .= '<tr><td id="company_logo">'. 
		    		str_replace('%BASE_HREF%',$_SESSION['basehref'],TL_DOC_COMPANY_LOGO) ."</td></tr>";
		}
		$output .= "</table></center>\n";
	}
	
	$output .= "</div>\n";

	$my_title ='';
	$output .= '<h1 id="doctitle">';


  $my_title = lang_get('testproject') . ' ' . $tproject_name;
  if( $title != '' )
  {
    // $my_title = lang_get('testsuite') . ' ' . $title;
    $my_title = lang_get($item_type) . ' ' . $title;
 	}


	if( is_null($tplan_info) )
	{
	  $output .= lang_get('title_test_spec');
	}
	else
	{
	  $output .= '<h1 id="doctitle">' . lang_get('testplan') . htmlspecialchars($tplan_info['name']);
	}
	$output .= "<br>" . $my_title . "</h1>\n";  
 
	
	$output .= '<div id="summary">' .
		         '<p id="prodname">'. lang_get('testproject') .": " . $tproject_name . "</p>\n";
	if (strlen($tproject_notes))
		$output .= '<p id="prodnotes">'. $tproject_notes . "</p>\n";
		       
	$output .= '<p id="author">' . lang_get('author').": " . $author . "</p>\n" .
		         '<p id="printedby">' . lang_get('printed_by_TestLink_on')." ". 
		         strftime($g_date_format, time()) . "</p></div>\n";

	if (TL_DOC_COPYRIGHT != '')
		$output .= '<div class="pagefooter" id="copyright">' . htmlspecialchars(TL_DOC_COPYRIGHT)."</div>\n";
	if (TL_DOC_CONFIDENT != '')
		$output .= '<div class="pagefooter" id="confidential">' . htmlspecialchars(TL_DOC_CONFIDENT)."</div>\n";

	return $output;
}


/*
  function: renderTestSpecTreeForPrinting

  args :
  
        [$tplan_id]
        
  returns: 
  
  rev :
       20070509 - franciscom - added $tplan_id in order to refactor and
                               add contribution BUGID 

*/
function renderTestSpecTreeForPrinting(&$db,&$node,$item_type,&$printingOptions,
                                       $tocPrefix,$tcCnt,$level,$user_id,$tplan_id=0)
{
  $tree_mgr = new tree($db);
  $map_id_descr = array_flip($tree_mgr->get_available_node_types());
  
	$code = null;
	$bCloseTOC = 0;	
	if (isset($node['node_type_id']))
	{
	  $verbose_node_type = $map_id_descr[$node['node_type_id']];
		switch($verbose_node_type)
		{
			case 'testproject':
				$code .= renderProjectNodeForPrinting($db,$node,$printingOptions,$item_type,
				                                      $printingOptions['title'],$user_id,$tplan_id);
				break;
					
			case 'testsuite':
				if (!is_null($tocPrefix))
					$tocPrefix .= ".";
				$tocPrefix .= $tcCnt;
				$code .= renderTestSuiteNodeForPrinting($db,$node,$printingOptions,$tocPrefix,$level);
				break;
			
			case 'testcase':
				$code .= renderTestCaseForPrinting($db,$node,$printingOptions,$level,$tplan_id);
				break;
		}
	}
	if (isset($node['childNodes']) && $node['childNodes'])
	{
		$childNodes = $node['childNodes'];
		$tsCnt = 0;
    $children_qty=sizeof($childNodes);
		for($i = 0;$i <$children_qty ;$i++)
		{
			$current = $childNodes[$i];
			if(is_null($current))
				continue;
			
			if (isset($current['node_type_id']) && $map_id_descr[$current['node_type_id']] == 'testsuite')
				$tsCnt++;
			$code .= renderTestSpecTreeForPrinting($db,$current,$item_type,$printingOptions,
			                                       $tocPrefix,$tsCnt,$level+1,$user_id);
		}
	}
	if (isset($node['node_type_id']) && $map_id_descr[$node['node_type_id']] == 'testproject')
	{
		if ($printingOptions['toc'])
		{
			$printingOptions['tocCode'] .= '</div><hr />';	
			$code = str_replace("{{INSERT_TOC}}",$printingOptions['tocCode'],$code);
		}
		$code .= "</body></html>";
	}
		
	return $code;
}

/*
  function: renderTestCaseForPrinting 

  args :
  
  returns: 

  rev :
       20071014 - franciscom - display test case version
       20070509 - franciscom - added Contribution
       
*/
function renderTestCaseForPrinting(&$db,&$node,&$printingOptions,$level,$tplan_id=0) 
{
 	$id = $node['id'];
	$name = htmlspecialchars($node['name']);

	$code = null;
  $tc_mgr = null;
  $tcInfo = null;
  $tcResultInfo = null;
  
  $versionID = isset($node['tcversion_id']) ? $node['tcversion_id'] : TC_LATEST_VERSION; 
		
	if( $printingOptions['body'] || $printingOptions['summary'] || 
	    $printingOptions['author'] || $printingOptions['keyword'])
	{
		$tc_mgr = new testcase($db);
    	$tcInfo = $tc_mgr->get_by_id($id,$versionID);
		if ($tcInfo)
			$tcInfo=$tcInfo[0];
	}
	if($printingOptions['passfail'])
	{
		$resultTC['tcid'] = $versionID;		
		$tcResultInfo = createTestInput($db,$resultTC,$build_id, $tplan_id);
	}
	
	if ($printingOptions['toc']) 
	{
	  $printingOptions['tocCode']  .= '<p style="padding-left: '.(15*$level).'px;"><a href="#tc' . $id . '">' . 
	   	                 $name . '</a></p>';
		$code .= "<a name='tc" . $id . "'></a>";
	}
 	$code .= '<div class="tc"><table class="tc" width="90%">';
 	$code .= '<tr><th colspan="2">' . lang_get('test_case') . " " . $id . ": " . $name  . "</th></tr>";
	

	// To manage print of test specification
	if( isset($node['version']) )
	{
	  $code .= '<tr><th colspan="2">' . lang_get('version') . ' ' . $node['version'] . "</th></tr>";
	}

  	if ($printingOptions['author'])
  	{    
		$authorName = null;
		$user = tlUser::getByID($db,$tcInfo['author_id']);
		if ($user)
			$authorName = $user->getDisplayName();
     	$code .= '<tr><td colspan="2"><b>' . lang_get("author") . " </b>" . $authorName . "</td></tr>";
  	}

	if ($printingOptions['passfail'])
	{
		$code .= '<tr><td width="20%" valign="top"><b><u>'.lang_get('Result').": ".$tcResultInfo['status']."</u></b></td></tr>" .
				'<tr><td width="20%" valign="top"><u>'.lang_get('testnotes')."</u><br /></td><td>".$tcResultInfo['note']."</td></tr>";
	}
	
  	if (($printingOptions['body'] || $printingOptions['summary'])) // && (!empty(trim(strip_tags($tcInfo['summary'])))))
	{
		$code .= "<tr><td colspan=\"2\"><u>".lang_get('summary')."</u>: " .  $tcInfo['summary'] . "</td></tr>";
	}

  	if (($printingOptions['body'])) // && (!empty(trim(strip_tags($tcInfo['steps'])))))
	{
	   	$code .= "<tr><td colspan=\"2\"><u>".lang_get('steps')."</u>:<br />" .  $tcInfo['steps'] . "</td></tr>";
	   	$code .= "<tr><td colspan=\"2\"><u>".lang_get('expected_results')."</u>:<br />" .  $tcInfo['expected_results'] . "</td></tr>";
	}

	// collect REQ for TC
	// MHT: based on contribution by JMU (1045) 
	if ($printingOptions['requirement'])
	{
		
		$req_mgr = new requirement_mgr($db);
		$arrReqs = $req_mgr->get_all_for_tcase($id);

		$code .= '<tr><td width="20%" valign="top"><b><u>'.lang_get('reqs').'</u></b><td>';
		if (sizeof($arrReqs))
		{
			foreach ($arrReqs as $req) 
			{
				$code .=  $req['id'] . ":  " . $req['title'] . "<br />";
			}
		}
		else
		{
			$code .= lang_get('none');
		}
		$code .= "</td></tr>";
	}
	// collect keywords for TC
	// MHT: based on contribution by JMU (1045) 
	if ($printingOptions['keyword'])
	{
		$code .= '<tr><td width="20%" valign="top"><b><u>'.lang_get('keywords').'</u></b><td>';

		$arrKeywords = $tc_mgr->getKeywords($id,null);
		if (sizeof($arrKeywords))
		{
			foreach ($arrKeywords as $kw) 
			{
				$code .= $kw['keyword'] . "<br />";
			}
		}
		else
		{
			$code .= lang_get('none');
		}
		$code .= "</td></tr>";
	}

	$code .= "</table></div>";

  if( !is_null($tc_mgr) )
	{
	  unset($tc_mgr);
	}
	
	
	return $code;
}

/*
  function: 

  args :
  
  returns: 

*/
function renderProjectNodeForPrinting(&$db,&$node,&$printingOptions,$item_type,
                                      $title,$user_id,$tplan_id=0)
{

	$tproject = new testproject($db);
	$tproject_info = $tproject->get_by_id($node['id']);
	$tplan_info = null;

	if( $tplan_id != 0)
	{
	  $tplan_mgr = new testplan($db);
	  $tplan_info = $tplan_mgr->get_by_id($tplan_id);  
	}
	
	
	$code = printHeader($title,$_SESSION['basehref']);
	$code .= printFirstPage($db, $item_type, $title, $tproject_info,$user_id,$tplan_info);

	$printingOptions['toc_numbers'][1] = 0;
	if ($printingOptions['toc'])
	{
		$printingOptions['tocCode'] = '<div class="toc"><h2>'.lang_get('title_toc').'</h2>';
		$code .= "{{INSERT_TOC}}";
	}
	
	return $code;
}


/*
  function: 

  args :
  
  returns: 

*/
function renderTestSuiteNodeForPrinting(&$db,&$node,&$printingOptions,$tocPrefix,$level) 
{
	$code = null;
	$name = isset($node['name']) ? htmlspecialchars($node['name']) : '';
	if ($printingOptions['toc']) 
	{
	 	$printingOptions['tocCode'] .= '<p style="padding-left: '.(10*$level).'px;"><a href="#cat' . $node['id'] . '">' . 
	 	                 $name . '</a></p>';
		$code .= "<a name='cat{$node['id']}'></a>";
	}
 	$code .= "<h1>{$tocPrefix} ". lang_get('test_suite') ." {$name}</h1>";
						 
	if ($printingOptions['header']) 
  	{
		$tsuite = new testsuite($db);
		$tInfo = $tsuite->get_by_id($node['id']);
		unset($tsuite);
    $code .= "<h2>{$tocPrefix}.0 " . lang_get('details') . "</h2><div>{$tInfo['details']}</div><br />";
 	}
	
	return $code;
}



/*
  function: 

  args :
  
  returns: 

*/
function renderTestPlanForPrinting(&$db,&$node,$item_type,&$printingOptions,
                                       $tocPrefix,$tcCnt,$level,$user_id,$tplan_id)

{
  $code =  renderTestSpecTreeForPrinting($db,$node,$item_type,$printingOptions,
                                         $tocPrefix,$tcCnt,$level,$user_id,$tplan_id);
  
  return $code;
}
?>
