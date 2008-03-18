{*
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: tcView_viewer.tpl,v 1.8 2008/03/18 21:05:27 schlundus Exp $
viewer for test case in test specification

20080113 - franciscom - changed format for test case id + name
20071204 - franciscom - display execution_type
20070628 - franciscom - active_status_op_enabled always true
20061230 - franciscom - an experiment to make simple management
                        of frequent used href
*}

{assign var="hrefReqSpecMgmt" value="lib/general/frmWorkArea.php?feature=reqSpecMgmt"}
{assign var="hrefReqSpecMgmt" value=$basehref$hrefReqSpecMgmt}

{assign var="hrefReqMgmt" value="lib/requirements/reqView.php?requirement_id="}
{assign var="hrefReqMgmt" value=$basehref$hrefReqMgmt}


{if $args_show_title == "yes"}
    {if $args_tproject_name != ''}
     <h1>{lang_get s='testproject'} {$args_tproject_name|escape} </h1>
     <br />
    {/if}
    {if $args_tsuite_name != ''}
     <h1>{lang_get s='testsuite'} {$args_tsuite_name|escape} </h1>
     <br />
    {/if}

<h1>{lang_get s='title_test_case'} {$args_testcase.name|escape} </h1>
{/if}
	{assign var="author_userinfo" value=$args_users[$args_testcase.author_id]}
 	{assign var="updater_userinfo" value=$args_users[$args_testcase.updater_id]}

{if $args_can_edit == "yes" }

  {assign var="edit_enabled" value=0}
  {* 20070628 - franciscom
     Seems logical you can disable some you have executed before*}
  {assign var="active_status_op_enabled" value=1}
  {assign var="has_been_executed" value=0}
  {lang_get s='can_not_edit_tc' var="warning_edit_msg"}
  {if $args_status_quo eq null or
      $args_status_quo[$args_testcase.id].executed eq null}

      {assign var="edit_enabled" value=1}
      {* {assign var="active_status_op_enabled" value=1}  *}
      {assign var="warning_edit_msg" value=""}

  {else}
     {if $args_tcase_cfg->can_edit_executed eq 1}
       {assign var="edit_enabled" value=1}
       {assign var="has_been_executed" value=1}
       {lang_get s='warning_editing_executed_tc' var="warning_edit_msg"}
     {/if}
  {/if}


<div class="groupBtn">
	<form method="post" action="lib/testcases/tcEdit.php">
	  <input type="hidden" name="testcase_id" value="{$args_testcase.testcase_id}" />
	  <input type="hidden" name="tcversion_id" value="{$args_testcase.id}" />
	  <input type="hidden" name="has_been_executed" value="{$has_been_executed}" />

	    {assign var="go_newline" value=""}
	    {if $edit_enabled}
	 	    <input type="submit" name="edit_tc" value="{lang_get s='btn_edit'}" />
	    {/if}
	
		{if $args_can_delete_testcase == "yes" }
			<input type="submit" name="delete_tc" value="{lang_get s='btn_del'}" />
	    {/if}
	
	    {if $args_can_move_copy == "yes" }
	   		<input type="submit" name="move_copy_tc"   value="{lang_get s='btn_mv_cp'}" />
	    	<br />
	     	{assign var="go_newline" value="<br />"}
	    {/if}
	
	    {$go_newline}
	 	{if $args_can_delete_version == "yes" }
			 <input type="submit" name="delete_tc_version" value="{lang_get s='btn_del_this_version'}" />
	    {/if}
	
		{* --------------------------------------------------------------------------------------- *}
		{if $active_status_op_enabled eq 1}
	        {if $args_testcase.active eq 0}
				{assign var="act_deact_btn" value="activate_this_tcversion"}
				{assign var="act_deact_value" value="activate_this_tcversion"}
				{assign var="version_title_class" value="inactivate_version"}
	      	{else}
				{assign var="act_deact_btn" value="deactivate_this_tcversion"}
				{assign var="act_deact_value" value="deactivate_this_tcversion"}
				{assign var="version_title_class" value="activate_version"}
	      	{/if}
	      	<input type="submit" name="{$act_deact_btn}"
	                           value="{lang_get s=$act_deact_value}" />
	    {/if}
	 	{* --------------------------------------------------------------------------------------- *}
   	&nbsp;&nbsp;
   		<input type="submit" name="do_create_new_version"   value="{lang_get s='btn_new_version'}" />

	</form>
	<form method="post" action="lib/testcases/tcExport.php" name="tcexport">
		<br/>
		<input type="hidden" name="testcase_id" value="{$args_testcase.testcase_id}" />
		<input type="hidden" name="tcversion_id" value="{$args_testcase.id}" />
		<input type="submit" name="export_tc"   value="{lang_get s='btn_export'}" />
		{* 20071102 - franciscom *}
		{*
		<input type="button" name="tstButton" value="{lang_get s='btn_execute_automatic_testcase'}"
		       onclick="javascript: startExecution({$args_testcase.testcase_id},'testcase');" />
		*}
	</form>

	{* 20071102 - franciscom *}
	{*
	<div id="inProgress"></div>
  	*}

  	{if $warning_edit_msg neq ""}
    	<p><div class="warning_message" align="center">{$warning_edit_msg}</div>
  	{/if}
	{*
	</div>
	*}
{/if}

  {if $args_testcase.active eq 0}
    <br /><div class="warning_message" align="center">{lang_get s='tcversion_is_inactive_msg'}</div>
  {/if}
	<div id="executionResults"></div>

	<table width="95%" class="simple" border="0">
    {if $args_show_title == "yes"}
		<tr>
			<th  colspan="2">
			{$args_testcase.tc_external_id}{$smarty.const.TITLE_SEP}{$args_testcase.name|escape}</th>
		</tr>
    {/if}




    {if $args_show_version == "yes"}
		<tr>
			<td class="bold" colspan="2">{lang_get s='version'}
			{$args_testcase.version|escape}</td>
		</tr>
		{/if}

		<tr>
			<td class="bold" colspan="2">{lang_get s='summary'}</td>
		</tr>
		<tr>
			<td colspan="2">{$args_testcase.summary}</td>
		</tr>
		<tr>
			<td class="bold" width="50%">{lang_get s='steps'}</td>
			<td class="bold" width="50%">{lang_get s='expected_results'}</td>
		</tr>
		<tr>
			<td>{$args_testcase.steps}</td>
			<td>{$args_testcase.expected_results}</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
    <tr>
			<td colspan="2"><span class="labelHolder">{lang_get s='execution_type'}</span>
			                {$smarty.const.TITLE_SEP}
			                {$execution_types[$args_testcase.execution_type]}</td>
		</tr>

		<tr>
			<td colspan="2">{if $args_cf neq ''}
			                 <div class="custom_field_container">{$args_cf}</div>
			                {else}
			                   &nbsp;
			                {/if}
			 </td>
		</tr>

		<tr>
		  	<td colspan="2">
				<table cellpadding="0" cellspacing="0" style="font-size:100%;">
			    <tr>
			     	  <td width="35%"><a href={$gsmarty_href_keywordsView}>{lang_get s='keywords'}</a>: &nbsp;
						</td>
				 	  <td>
					  	{foreach item=keyword_item from=$args_keywords_map}
						    {$keyword_item|escape}
						    <br />
						{/foreach}
					</td>
				</tr>
				</table>
			</td>
		</tr>


	{if $opt_requirements == TRUE && $view_req_rights == "yes"}
		<tr>
		  	<td colspan="2">
  				<table cellpadding="0" cellspacing="0" style="font-size:100%;">
     			  <tr>
       			  <td colspan="2"><span><a title="{lang_get s='requirement_spec'}" href="{$hrefReqSpecMgmt}"
      				target="mainframe" class="bold">{lang_get s='Requirements'}</a>
      				: &nbsp;</span>
      			  </td>
      			  <td>
      				{section name=item loop=$args_reqs}
      					<span onclick="javascript: open_top('{$hrefReqMgmt}{$args_reqs[item].id}');"
      					style="cursor:  pointer;">{$args_reqs[item].title|escape}</span>
      					{if !$smarty.section.item.last}<br />{/if}
      				{sectionelse}
      					{lang_get s='none'}
      				{/section}
      			  </td>
    		    </tr>
    		  </table>
    		</td>
		</tr>
	{/if}

  <tr>
  <td colspan="2">
  &nbsp;
  </td>
  </tr>
  <tr class="time_stamp_creation">
  <td colspan="2">
      {lang_get s='title_created'}&nbsp;{localize_timestamp ts=$args_testcase.creation_ts }&nbsp;
      		{lang_get s='by'}&nbsp;{$author_userinfo->getDisplayName()|escape}
  </td>
  </tr>
  {if $args_testcase.updater_last_name ne "" || $args_testcase.updater_first_name ne ""}
    <tr class="time_stamp_creation">
    <td colspan="2">
    {lang_get s='title_last_mod'}&nbsp;{localize_timestamp ts=$args_testcase.modification_ts}
		  &nbsp;{lang_get s='by'}&nbsp;{$updater_userinfo->getDisplayName()|escape}
    </td>
    </tr>
  {/if}
	</table>

