<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 *
 * Filename $RCSfile: testcaseCommands.class.php,v $
 *
 * @version $Revision: 1.12 $
 * @modified $Date: 2010/01/03 16:48:46 $  by $Author: franciscom $
 * testcases commands
 *
 * rev:
 *	20090831 - franciscom - preconditions 
 *	BUGID 2364 - changes in show() calls
 *  BUGID - doAdd2testplan() - added user id, con call to link_tcversions()
 *
**/
class testcaseCommands
{
	private $db;
	private $tcaseMgr;
	private $templateCfg;
	private $execution_types;
	private $grants;

	function __construct(&$db)
	{
	    $this->db=$db;
	    $this->tcaseMgr = new testcase($db);
        $this->execution_types = $this->tcaseMgr->get_execution_types();
        $this->grants=new stdClass();
        $this->grants->requirement_mgmt=has_rights($db,"mgt_modify_req"); 

	}

	function setTemplateCfg($cfg)
	{
	    $this->templateCfg=$cfg;
	}

	/**
	 * 
	 *
	 */
	function initGuiBean()
	{
	    $obj = new stdClass();
	    $obj->sqlResult = '';
	    $obj->action = '';
	    $obj->loadOnCancelURL = '';
		$obj->attachments = null;
		$obj->direct_link = null;
	    $obj->execution_types = $this->execution_types;
		$obj->main_descr = '';
		$obj->name = '';
		$obj->containerID = '';
		$obj->grants = $this->grants;
    	$obj->initWebEditorFromTemplate = false;
	    return $obj;
	}
	 
	/**
	 * 
	 *
	 */
	function create(&$argsObj,&$otCfg,$oWebEditorKeys)
	{
    	$guiObj = $this->initGuiBean();
    	$guiObj->initWebEditorFromTemplate = true;
    	
		$importance_default = config_get('testcase_importance_default');
		
    	$tc_default=array('id' => 0, 'name' => '', 'importance' => $importance_default,
  	                      'execution_type' => TESTCASE_EXECUTION_TYPE_MANUAL);

        $guiObj->containerID = $argsObj->container_id;
		if($argsObj->container_id > 0)
		{
			$pnode_info = $this->tcaseMgr->tree_manager->get_node_hierarchy_info($argsObj->container_id);
			$node_descr = array_flip($this->tcaseMgr->tree_manager->get_available_node_types());
			$guiObj->parent_info['name'] = $pnode_info['name'];
			$guiObj->parent_info['description'] = lang_get($node_descr[$pnode_info['node_type_id']]);
		}
        $sep_1 = config_get('gui_title_separator_1');
        $sep_2 = config_get('gui_title_separator_2'); 
        $guiObj->main_descr = $guiObj->parent_info['description'] . $sep_1 . $guiObj->parent_info['name'] . 
                              $sep_2 . lang_get('title_new_tc');
    	
    	$otCfg->to->map = array();
    	keywords_opt_transf_cfg($otCfg,'');
    	
		$cfPlaces = $this->tcaseMgr->buildCFLocationMap();
		foreach($cfPlaces as $locationKey => $locationFilter)
		{ 
			$guiObj->cf[$locationKey] = 
				$this->tcaseMgr->html_table_of_custom_field_inputs(null,null,'design','',null,null,
				                                                   $argsObj->testproject_id,$locationFilter);
		}	
    	$guiObj->tc=$tc_default;
    	$guiObj->opt_cfg=$otCfg;
		$templateCfg = templateConfiguration('tcNew');
		$guiObj->template=$templateCfg->default_template;
    	return $guiObj;
	}

	/**
	 * 
	 *
	 */
	function doCreate(&$argsObj,&$otCfg,$oWebEditorKeys)
	{
        $guiObj = $this->create($argsObj,$otCfg,$oWebEditorKeys);
		   
		// compute order
		$new_order = config_get('treemenu_default_testcase_order');
		$nt2exclude=array('testplan' => 'exclude_me','requirement_spec'=> 'exclude_me','requirement'=> 'exclude_me');
		$siblings = $this->tcaseMgr->tree_manager->get_children($argsObj->container_id,$nt2exclude);
		if( !is_null($siblings) )
		{
			$dummy = end($siblings);
			$new_order = $dummy['node_order']+1;
		}
		$options = array('check_duplicate_name' => config_get('check_names_for_duplicates'),
		                 'action_on_duplicate_name' => 'block');
		$tcase = $this->tcaseMgr->create($argsObj->container_id,$argsObj->name,$argsObj->summary,$argsObj->preconditions,
		                            	 $argsObj->steps,$argsObj->expected_results,$argsObj->user_id,
		                            	 $argsObj->assigned_keywords_list,$new_order,testcase::AUTOMATIC_ID,
		                            	 $argsObj->exec_type,$argsObj->importance,$options);
       
		if($tcase['status_ok'])
		{
			$cf_map = $this->tcaseMgr->cfield_mgr->get_linked_cfields_at_design($argsObj->testproject_id,ENABLED,
			                                                                    NO_FILTER_SHOW_ON_EXEC,'testcase') ;
			$this->tcaseMgr->cfield_mgr->design_values_to_db($_REQUEST,$tcase['id']);

		   	$guiObj->user_feedback = sprintf(lang_get('tc_created'),$argsObj->name);
		   	$guiObj->sqlResult = 'ok';
		   	$guiObj->initWebEditorFromTemplate = true;
			$opt_list = '';
		}
		elseif(isset($tcase['msg']))
		{
			$guiObj->user_feedback = lang_get('error_tc_add');
			$guiObj->user_feedback .= '' . $tcase['msg'];
	    	$guiObj->sqlResult = 'ko';
    		$opt_list = $argsObj->assigned_keywords_list;
    		$guiObj->initWebEditorFromTemplate = false;
		}

        keywords_opt_transf_cfg($otCfg, $opt_list);
    	$guiObj->opt_cfg=$otCfg;
		$templateCfg = templateConfiguration('tcNew');
		$guiObj->template=$templateCfg->default_template;
		return $guiObj;    
    }




	/*
	  function: edit (Test Case)
	
	  args:
	  
	  returns: 
	
	*/
	function edit(&$argsObj,&$otCfg,$oWebEditorKeys)
	{
    	$guiObj = $this->initGuiBean();
    	$otCfg->to->map = $this->tcaseMgr->get_keywords_map($argsObj->tcase_id," ORDER BY keyword ASC ");
    	
    	// new dBug($otCfg);
    	// new dBug($otCfg->to->map);
    	// new dBug($argsObj->assigned_keywords_list);
    	keywords_opt_transf_cfg($otCfg, $argsObj->assigned_keywords_list);
    	// new dBug($otCfg);
    	
    	
  		$tc_data = $this->tcaseMgr->get_by_id($argsObj->tcase_id,$argsObj->tcversion_id);

  		foreach($oWebEditorKeys as $key)
   		{
  		  	$guiObj->$key = $tc_data[0][$key];
  		  	$argsObj->$key = $tc_data[0][$key];
  		}
  		
  		$cf_smarty = null;
		$cfPlaces = $this->tcaseMgr->buildCFLocationMap();
		foreach($cfPlaces as $locationKey => $locationFilter)
		{ 
			$cf_smarty[$locationKey] = 
				$this->tcaseMgr->html_table_of_custom_field_inputs($argsObj->tcase_id,null,'design','',
				                                                   null,null,null,$locationFilter);
		}	
   		$templateCfg = templateConfiguration('tcEdit');
		$guiObj->cf = $cf_smarty;
    	$guiObj->tc=$tc_data[0];
    	$guiObj->opt_cfg=$otCfg;
		$guiObj->template=$templateCfg->default_template;
    	return $guiObj;
  }


  /*
    function: doUpdate

    args:
    
    returns: 

  */
    function doUpdate(&$argsObj,$request)
	{
        $smartyObj = new TLSmarty();
        $viewer_args=array();

    	$guiObj = $this->initGuiBean();
   	    $guiObj->refresh_tree=$argsObj->do_refresh ? "yes" : "no";
        $guiObj->has_been_executed = $argsObj->has_been_executed;

		  // to get the name before the user operation
        $tc_old = $this->tcaseMgr->get_by_id($argsObj->tcase_id,$argsObj->tcversion_id);

        $ret=$this->tcaseMgr->update($argsObj->tcase_id, $argsObj->tcversion_id, $argsObj->name, 
		                             $argsObj->summary, $argsObj->preconditions, $argsObj->steps, 
		                             $argsObj->expected_results, $argsObj->user_id, 
		                             $argsObj->assigned_keywords_list,
		                             TC_DEFAULT_ORDER, $argsObj->exec_type, $argsObj->importance);

        if($ret['status_ok'])
		{
		    $guiObj->refresh_tree='yes';
		    $guiObj->user_feedback = '';
  			$ENABLED = 1;
	  		$NO_FILTERS = null;
		  	$cf_map=$this->tcaseMgr->cfield_mgr->get_linked_cfields_at_design($argsObj->testproject_id,
		                                                                      $ENABLED,$NO_FILTERS,'testcase') ;
			$this->tcaseMgr->cfield_mgr->design_values_to_db($request,$argsObj->tcase_id);
         
            $guiObj->attachments[$argsObj->tcase_id] = getAttachmentInfosFrom($this->tcaseMgr,$argsObj->tcase_id);
		}
		else
		{
		    $guiObj->refresh_tree='no';
		    $guiObj->user_feedback = $ret['msg'];
		}
	
	    $viewer_args['refresh_tree'] = $guiObj->refresh_tree;
 	    $viewer_args['user_feedback'] = $guiObj->user_feedback;
      
	    $this->tcaseMgr->show($smartyObj,$guiObj, $this->templateCfg->template_dir,
	                          $argsObj->tcase_id,$argsObj->tcversion_id,$viewer_args,null,$argsObj->show_mode);
 
        return $guiObj;
  }  


  /**
   * doAdd2testplan
   *
   */
	function doAdd2testplan(&$argsObj,$request)
	{
      	$smartyObj = new TLSmarty();
      	$smartyObj->assign('attachments',null);
    	$guiObj = $this->initGuiBean();

      	$viewer_args=array();
      	$tplan_mgr = new testplan($this->db);
      	
   	  	$guiObj->refresh_tree=$argsObj->do_refresh?"yes":"no";
      	$item2link[$argsObj->tcase_id]=$argsObj->tcversion_id;
      	
      	if( isset($request['add2tplanid']) )
      	{
      	    foreach($request['add2tplanid'] as $tplan_id => $value)
      	    {
      	        $tplan_mgr->link_tcversions($tplan_id,$item2link,$argsObj->user_id);  
      	    }
      	    $this->tcaseMgr->show($smartyObj,$guiObj,$this->templateCfg->template_dir,
	  	                          $argsObj->tcase_id,$argsObj->tcversion_id,$viewer_args);
      	}
      	return $guiObj;
  }

  /**
   * add2testplan - is really needed???? 20090308 - franciscom - TO DO
   *
   */
	function add2testplan(&$argsObj,$request)
	{
      // $smartyObj = new TLSmarty();
      // $guiObj=new stdClass();
      // $viewer_args=array();
      // $tplan_mgr = new testplan($this->db);
      // 
   	  // $guiObj->refresh_tree=$argsObj->do_refresh?"yes":"no";
      // 
      // $item2link[$argsObj->tcase_id]=$argsObj->tcversion_id;
      // foreach($request['add2tplanid'] as $tplan_id => $value)
      // {
      //     $tplan_mgr->link_tcversions($tplan_id,$item2link);  
      // }
	    // $this->tcaseMgr->show($smartyObj,$this->templateCfg->template_dir,
	    //                       $argsObj->tcase_id,$argsObj->tcversion_id,$viewer_args);
      // 
      // return $guiObj;
  }


  /**
   * 
   *
   */
	function delete(&$argsObj,$request)
	{
    	$guiObj = $this->initGuiBean();
 		$guiObj->delete_message = '';
		$cfg = config_get('testcase_cfg');

 		$my_ret = $this->tcaseMgr->check_link_and_exec_status($argsObj->tcase_id);
 		$guiObj->exec_status_quo = $this->tcaseMgr->get_exec_status($argsObj->tcase_id);
		
  		switch($my_ret)
		{
			case "linked_and_executed":
				$guiObj->exec_status_quo = lang_get('warning') . TITLE_SEP . lang_get('delete_linked_and_exec');
				break;
    	
			case "linked_but_not_executed":
				$guiObj->exec_status_quo = lang_get('warning') . TITLE_SEP . lang_get('delete_linked');
				break;
		}
		$tcinfo = $this->tcaseMgr->get_by_id($argsObj->tcase_id);
		list($prefix,$root) = $this->tcaseMgr->getPrefix($argsObj->tcase_id,$argsObj->testproject_id);
        $prefix .= $cfg->glue_character;
        $external_id = $prefix . $tcinfo[0]['tc_external_id'];
        
		$guiObj->title = lang_get('title_del_tc');
		$guiObj->testcase_name =  $tcinfo[0]['name'];
		$guiObj->testcase_id = $argsObj->tcase_id;
		$guiObj->tcversion_id = testcase::ALL_VERSIONS;
		$guiObj->refresh_tree = "yes";
 		$guiObj->main_descr = lang_get('title_del_tc') . TITLE_SEP . $external_id . TITLE_SEP . $tcinfo[0]['name'];  
    
    	$templateCfg = templateConfiguration('tcDelete');
  		$guiObj->template=$templateCfg->default_template;
		return $guiObj;
	}

  /**
   * 
   *
   */
	function doDelete(&$argsObj,$request)
	{
		$cfg = config_get('testcase_cfg');

    	$guiObj = $this->initGuiBean();
 		$guiObj->user_feedback = '';
		$guiObj->delete_message = '';
		$guiObj->action = 'deleted';
		$guiObj->sqlResult = 'ok';
		$tcinfo = $this->tcaseMgr->get_by_id($argsObj->tcase_id,$argsObj->tcversion_id);
		list($prefix,$root) = $this->tcaseMgr->getPrefix($argsObj->tcase_id,$argsObj->testproject_id);
        $prefix .= $cfg->glue_character;
        $external_id = $prefix . $tcinfo[0]['tc_external_id'];
		
		if (!$this->tcaseMgr->delete($argsObj->tcase_id,$argsObj->tcversion_id))
		{
			$guiObj->action = '';
			$guiObj->sqlResult = $this->tcaseMgr->db->error_msg();
		}
		else
		{
			$guiObj->user_feedback = sprintf(lang_get('tc_deleted'), ":" . $external_id . TITLE_SEP . $tcinfo[0]['name']);
		}
    	
		$guiObj->main_descr = lang_get('title_del_tc') . ":" . $external_id . TITLE_SEP . htmlspecialchars($tcinfo[0]['name']);
  
  		// 20080706 - refresh must be forced to avoid a wrong tree situation.
  		// if tree is not refreshed and user click on deleted test case he/she
  		// will get a SQL error
  		// $refresh_tree = $cfg->spec->automatic_tree_refresh ? "yes" : "no";
  		$guiObj->refresh_tree = "yes";
 
  		// When deleting JUST one version, there is no need to refresh tree
		if($argsObj->tcversion_id != testcase::ALL_VERSIONS)
		{
			  $guiObj->main_descr .= " " . lang_get('version') . " " . $tcinfo[0]['version'];
			  $guiObj->refresh_tree = "no";
		  	  $guiObj->user_feedback = sprintf(lang_get('tc_version_deleted'),$tcinfo[0]['name'],$tcinfo[0]['version']);
		}

		$guiObj->testcase_name = $tcinfo[0]['name'];
		$guiObj->testcase_id = $argsObj->tcase_id;
    
    	$templateCfg = templateConfiguration('tcDelete');
  		$guiObj->template=$templateCfg->default_template;
		return $guiObj;
	}



} // end class  
?>