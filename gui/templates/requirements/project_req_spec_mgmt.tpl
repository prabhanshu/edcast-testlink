{*
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: project_req_spec_mgmt.tpl,v 1.12 2009/03/16 21:35:39 schlundus Exp $

rev: 20080415 - franciscom - refactoring
*}
{* ------------------------------------------------------------------------- *}

{lang_get var="labels" s="btn_reorder_req_spec,btn_new_req_spec"}
{assign var="req_module" value='lib/requirements/'}
{assign var="url_args" value="reqSpecEdit.php?doAction=create&amp;tproject_id="}
{assign var="req_spec_new_url" value="$basehref$req_module$url_args"}

{assign var="url_args" value="reqSpecEdit.php?doAction=reorder&amp;tproject_id="}
{assign var="req_spec_reorder_url" value="$basehref$req_module$url_args}

{include file="inc_head.tpl"}

{* ------------------------------------------------------------------------- *}
<body>
<h1 class="title">{$gui->main_descr|escape}</h1>
<div class="workBack">
	<div class="groupBtn">
		<form method="post">
			<input type="button" id="new_req_spec" name="new_req_spec"
			       value="{$labels.btn_new_req_spec}"
			       onclick="location='{$req_spec_new_url}{$gui->tproject_id}'" />
		</form>
	</div>
</div>

{if $gui->refresh_tree == "yes"}
   {include file="inc_refreshTree.tpl"}
{/if}

</body>
</html>
