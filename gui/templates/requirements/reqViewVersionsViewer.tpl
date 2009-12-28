{*
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: reqViewVersionsViewer.tpl,v 1.6 2009/12/28 17:23:50 franciscom Exp $
viewer for requirement

rev:
*}
{lang_get var="labels"
          s="requirement_spec,Requirements,scope,status,type,expected_coverage,  
             coverage,btn_delete,btn_cp,btn_edit,btn_del_this_version,btn_new_version,
             btn_del_this_version,
             version,testproject,title_last_mod,title_created,by"}

             
{assign var="hrefReqSpecMgmt" value="lib/general/frmWorkArea.php?feature=reqSpecMgmt"}
{assign var="hrefReqSpecMgmt" value=$basehref$hrefReqSpecMgmt}

{assign var="hrefReqMgmt" value="lib/requirements/reqView.php?showReqSpecTitle=1&requirement_id="}
{assign var="hrefReqMgmt" value=$basehref$hrefReqMgmt}

{assign var="module" value='lib/requirements/'}
{assign var="req_id" value=$args_req.id}
{assign var="req_version_id" value=$args_req.version_id}

{if $args_show_title }
    {if $args_tproject_name != ''}
     <h2>{$labels.testproject} {$args_tproject_name|escape} </h2>
    {/if}
    {if $args_req_spec_name != ''}
     <h2>{$labels.req_spec} {$args_req_spec_name|escape} </h2>
    {/if}
	  <h2>{$labels.title_test_case} {$args_req.title|escape} </h2>
{/if}
{assign var="warning_edit_msg" value=""}

{if $args_grants->req_mgmt == "yes"}
  <div class="groupBtn">
	  <form id="req" name="req" action="lib/requirements/reqEdit.php" method="post">
	  	<input type="hidden" name="requirement_id" value="{$args_req.id}" />
	  	<input type="hidden" name="req_version_id" value="{$args_req.version_id}" />
	  	<input type="hidden" name="doAction" value="" />
	  	
	  	<input type="submit" name="edit_req" value="{$labels.btn_edit}" onclick="doAction.value='edit'"/>
	  	
	  	{if $args_can_delete_req}
	  	<input type="button" name="delete_req" value="{$labels.btn_delete}"
	  	       onclick="delete_confirmation({$args_req.id},
	  	                                    '{$args_req.req_doc_id|escape:'javascript'|escape}:{$args_req.title|escape:'javascript'|escape}',
	  				                              '{$del_msgbox_title}', '{$warning_msg}',pF_delete_req);"	/>

	  	{/if}
	  	
	  	{if $args_can_delete_version}
	  	<input type="button" name="delete_req_version" value="{$labels.btn_del_this_version}"
	  	       onclick="delete_confirmation({$args_req.version_id},
	  	                '{$labels.version}:{$args_req.version}-{$args_req.req_doc_id|escape:'javascript'|escape}:{$args_req.title|escape:'javascript'|escape}',
	  				                              '{$del_msgbox_title}', '{$warning_msg}',pF_delete_req_version);"	/>
	  				                                
	  	{/if}

	    {if $args_can_copy}  				                                
	  	<input type="submit" name="copy_req" value="{$labels.btn_cp}" onclick="doAction.value='copy'"/>
	  	{/if}
	  	<input type="submit" name="new_version" value="{$labels.btn_new_version}" onclick="doAction.value='doCreateVersion'"/>
	  </form>
  </div> {* class="groupBtn" *}
{/if}


<table class="simple">
  {if $args_show_title}
	<tr>
		<th colspan="2">
		{$args_req.req_doc_id}{$smarty.const.TITLE_SEP}{$args_req.title|escape}</th>
	</tr>
  {/if}
	<tr>
    <th>{$args_req.req_doc_id|escape}{$tlCfg->gui_title_separator_1}{$args_req.title|escape}</th>
	</tr>

  {if $args_show_version}
	  <tr>
	  	<td class="bold" colspan="2">{$labels.version}
	  	{$args_req.version}
	  	</td>
	  </tr>
	{/if}

  <tr>
	  <td>{$labels.status}{$smarty.const.TITLE_SEP}{$args_gui->reqStatus[$args_req.status]}</td>
	</tr>
	<tr>
	  <td>{$labels.type}{$smarty.const.TITLE_SEP}{$args_gui->reqType[$args_req.type]}</td>
	</tr>
	<tr>
	  <td>{$labels.expected_coverage}{$smarty.const.TITLE_SEP}{$args_req.expected_coverage}</td>
	</tr>

	<tr>
		<td>
			<fieldset class="x-fieldset x-form-label-left"><legend class="legend_container">{$labels.scope}</legend>
			{$args_req.scope}
			</fieldset>
		</td>
	</tr>
	<td>
	  <fieldset class="x-fieldset x-form-label-left"><legend class="legend_container">{$labels.coverage}</legend>
	  {if $args_req_coverage != ''}
	  {section name=row loop=$args_req_coverage}
	    <span> {* BUGID 2521 *}
	    <a href="javascript:openTCaseWindow({$args_req_coverage[row].id})">
	    {$args_gui->tcasePrefix|escape}{$args_gui->glueChar}{$args_req_coverage[row].tc_external_id}{$args_gui->pieceSep}{$args_req_coverage[row].name|escape}</a>
	    </span><br />
	   {sectionelse}
	  <span>{$labels.req_msg_notestcase}</span>
	  {/section}
	  {/if}
	  
	  </fieldset>
			</td>
	 </tr>
	<tr>
			<td>&nbsp;</td>
	</tr>

	<tr class="time_stamp_creation">
  		<td >
      		{$labels.title_created}&nbsp;{localize_timestamp ts=$args_req.creation_ts }&nbsp;
      		{$labels.by}&nbsp;{$args_req.author|escape}
  		</td>
  </tr>
	{if $args_req.modifier != ""}
  <tr class="time_stamp_creation">
  		<td >
    		{$labels.title_last_mod}&nbsp;{localize_timestamp ts=$args_req.modification_ts}
		  	&nbsp;{$labels.by}&nbsp;{$args_req.modifier|escape}
    	</td>
  </tr>
	{/if}
	<tr>
	</tr>
	<tr>
	</tr>
</table>

	{if $args_cf neq ''}
	<div>
        <div id="cfields_design_time" class="custom_field_container">{$args_cf}</div>
	</div>
	{/if}