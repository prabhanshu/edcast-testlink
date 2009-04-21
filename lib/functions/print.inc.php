<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * @filesource $RCSfile: print.inc.php,v $
 * @version $Revision: 1.76 $
 * @modified $Date: 2009/04/21 04:54:11 $ by $Author: amkhullar $
 *
 * @author	Martin Havlat <havlat@users.sourceforge.net>
 *
 * Library for documents generation
 * Used by printDocument.php
 *
 * Revisions:
 *
 *		20090410 - amkhullar - BUGID 2368
 *      20090330 - franciscom - renderTestSpecTreeForPrinting() - added logic to print ALWAYS test plan custom fields
 *      20090329 - franciscom - renderTestCaseForPrinting() refactoring of code regarding custom fields
 *                              renderTestSuiteNodeForPrinting() - print ALWAYS custom fields
 * 		20090326 - amkhullar - BUGID 2207 - Code to Display linked bugs to a TC in Test Report
 *		20090322 - amkhullar - added check box for Test Case Custom Field display on Test Plan/Report
 *  	20090223 - havlatm - estimated execution moved to extra chapter, refactoring a few functions
 * 		20090129 - havlatm - removed base tag from header (problems with internal links for some browsers)
 *  	20081207 - franciscom - BUGID 1910 - changes on display of estimated execution time
 *                             added code to display CF with scope='execution'
 * 
 *  	20080820 - franciscom - added contribution (BUGID 1670)
 *                             Test Plan report:
 *                             Total Estimated execution time will be printed
 *                             on table of contents. 
 *                             Compute of this time can be done if: 
 *                             - Custom Field with Name CF_ESTIMATED_EXEC_TIME exists
 *                             - Custom Field is managed at design time
 *                             - Custom Field is assigned to Test Cases
 *                             
 *                             Important Note:
 *                             Lots of controls must be developed to avoid problems 
 *                             presenting with results, when user use time with decimal part.
 *                             Example:
 *                             14.6 minuts what does means? 
 *                             a) 14 min and 6 seconds?  
 *                             b) 14 min and 6% of 1 minute => 14 min 3.6 seconds ?
 *
 *                             Implementation at (20080820) is very simple => is user
 *                             responsibility to use good times (may be always interger values)
 *                             to avoid problems.
 *                             Another choice: TL must round individual times before doing sum.
 *
 *	20080819 - franciscom - renderTestCaseForPrinting() - removed mysql only code
 *	20080602 - franciscom - display testcase external id
 *	20080525 - havlatm - fixed missing test result
 *	20080505 - franciscom - renderTestCaseForPrinting() - added custom fields
 *	20080418 - franciscom - document_generation configuration .
 *                             removed tlCfg global coupling
 *	20071014 - franciscom - renderTestCaseForPrinting() added printing of test case version
 *	20070509 - franciscom - changes in renderTestSpecTreeForPrinting() interface
 *
 * ----------------------------------------------------------------------------------- */

require_once("exec.inc.php");

/**
 * render HTML header
 * Standard: HTML 4.01 trans (because is more flexible to bugs in user data)
 * @param string $title
 * @param string $base_href Base URL
 * @return string html data
 */
function renderHTMLHeader($title,$base_href)
{
	$docCfg = config_get('document_generator');
    $docCfg->css_template;
  
	$output = "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>\n";
	$output .= "<html>\n<head>\n";
	$output .= '<meta http-equiv="Content-Type" content="text/html; charset=' . config_get('charset') . '" />';
	$output .= '<title>' . htmlspecialchars($title). "</title>\n";
	$output .= '<link type="text/css" rel="stylesheet" href="'. $base_href . $docCfg->css_template ."\" />\n";
	$output .= '<style type="text/css" media="print">.notprintable { display:none;}</style>';
	$output .= "\n</head>\n";

	return $output;
}


/**
 * Generate initial page of document
 * @param singleton $doc_info data with the next string values: 
 *                  title
 *                  type_name: what does this means ???
 *                  author, tproject_name, testplan_name  
 * @return string html
 * @author havlatm
 */
function renderFirstPage($doc_info)
{
    $docCfg = config_get('document_generator');
    $date_format_cfg = config_get('date_format');
  	$output = "<body>\n<div>\n";

	// Print header
	if ($docCfg->company_name != '' )
	{
		$output .= '<div style="float:right;">' . htmlspecialchars($docCfg->company_name) ."</div>\n";
	}
	$output .= '<div>'. $doc_info->tproject_name . "</div><hr />\n";
    
	if ($docCfg->company_logo != '' )
	{
		$output .= '<p style="text-align: center;"><img alt="TestLink logo" ' .
		           'title="configure using $tlCfg->company->logo_image"'.
        	     ' src="' . $_SESSION['basehref'] . TL_THEME_IMG_DIR . $docCfg->company_logo . '" /></p>';
	}
	$output .= "</div>\n";
	$output .= '<div class="doc_title"><p>' . $doc_info->title . '</p>';
	$output .= '<p>'.$doc_info->type_name.'</p>';
	$output .= "</div>\n";
    
	// Print summary on the first page
	$output .= '<div class="summary">' .
		         '<p id="prodname">'. lang_get('project') .": " . $doc_info->tproject_name . "</p>\n";
    
	$output .= '<p id="author">' . lang_get('author').": " . $doc_info->author . "</p>\n" .
		         '<p id="printedby">' . lang_get('printed_by_TestLink_on')." ".
		         strftime($date_format_cfg, time()) . "</p></div>\n";
    
	// Print legal notes
	if ($docCfg->company_copyright != '')
	{
		$output .= '<div class="pagefooter" id="copyright">' . $docCfg->company_copyright."</div>\n";
	}
		           
	if ($docCfg->confidential_msg != '')
	{
		$output .= '<div class="pagefooter" id="confidential">' .	$docCfg->confidential_msg . "</div>\n";
	}
	
	return $output;
}


/**
 * Generate a chapter to a document
 * @param string $title
 * @param string $content
 * @return string html
 * @author havlatm
 */
function renderSimpleChapter($title, $content)
{
	$output = '';
	if (strlen($content) > 0)
	{
		$output .= '<h1 class="doclevel">'.$title."</h1>\n";
		$output .= '<div class="txtlevel">' .$content . "</div>\n";
	}

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
                                       $tocPrefix,$tcCnt,$level,$user_id,
                                       $tplan_id = 0,$tcPrefix = null,
                                       $tprojectID = 0)
{
	static $tree_mgr;
	static $map_id_descr;
 	$code = null;

	if( !$tree_mgr )
	{ 
 	    $tplan_mgr = new testplan($db);
	    $tree_mgr = new tree($db);
 	    $map_id_descr = $tree_mgr->node_types;
 	}
 	$verbose_node_type = $map_id_descr[intval($node['node_type_id'])];

    switch($verbose_node_type)
	{
		case 'testproject':
		    if($tplan_id != 0 )
		    {
		        // 20090330 - franciscom
		        // we are printing a test plan, get it's custom fields
                $cfieldFormatting=array('table_css_style' => 'class="cf"');
                if ($printingOptions['cfields'])
        		{
	            	$cfields = $tplan_mgr->html_table_of_custom_field_values($tplan_id,'design',null,$cfieldFormatting);
	            	$code .= "<br /><hr><br /> $cfields <br /><hr><br />";
        		}
		    }
			$code .= renderToc($printingOptions);
		break;

		case 'testsuite':
            $tocPrefix .= (!is_null($tocPrefix) ? "." : '') . $tcCnt;
            $code .= renderTestSuiteNodeForPrinting($db,$node,$printingOptions,$tocPrefix,$level,$tplan_id,$tprojectID);
		break;

		case 'testcase':
			  $code .= renderTestCaseForPrinting($db,$node,$printingOptions,$level,$tplan_id,$tcPrefix,$tprojectID);
	    break;
	}
	
	if (isset($node['childNodes']) && $node['childNodes'])
	{
	  
		$childNodes = $node['childNodes'];
		$tsCnt = 0;
   	    $children_qty = sizeof($childNodes);
		for($i = 0;$i < $children_qty ;$i++)
		{
			$current = $childNodes[$i];
			if(is_null($current))
			{
				continue;
            }
            
			if (isset($current['node_type_id']) && $map_id_descr[$current['node_type_id']] == 'testsuite')
			{
			    $tsCnt++;
			}
			$code .= renderTestSpecTreeForPrinting($db,$current,$item_type,$printingOptions,
			                                       $tocPrefix,$tsCnt,$level+1,$user_id,$tplan_id,$tcPrefix,$tprojectID);
		}
	}
	
	if ($verbose_node_type == 'testproject')
	{
		if ($printingOptions['toc'])
		{
			$printingOptions['tocCode'] .= '</div><hr />';
			$code = str_replace("{{INSERT_TOC}}",$printingOptions['tocCode'],$code);
		}
	}

	return $code;
}


/*
  function: renderTestCaseForPrinting
  args :
  returns:

  rev :

*/
function renderTestCaseForPrinting(&$db,&$node,&$printingOptions,$level,
                                   $tplan_id=0,$prefix = null,$tprojectID = 0)
{
    static $req_mgr;
	static $tc_mgr;
	static $results_cfg;
	static $status_labels;
	static $labels;
	static $doc_cfg;
	static $gui_cfg;
	static $testcaseCfg;
	static $tcase_prefix;
    static $userMap = array();
    
	$code = null;
	$tcInfo = null;
    $tcResultInfo = null;
    $tcase_pieces=null;
    $cfieldFormatting=array('td_css_style' => '','add_table' => false);
    
	  if( !$results_cfg )
	  {
 	      $tc_mgr = new testcase($db);
	      $doc_cfg = config_get('document_generator');
	      $gui_cfg = config_get('gui');
	      $testcaseCfg = config_get('testcase_cfg');
	      
	      $results_cfg = config_get('results');
        foreach($results_cfg['code_status'] as $key => $value)
        {
            $status_labels[$key] = "check your \$tlCfg->results['status_label'] configuration ";
            if( isset($results_cfg['status_label'][$value]) )
            {
                $status_labels[$key] = lang_get($results_cfg['status_label'][$value]);
            }    
        }
        
        $labels=array("last_exec_result" => '', "testnotes" => '', "none" => '', "reqs" => '',
                      "author" => '', "summary" => '',"steps" => '', "expected_results" =>'',
                      "build" => '', "test_case" => '', "keywords" => '',"version" => '', 
                      "test_status_not_run" => '', "not_aplicable" => '', 'bugs' => '');
                                
        foreach($labels as $id => $value)
        {
            $labels[$id] = lang_get($id);
        }
    
	      if( !is_null($prefix) )
	      {
	          $tcase_prefix = $prefix;
	      }
	  }
	
    $versionID = isset($node['tcversion_id']) ? $node['tcversion_id'] : TC_LATEST_VERSION;
    $id = $node['id'];
    $tcInfo = $tc_mgr->get_by_id($id,$versionID);
    if ($tcInfo)
    {
    	$tcInfo = $tcInfo[0];  
    }
    if( !$tcase_prefix )
    {
    	$tcase_prefix = $tc_mgr->getPrefix($id);
    }
	  $external_id = $tcase_prefix . $testcaseCfg->glue_character . $tcInfo['tc_external_id'];
	  $name = htmlspecialchars($node['name']);

  	$cfields = array('specScope' => '', 'execScope' => '');

	  // get custom fields that has specification scope
  	if ($printingOptions['cfields'])
    	{
		$cfields['specScope'] = $tc_mgr->html_table_of_custom_field_values($id,'design',null,null,$tplan_id,$tprojectID,$cfieldFormatting);
    	}

    // THIS IS NOT THE WAY TO DO THIS IS ABSOLUTELY WRONG AND MUST BE REFACTORED, 
    // using existent methods - franciscom - 20090329 
    // Need to get CF with execution scope
	  $sql =  " SELECT E.id AS execution_id, E.status, E.execution_ts, " .
	          " E.notes, E.build_id, E.tcversion_id,E.tcversion_number,E.testplan_id," .
	          " B.name AS build_name " .
	          " FROM executions E, builds B" .
	          " WHERE E.build_id= B.id " . 
	          " AND E.tcversion_id = {$versionID} " .
	          " AND E.testplan_id = {$tplan_id} " .
	  		    " ORDER BY execution_id DESC";
	  $exec_info = $db->get_recordset($sql,null,1);

    if(!is_null($exec_info ))
    { 
        $execution_id=$exec_info[0]['execution_id'];
        // Added condition for the display on/off of the custom fields on test cases.
        if ($printingOptions['cfields'])
        {
        	$cfields['execScope'] = $tc_mgr->html_table_of_custom_field_values($versionID,'execution',null,
        	                                                                   $execution_id, $tplan_id,
        	                                                                   $tprojectID,$cfieldFormatting);
        }
    }
	  
	if ($printingOptions['toc'])
	{
	      $printingOptions['tocCode']  .= '<p style="padding-left: '.(15*$level).'px;"><a href="#tc' . $id . '">' .
	       	                              $name . '</a></p>';
	    	$code .= "<a name=\"tc{$id}\"></a>";
	}
      
 	  $code .= '<div><table class="tc" width="90%">';
 	  $code .= '<tr><th colspan="2">' . $labels['test_case'] . " " . htmlspecialchars($external_id) . ": " . $name;
    
	  // add test case version
	  if($doc_cfg->tc_version_enabled && isset($node['version']) ) 
	  {
	  	$code .= '&nbsp;<span style="font-size: 80%;"' . $gui_cfg->role_separator_open . $labels['version'] . 
	  	         $gui_cfg->title_separator_1 .  $node['version'] . $gui_cfg->role_separator_close . '</span>';
	  }
 	  $code .= '</th></tr>';

  	if ($printingOptions['author'])
  	{
  		$authorName = null;
		$arrAuthorID['author_id'] = $tcInfo['author_id'];
		$arrAuthorID['updater_id'] = $tcInfo['updater_id'];
	      
		/** @TODO array $userMap[] should be filled at once for all names at the begin process */
	    foreach ($arrAuthorID as $key)
	    {
	    	if(isset($userMap[$key]))
	    	{
	    		$authorName[] = $userMap[$key];
		    }
		    else
	    	{
	        	$user = tlUser::getByID($db,$tcInfo['author_id']);
		        if ($user)
		        {
	    	      	$authorName[] = $user->getDisplayName();
	        	  	$userMap[$key] = htmlspecialchars($authorName);
		        }
		    }
	    }
	      
	    $code .= '<tr><td width="20%" valign="top"><span class="label">' . $labels['author'] 
	    		. ':</span> ' . $authorName[0]; 
		if ($tcInfo['updater_id'] > 0) // add updater if available
			$code .= ' <br /><span class="label">' . $labels['last_edit'] . ':</span> '.$authorName[1];
		$code .= ' </td></tr>';
  	}

    if (($printingOptions['body'] || $printingOptions['summary']))
    {
        $tcase_pieces=array('summary');
    }
    
    if (($printingOptions['body']))
    {
        $tcase_pieces[]='steps';
        $tcase_pieces[]='expected_results';        
    }
    
    if( !is_null($tcase_pieces) )
    {
        foreach( $tcase_pieces as $key )
        {
        	// disable the field if it's empty
        	if ($tcInfo[$key] != '')
            	$code .= '<tr><td colspan="2"><span class="label">' . $labels[$key] .
                     ':</span><br />' .  $tcInfo[$key] . "</td></tr>";
        }
    }
    $code .= $cfields['specScope'] . $cfields['execScope'];
	
	// generate test results
	if ($printingOptions['passfail'])
	{
		$build_name='';
	    $bugString = '';
	    if ($exec_info) 
		{
	  		$tcstatus2 = $status_labels[$exec_info[0]['status']];
	  		$tcnotes2 = $exec_info[0]['notes'];
            $build_name = " - ({$labels['build']}:{$exec_info[0]['build_name']})";
            	
            //- amitkhullar-BUGID 2207 - Code to Display linked bugs to a TC in Test Report
	      	$bug_interface = config_get("bugInterface");
			$bugs = get_bugs_for_exec($db,$bug_interface,$execution_id);
			if (($bugs) && ($tcstatus2 == 'Failed')) 
			{
				$bugString = $labels['bugs'] . ": <br />";
				foreach($bugs as $bugID => $bugInfo) 
				{
					$bugString .= $bugInfo['link_to_bts']."<br />";
				}
				$bugString = '<tr><td colspan=2 style="width:30%" valign="top"><span class="label">' . 
						$bugString . '</span></td></tr>'; 
					
			}
		}
	    else
	    {
			$tcstatus2 = $labels["test_status_not_run"];
	  		$tcnotes2 = $labels["not_aplicable"];
	  	}
	  	
	  	$code .= '<tr><td width="30%" valign="top"><span class="label">' . 
	  	         $labels['last_exec_result'] . $build_name . 
	  			     ':</span><br /><span style="text-align:center; padding:10px;">'.$tcstatus2.'</span></td>'.
	  			     '<td><u>'.$labels['testnotes'] . ':</u><br />' . $tcnotes2 . '</td></tr>' . $bugString ;
	  	
	  }


	  // collect REQ for TC
	  // MHT: based on contribution by JMU (1045)
	  if ($printingOptions['requirement'])
	  {
	    if(!$req_mgr)
	    { 
	        $req_mgr = new requirement_mgr($db);
	  	}
	  	
	  	$requirements = $req_mgr->get_all_for_tcase($id);
	  	$code .= '<tr><td width="20%" valign="top"><span class="label">'. $labels['reqs'].'</span><td>';
	  	if (sizeof($requirements))
	  	{
	  		foreach ($requirements as $req)
	  		{
	  			$code .=  htmlspecialchars($req['req_doc_id'] . ":  " . $req['title']) . "<br />";
	  		}
	  	}
	  	else
	  	{
	  		$code .= '&nbsp;' . $labels['none'] . '<br />';
	  	}
	  	
	  	$code .= "</td></tr>\n";
	  }
	  
	  // collect keywords for TC
	  // MHT: based on contribution by JMU (1045)
	  if ($printingOptions['keyword'])
	  {
	  	$code .= '<tr><td width="20%" valign="top"><span class="label">'. $labels['keywords'].':</span><td>';
    
	  	$arrKeywords = $tc_mgr->getKeywords($id);
	  	if (sizeof($arrKeywords))
	  	{
	  		foreach ($arrKeywords as $kw)
	  		{
	  			$code .= htmlspecialchars($kw['keyword']) . "<br />";
	  		}
	  	}
	  	else
	  	{
	  		$code .= '&nbsp;' . $labels['none'] . '<br>';
	  	}
	  	$code .= "</td></tr>\n";
	  }

	  $code .= "</table>\n</div>\n";
	  return $code;
}


/*
  havlatm: Remaining part of renderProjectNodeForPrinting
  @todo refactore
*/
function renderToc(&$printingOptions)
{
	$code = '';
	$printingOptions['toc_numbers'][1] = 0;
	if ($printingOptions['toc'])
	{
		$printingOptions['tocCode'] = '<h1 class="doclevel">'.lang_get('title_toc').'</h1><div class="toc">';
		$code .= "{{INSERT_TOC}}";
	}

	return $code;
}


/*
  function: renderTestSuiteNodeForPrinting
  args :
  returns:
  
  rev: 20090329 - franciscom - added ALWAYS Custom Fields
       20081207 - franciscom - refactoring using static to decrease exec time.
*/
function renderTestSuiteNodeForPrinting(&$db,&$node,&$printingOptions,$tocPrefix,$level,$tplan_id,$tproject_id)
{
    static $tsuite_mgr;
    static $labels;
    if( !$labels)
    { 
	    $labels=array('test_suite' => lang_get('test_suite'),'details' => lang_get('details'));
	}
  
	$code = null;
	$name = isset($node['name']) ? htmlspecialchars($node['name']) : '';
  	$cfields = array('design' => '');
    $cfieldFormatting=array('table_css_style' => 'class="cf"');

	if ($printingOptions['toc'])
	{
	 	$printingOptions['tocCode'] .= '<p style="padding-left: '.(10*$level).'px;"><a href="#cat' . $node['id'] . '">' .
	 	                               $name . '</a></p>';
		$code .= "<a name='cat{$node['id']}'></a>";
	}
 	$code .= "<h1 class='doclevel'>{$tocPrefix} {$labels['test_suite']} {$name}</h1>";

	if ($printingOptions['header'])
    {
        if( !$tsuite_mgr)
        { 
		    $tsuite_mgr = new testsuite($db);
		}
		$tInfo = $tsuite_mgr->get_by_id($node['id']);
   	    $code .= "<h2 class='doclevel'>{$tocPrefix}.0 {$labels['details']} </h2><div>{$tInfo['details']}</div><br />";
   	
   	    // get Custom fields    
   	    // Attention: for test suites custom fields can not be edited during execution,
   	    //            then we need to get just custom fields with scope  'design'
        $add_br=false;
        foreach($cfields as $key => $value)
        {
            $cfields[$key]= $tsuite_mgr->html_table_of_custom_field_values($node['id'],$key,null,
	                                                                       $tproject_id,$cfieldFormatting);
   	        if( strlen($cfields[$key]) > 0 )
   	        {
   	            $add_br=true;
   	            $code .= "<br />" . $cfields[$key];    
   	        }
   	    }
   	    if( $add_br ) 
   	    {
   	        $code .= '<br />';    
   	    }
     
 	}

	return $code;
}



/*
  function: renderTestPlanForPrinting
  args:
  returns:
*/
function renderTestPlanForPrinting(&$db,&$node,$item_type,&$printingOptions,
                                   $tocPrefix,$tcCnt,$level,$user_id,$tplan_id,$tprojectID)

{
	$tProjectMgr = new testproject($db);
	$tcPrefix = $tProjectMgr->getTestCasePrefix($tprojectID);
	$code =  renderTestSpecTreeForPrinting($db,$node,$item_type,$printingOptions,
                                           $tocPrefix,$tcCnt,$level,$user_id,
                                           $tplan_id,$tcPrefix,$tprojectID);
	return $code;
}


/** 
 * Render HTML for estimated and real execute duration 
 * based on contribution (BUGID 1670)
 * @param array_of_strings $statistics
 * @return string HTML code
 * @version 1.0
 */
function renderTestDuration($statistics)
{
    $output = '';
	$estimated_string = '';
	$real_string = '';
	$bEstimatedTimeAvailable = isset($statistics['estimated_execution']);
	$bRealTimeAvailable = isset($statistics['real_execution']);
    
	if( $bEstimatedTimeAvailable || $bRealTimeAvailable)
	{ 
	    $output = "<div>\n";
	    
		if($bEstimatedTimeAvailable) 
		{
			$estimated_minutes = $statistics['estimated_execution']['minutes'];
	    	$tcase_qty = $statistics['estimated_execution']['tcase_qty'];
		         
    	   	if($estimated_minutes > 60)
    	   	{
				$estimated_string = lang_get('estimated_time_hours') . round($estimated_minutes/60,2) ;
			}
			else
			{
				$estimated_string = lang_get('estimated_time_min') . $estimated_minutes;
            }
			$estimated_string = sprintf($estimated_string,$tcase_qty);

			$output .= '<p>' . $estimated_string . "</p>\n";
		}
		  
		if($bRealTimeAvailable) 
		{
			$real_minutes = $statistics['real_execution']['minutes'];
			$tcase_qty = $statistics['real_execution']['tcase_qty'];
			if($real_minutes > 0)
		    {
	        	if($real_minutes > 60)
	        	{
		        	$real_string = lang_get('real_time_hours') . round($real_minutes/60,2) ;
			    }
			    else
			    {
			      	$real_string = lang_get('real_time_min') . $real_minutes;
                } 
				$real_string = sprintf($real_string,$tcase_qty);    
			}
			$output .= '<p>' . $real_string . "</p>\n";
		}
    $output .= "</div>\n";
	}

	return $output;	
}


/** @return string final markup for HTML */
function renderEof()
{
	return "\n</body>\n</html>";
}


/**
 * compose html text for metrics (meantime estimated time only)
 * @return string html
 */
function buildTestPlanMetrics($statistics)
{
    $output = '<h1 class="doclevel">'.lang_get('title_nav_results')."</h1>\n";
    $output .= renderTestDuration($statistics);
    
	return $output;	
}


?>