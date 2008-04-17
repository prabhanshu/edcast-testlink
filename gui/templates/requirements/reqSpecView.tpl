{* TestLink Open Source Project - http://testlink.sourceforge.net/ *}
{* $Id: reqSpecView.tpl,v 1.18 2008/04/17 08:24:00 franciscom Exp $ *}
{*
   Purpose: smarty template - view a requirement specification
   Author: Martin Havlat

   rev: 20071226 - franciscom - fieldset class added (thanks ext je team)
        20071106 - franciscom - added ext js library
        20070102 - franciscom - added javascript validation of checked requirements
*}

{assign var="cfg_section" value=$smarty.template|basename|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}

{assign var="bn" value=$smarty.template|basename}
{assign var="buttons_template" value=$smarty.template|replace:"$bn":"inc_btn_$bn"}

{assign var="reqSpecID" value=$gui->req_spec_id}
{assign var="req_module" value='lib/requirements/'}
{assign var="url_args" value="reqEdit.php?doAction=create&amp;req_spec_id="}
{assign var="req_edit_url" value="$basehref$req_module$url_args$reqSpecID"}

{assign var="url_args" value="reqImport.php?req_spec_id="}
{assign var="req_import_url"  value="$basehref$req_module$url_args$reqSpecID"}

{assign var="url_args" value="reqExport.php?req_spec_id="}
{assign var="req_export_url"  value="$basehref$req_module$url_args$reqSpecID"}

{assign var="url_args" value="reqEdit.php?doAction=reorder&amp;req_spec_id="}
{assign var="req_reorder_url"  value="$basehref$req_module$url_args$reqSpecID"}

{assign var="url_args" value="reqEdit.php?doAction=create_tcases&amp;req_spec_id="}
{assign var="req_create_tc_url"  value="$basehref$req_module$url_args$reqSpecID"}


{* used on inc_btn_reqSpecView.tpl *}
{lang_get s='warning_delete_req_spec' var="warning_msg" }
{lang_get s='delete' var="del_msgbox_title" }

{include file="inc_head.tpl" openHead="yes" jsValidate="yes"}
{include file="inc_del_onclick.tpl"}

<script type="text/javascript">
/* All this stuff is needed for logic contained in inc_del_onclick.tpl */
var del_action=fRoot+'{$req_module}reqSpecEdit.php?doAction=doDelete&req_spec_id=';
</script>
</head>


<body {$body_onload}>

<div class="workBack">
<h1>
 {lang_get s='help' var='common_prefix'}
 {lang_get s='req_spec' var="xx_alt"}
 {assign var="text_hint" value="$common_prefix: $xx_alt"}
 {include file="inc_help.tpl" help="requirementsCoverage" locale=$locale
          alt="$text_hint" title="$text_hint"  style="float: right;"}
	{$gui->main_descr|escape}
</h1>
<br />
{include file="$buttons_template"}

<table class="simple" style="width: 90%">
	<tr>
		<th>{$gui->main_descr|escape}</th>
	</tr>
	<tr>
		<td>
			<fieldset class="x-fieldset x-form-label-left"><legend class="legend_container">{lang_get s='scope'}</legend>
			{$gui->req_spec.scope}
			</fieldset>
		</td>
	</tr>
  {if $gui->req_spec.total_req != 0}
  <tr>
  <td>{lang_get s='req_total'}{$smarty.const.TITLE_SEP}{$gui->req_spec.total_req}</td>
   </tr>
  {/if}
	<tr>
		<td>&nbsp;</td>
	</tr>

	<tr>
	  <td>
  	{$gui->cfields}
  	</td>
	</tr>

 <tr class="time_stamp_creation">
  <td colspan="2">
      {lang_get s='title_created'}&nbsp;{localize_timestamp ts=$gui->req_spec.creation_ts }&nbsp;
      		{lang_get s='by'}&nbsp;{$gui->req_spec.author|escape}
  </td>
  </tr>
  {if $gui->req_spec.modifier != ""}
    <tr class="time_stamp_creation">
    <td colspan="2">
    {lang_get s='title_last_mod'}&nbsp;{localize_timestamp ts=$gui->req_spec.modification_ts}
		  &nbsp;{lang_get s='by'}&nbsp;{$gui->req_spec.modifier|escape}
    </td>
    </tr>
  {/if}

</table>

{assign var="bDownloadOnly" value=true}
{if $gui->grants->req_mgmt == 'yes'}
	{assign var="bDownloadOnly" value=false}
{/if}
{include file="inc_attachments.tpl" id=$gui->req_spec.id  tableName="req_specs"
         attachmentInfos=$gui->attachments  downloadOnly=$bDownloadOnly}

</div>
{if $gui->refresh_tree}
   {include file="inc_refreshTree.tpl"}
{/if}
</body>
</html>
