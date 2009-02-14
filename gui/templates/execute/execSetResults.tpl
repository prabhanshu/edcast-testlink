{*
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: execSetResults.tpl,v 1.33 2009/02/14 15:15:45 franciscom Exp $
Purpose: smarty template - show tests to add results
Rev:

  20090212 - amitkhullar - BUGID 2068
  20081231 - franciscom - new implementation of Bulk TC Status 
                          BUGID 1635
  20081210 - franciscom - BUGID 1905 
  20081125 - franciscom - BUGID 1902 - fixed check to display button to launch remote executions
  
  20080528 - franciscom - BUGID 1504 - version number management
	20080515 - havlatm - updated help link
  20080322 - franciscom - feature: allow edit of execution notes
                          minor refactoring.
  20071231 - franciscom - new show/hide section to show exec notes
  20071103 - franciscom - BUGID 700
  20071101 - franciscom - added test automation code
  20070826 - franciscom - added some niftycube effects
  20070519 - franciscom -
  BUGID 856: Guest user can execute test case

  20070211 - franciscom - added delete logic
  20070205 - franciscom - display test plan custom fields.
  20070125 - franciscom - management of closed build
  20070104 - franciscom - custom field management for test cases
  20070101 - franciscom - custom field management for test suite div
*}

{assign var="attachment_model" value=$cfg->exec_cfg->att_model}
{assign var="title_sep"  value=$smarty.const.TITLE_SEP}
{assign var="title_sep_type3"  value=$smarty.const.TITLE_SEP_TYPE3}

{assign var="input_enabled_disabled" value="disabled"}
{assign var="att_download_only" value=true}
{assign var="enable_custom_fields" value=false}
{assign var="draw_submit_button" value=false}

{assign var="show_current_build" value=0}
{assign var="my_build_name" value=$gui->build_name|escape}

{lang_get s='build' var='build_title'}

{lang_get var='labels'
          s='edit_notes,build_is_closed,test_cases_cannot_be_executed,test_exec_notes,test_exec_result,
             th_testsuite,details,warning_delete_execution,title_test_case,th_test_case_id,
             version,has_no_assignment,assigned_to,execution_history,exec_notes,
             last_execution,exec_any_build,date_time_run,test_exec_by,build,exec_status,
             test_status_not_run,tc_not_tested_yet,last_execution,exec_current_build,
	           attachment_mgmt,bug_mgmt,delete,closed_build,alt_notes,alt_attachment_mgmt,
	           img_title_bug_mgmt,img_title_delete_execution,test_exec_summary,title_t_r_on_build,
	           execution_type_manual,execution_type_auto,run_mode,or_unassigned_test_cases,
	           no_data_available,import_xml_results,btn_save_all_tests_results,execution_type,
	           testcaseversion,btn_print,execute_and_save_results,warning,warning_nothing_will_be_saved,
	           test_exec_steps,test_exec_expected_r,btn_save_tc_exec_results,only_test_cases_assigned_to,
             click_to_open,reqs,requirement'}



{assign var="cfg_section" value=$smarty.template|basename|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}

{include file="inc_head.tpl" popup='yes' openHead='yes' jsValidate="yes" editorType=$gui->editorType}
<script language="JavaScript" src="gui/javascript/radio_utils.js" type="text/javascript"></script>
<script language="JavaScript" src="gui/javascript/expandAndCollapseFunctions.js" type="text/javascript"></script>

{if #ROUND_EXEC_HISTORY# || #ROUND_TC_TITLE# || #ROUND_TC_SPEC#}
  {assign var="round_enabled" value=1}
  <script language="JavaScript" src="{$basehref}gui/niftycube/niftycube.js" type="text/javascript"></script>
{/if}

<script language="JavaScript" type="text/javascript">
var msg="{$labels.warning_delete_execution}";
var import_xml_results="{$labels.import_xml_results}";
</script>

{include file="inc_del_onclick.tpl"}

{*  

{if $smarty.const.USE_EXT_JS_LIBRARY || $tlCfg->treemenu_type == 'EXTJS'}
  {include file="inc_ext_js.tpl"}
{/if}

*}

<script language="JavaScript" type="text/javascript">
{literal}
function load_notes(panel,exec_id)
{
  var url2load=fRoot+'lib/execute/getExecNotes.php?exec_id=' + exec_id;
  panel.load({url:url2load});
}
{/literal}
</script>

<script language="JavaScript" type="text/javascript">
{literal}
/*
Set value for a group of combo (have same prefix).
*/
function set_combo_group(formid,combo_id_prefix,value_to_assign)
{
  var f=document.getElementById(formid);
	var all_comboboxes = f.getElementsByTagName('select');
	var input_element;
	var idx=0;
		
	for(idx = 0; idx < all_comboboxes.length; idx++)
	{
	  input_element=all_comboboxes[idx];
		if( input_element.type == "select-one" && 
		    input_element.id.indexOf(combo_id_prefix)==0 &&
		   !input_element.disabled)
		{
       input_element.value=value_to_assign;
		}	
	}
}
{/literal}
</script>



{literal}
<script type="text/javascript">
{/literal}
var alert_box_title="{$labels.warning}";
var warning_nothing_will_be_saved="{$labels.warning_nothing_will_be_saved}";
{literal}
function validateForm(f)
{
  var status_ok=true;
  var cfields_inputs='';
  var cfChecks;
  var cfield_container;
  var access_key;
  cfield_container=document.getElementById('save_button_clicked').value;
  access_key='cfields_exec_time_tcversionid_'+cfield_container; 
    
  if( document.getElementById(access_key) != null )
  {    
 	    cfields_inputs = document.getElementById(access_key).getElementsByTagName('input');
      cfChecks=validateCustomFields(cfields_inputs);
      if( !cfChecks.status_ok )
      {
          var warning_msg=cfMessages[cfChecks.msg_id];
          alert_message(alert_box_title,warning_msg.replace(/%s/, cfChecks.cfield_label));
          return false;
      }
  }
  return true;
}

/*
  function: checkSubmitForStatus
            if a radio (with a particular id, see code for details)
            with $statusCode has been checked, then false is returned to block form submit().
            
            Dev. Note - remember this:
            
            KO:
               onclick="foo();checkSubmitForStatus('n')"
            OK
               onclick="foo();return checkSubmitForStatus('n')"
                              ^^^^^^ 
            

  args :
  
  returns: 

*/
function checkSubmitForStatus($statusCode)
{
  var button_clicked;
  var access_key;
  var isChecked;
  
  button_clicked=document.getElementById('save_button_clicked').value;
  access_key='status_'+button_clicked+'_'+$statusCode; 
 	isChecked = document.getElementById(access_key).checked;
  if(isChecked)
  {
      alert_message(alert_box_title,warning_nothing_will_be_saved);
      return false;
  }
  return true;
}
</script>
{/literal}





</head>
{*
IMPORTANT: if you change value, you need to chang init_args() logic on execSetResults.php
*}
{assign var="tplan_notes_view_memory_id" value="tpn_view_status"}
{assign var="build_notes_view_memory_id" value="bn_view_status"}
{assign var="bulk_controls_view_memory_id" value="bc_view_status"}


<body onLoad="show_hide('tplan_notes','{$tplan_notes_view_memory_id}',{$gui->tpn_view_status});
              show_hide('build_notes','{$build_notes_view_memory_id}',{$gui->bn_view_status});
              show_hide('bulk_controls','{$bulk_controls_view_memory_id}',{$gui->bc_view_status});
              multiple_show_hide('{$tsd_div_id_list}','{$tsd_hidden_id_list}',
                                 '{$tsd_val_for_hidden_list}');
              {if $round_enabled}Nifty('div.exec_additional_info');{/if}
              {if #ROUND_TC_SPEC# }Nifty('div.exec_test_spec');{/if}
              {if #ROUND_EXEC_HISTORY# }Nifty('div.exec_history');{/if}
              {if #ROUND_TC_TITLE# }Nifty('div.exec_tc_title');{/if}">

<h1 class="title">
	{$labels.title_t_r_on_build} {$my_build_name}
	{if $gui->ownerDisplayName != ""}
	  {$title_sep_type3}{$labels.only_test_cases_assigned_to}{$title_sep}{$gui->ownerDisplayName|escape}
	  {if $gui->include_unassigned}
	    {$labels.or_unassigned_test_cases}
	  {/if}
	{/if}
	{include file="inc_help.tpl" helptopic="hlp_executeMain"}
</h1>


<div id="main_content" class="workBack">
  {if $gui->build_is_open == 0}
  <div class="messages" style="align:center;">
     {$labels.build_is_closed}<br />
     {$labels.test_cases_cannot_be_executed}
  </div>
  <br />
  {/if}

<form method="post" id="execSetResults" name="execSetResults" 
      onSubmit="javascript:return validateForm(this);">

  <input type="hidden" id="save_button_clicked"  name="save_button_clicked" value="0" />
  <input type="hidden" id="do_delete"  name="do_delete" value="0" />
  <input type="hidden" id="exec_to_delete"  name="exec_to_delete" value="0" />

  {* -------------------------------------------------------------------------------- *}
  {* Test Plan notes show/hide management                                             *}
  {* -------------------------------------------------------------------------------- *}
  {lang_get s='test_plan_notes' var='container_title'}
  {assign var="div_id" value='tplan_notes'}
  {assign var="memstatus_id" value=$tplan_notes_view_memory_id}

  {include file="inc_show_hide_mgmt.tpl"
           show_hide_container_title=$container_title
           show_hide_container_id=$div_id
           show_hide_container_draw=false
           show_hide_container_class='exec_additional_info'
           show_hide_container_view_status_id=$memstatus_id}

  <div id="{$div_id}" class="exec_additional_info">
    {$gui->testplan_notes}
    {if $gui->testplan_cfields neq ''} <div id="cfields_testplan" class="custom_field_container">{$gui->testplan_cfields}</div>{/if}
  </div>

  {* -------------------------------------------------------------------------------- *}

  {* -------------------------------------------------------------------------------- *}
  {* Build notes show/hide management                                                 *}
  {* -------------------------------------------------------------------------------- *}
  {lang_get s='builds_notes' var='container_title'}
  {assign var="div_id" value='build_notes'}
  {assign var="memstatus_id" value=$build_notes_view_memory_id}

  {include file="inc_show_hide_mgmt.tpl"
           show_hide_container_title=$container_title
           show_hide_container_id=$div_id
           show_hide_container_view_status_id=$memstatus_id
           show_hide_container_draw=true
           show_hide_container_class='exec_additional_info'
           show_hide_container_html=$gui->build_notes}
  {* -------------------------------------------------------------------------------- *}



  {if $gui->map_last_exec eq ""}
     <div class="messages" style="text-align:center"> {$labels.no_data_available}</div>
  {else}
      {if $gui->grants->execute == 1 and $gui->build_is_open == 1}
        {assign var="input_enabled_disabled" value=""}
        {assign var="att_download_only" value=false}
        {assign var="enable_custom_fields" value=true}
        {assign var="draw_submit_button" value=true}


        {if $cfg->exec_cfg->show_testsuite_contents && $gui->can_use_bulk_op }
            {lang_get s='bulk_tc_status_management' var='container_title'}
            {assign var="div_id" value='bulk_controls'}
            {assign var="memstatus_id" value=$bulk_controls_view_memory_id}
            
            {include file="inc_show_hide_mgmt.tpl"
                     show_hide_container_title=$container_title
                     show_hide_container_id=$div_id
                     show_hide_container_draw=false
                     show_hide_container_class='exec_additional_info'
                     show_hide_container_view_status_id=$memstatus_id}

            <div id="{$div_id}" name="{$div_id}">
              {include file="execute/inc_exec_controls.tpl"
                       args_save_type='bulk'
                       args_input_enable_mgmt=$input_enabled_disabled
                       args_tcversion_id='bulk'
                       args_webeditor=$gui->bulk_exec_notes_editor
                       args_labels=$labels}
            </div>
        {/if}
    	{/if}

      {if !($cfg->exec_cfg->show_testsuite_contents && $gui->can_use_bulk_op) }
          <hr />
          <div class="groupBtn">
    	    	  <input type="button" name="print" id="print" value="{$labels.btn_print}"
    	    	         onclick="javascript:window.print();" />
    	    	  <input type="submit" id="toggle_history_on_off"
    	    	         name="{$gui->history_status_btn_name}"
    	    	         value="{lang_get s=$gui->history_status_btn_name}" />
    	    	  <input type="button" id="pop_up_import_button" name="import_xml_button"
    	    	         value="{$labels.import_xml_results}"
    	    	         onclick="javascript: openImportResult(import_xml_results);" />
          
              {* 20081125 - franciscom - BUGID 1902*}
		          {if $tlCfg->exec_cfg->enable_test_automation }
		          <input type="submit" id="execute_cases" name="execute_cases"
		                 value="{$labels.execute_and_save_results}"/>
		          {/if}
    	    	  <input type="hidden" id="history_on"
    	    	         name="history_on" value="{$gui->history_on}" />
          </div>
      {/if}
      <hr />
	{/if}

  {if $cfg->exec_cfg->show_testsuite_contents && $gui->can_use_bulk_op }
      <div>
 	    <table class="mainTable-x" width="100%">
 	    <tr>
 	    <th>{$labels.th_testsuite}</th>{* <th>&nbsp;</th> *}<th>{$labels.title_test_case}</th><th>{$labels.test_exec_result}</th>
 	    </tr>
 	    {foreach item=tc_exec from=$gui->map_last_exec name="tcSet"}
      
        {assign var="tc_id" value=$tc_exec.testcase_id}
	      {assign var="tcversion_id" value=$tc_exec.id}
	      {* IMPORTANT:
	                   Here we use version_number, which is related to tcversion_id SPECIFICATION.
	                   When we need to display executed version number, we use tcversion_number
	      *}
	      {assign var="version_number" value=$tc_exec.version}
	      
	    	<input type="hidden" id="tc_version_{$tcversion_id}" name="tc_version[{$tcversion_id}]" value='{$tc_id}' />
	    	<input type="hidden" id="version_number_{$tcversion_id}" name="version_number[{$tcversion_id}]" value='{$version_number}' />
      
        {* ------------------------------------------------------------------------------------ *}
        <tr bgcolor="{cycle values="#eeeeee,#d0d0d0"}">       
        <td>{$tsuite_info[$tc_id].tsuite_name}</td>{* <td>&nbsp;</td> *}
        <td>{$gui->tcasePrefix|escape}{$cfg->testcase_cfg->glue_character}{$tc_exec.tc_external_id|escape}::{$labels.version}: {$tc_exec.version}::{$tc_exec.name|escape}</td>
   			<td><select name="status[{$tcversion_id}]" id="status_{$tcversion_id}">
				    {html_options options=$gui->execStatusValues}
				</select>
			   </td>
        </tr>
      {/foreach}
      </table>
      </div>
  {else}
    {include file="execute/inc_exec_show_tc_exec.tpl"}
  {/if}
  
</form>
</div>
</body>
</html>
