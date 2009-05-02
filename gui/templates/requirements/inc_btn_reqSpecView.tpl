{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: inc_btn_reqSpecView.tpl,v 1.10 2009/05/02 15:18:21 franciscom Exp $

rev: 20090321 - franciscom 
     20080925 - franciscom - child requirements/folder management 
     20080924 - franciscom - if req spec has no requirements then disable certain features
*}
{lang_get var='labels'
          s='btn_req_create,btn_generate_doc,btn_new_req_spec'}
{assign var="cfg_section" value=$smarty.template|basename|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}

<!--- inc_btn_reqSpecView.tpl -->
<div class="groupBtn">
  	<form id="req_spec" name="req_spec" action="{$req_module}reqSpecEdit.php" method="post">
  	<div style="margin-bottom: 5px;">
  		<input type="hidden" name="req_spec_id" value="{$gui->req_spec_id}" />
  		<input type="hidden" name="doAction" value="" />
  	
  		{if $gui->grants->req_mgmt == "yes"}
    
        	{if $tlCfg->req_cfg->child_requirements_mgmt == $smarty.const.ENABLED}
  	        <input type="button" name="btn_new_req_spec" 
  	               value="{$labels.btn_new_req_spec}"
		               onclick="location='{$req_spec_new_url}'" />  
        	{/if}
  		<input type="submit" name="edit_req_spec" 
  	       value="{lang_get s='btn_edit_spec'}" 
  	       onclick="doAction.value='edit'"/>
  		<input type="button" name="deleteSRS" value="{lang_get s='btn_delete_spec'}"
  	       onclick="delete_confirmation({$gui->req_spec.id},'{$gui->req_spec.title|escape:'javascript'|escape}',
                                        '{$del_msgbox_title}','{$warning_msg}');"	/>
  		{/if}
  		<input type="button" name="print_req_spec" value="{$labels.btn_generate_doc}"
  			onclick="javascript: window.open('{$basehref}{$req_module}reqSpecPrint.php?req_spec_id={$gui->req_spec.id}', 
  		        '_blank','left=100,top=50,fullscreen=no,resizable=yes,toolbar=no,status=no,menubar=no,scrollbars=yes,directories=no,location=no,width=600,height=650');" />
  		<input type="button" name="analyse" value="{lang_get s='btn_analyse'}"
  			onclick="javascript: location.href=fRoot+'{$req_module}reqSpecAnalyse.php?req_spec_id={$gui->req_spec.id}';" />
	</div>
	<div>
  		{if $gui->grants->req_mgmt == "yes"}
	  	<input type="button" name="create_req" 
  	       value="{$labels.btn_req_create}"
		       onclick="location='{$req_edit_url}'" />  
		<input type="button" name="importReq" value="{lang_get s='btn_import'}"
		       onclick="location='{$req_import_url}'" />
	        {if $gui->requirements_count > 0}
		  	{* ===========================================================================
		  	   REMOVED
		  	   20090502 - franciscom -   This can be done with ajax tree
		  	   <input type="button" name="reorderReq" value="{lang_get s='req_reorder'}"
		               onclick="location='{$req_reorder_url}'" />
		       ===========================================================================        
        *}    
  			<input type="button" name="create_tcases" value="{lang_get s='req_select_create_tc'}"
		               onclick="location='{$req_create_tc_url}'" />
    		<input type="button" name="exportReq" value="{lang_get s='btn_export_reqs'}"
		               onclick="location='{$req_export_url}'" />
  		    {/if}
	  	{/if}
	</div>
  </form>
</div>