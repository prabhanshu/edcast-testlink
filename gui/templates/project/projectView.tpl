{* 
TestLink Open Source Project - http://testlink.sourceforge.net/ 
$Id: projectView.tpl,v 1.3 2008/01/14 19:15:06 asielb Exp $ 
Purpose: smarty template - edit / delete Test Plan 

Development hint:
     some variables smarty and javascript are created on the inc_*.tpl files.
     
Rev :
     
*}
{assign var="cfg_section" value=$smarty.template|basename|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}

{* Configure Actions *}
{assign var="managerURL" value="lib/project/projectEdit.php"}
{assign var="deleteAction" value="$managerURL?doAction=doDelete&tprojectID="}
{assign var="editAction" value="$managerURL?doAction=edit&tprojectID="}
{assign var="createAction" value="$managerURL?doAction=create"}

{lang_get s='popup_product_delete' var="warning_msg" }
{lang_get s='delete' var="del_msgbox_title" }

{lang_get var="labels" s='title_testproject_management,testproject_txt_empty_list,tcase_id_prefix,
                          th_name,th_notes,testproject_alt_edit,testproject_alt_active,
                          th_requirement_feature,testproject_alt_delete,btn_create,
                          testproject_alt_requirement_feature,th_active,th_delete,th_id'}


{include file="inc_head.tpl" openHead="yes"}
{include file="inc_del_onclick.tpl"}

<script type="text/javascript">
/* All this stuff is needed for logic contained in inc_del_onclick.tpl */
var del_action=fRoot+'{$deleteAction}';
</script>
</head>

<body {$body_onload}>

<h1>{$labels.title_testproject_management}</h1>
{if $editResult ne ""}
	<div>
		<p class="info">{$editResult}</p>
	</div>
{/if}

<div class="workBack">
<div id="testproject_management_list">
{if $tprojects eq ''}
	{$labels.testproject_txt_empty_list}

{else}
	<table class="simple" width="95%">
		<tr>
			{if $api_ui_show eq 1}
				<th>{$labels.th_id}</th>
			{/if}
			<th>{$labels.th_name}</th>
			<th>{$labels.th_notes}</th>
			<th>{$labels.tcase_id_prefix}</th>
			<th>{$labels.th_requirement_feature}</th>
			<th class="icon_cell">{$labels.th_active}</th>
			{if $canManage == "yes"}
			<th class="icon_cell">{$labels.th_delete}</th>
			{/if}
		</tr>
		{foreach item=testproject from=$tprojects}
		<tr>
			{if $api_ui_show eq 1}
				<td>{$testproject.id}</td>
			{/if}
			<td><a href="{$editAction}{$testproject.id}"> 
				     {$testproject.name|escape} 
				     {if $gsmarty_gui->show_icon_edit}
 				         <img title="{$labels.testproject_alt_edit}" 
 				              alt="{$labels.testproject_alt_edit}" 
 				              src="{$smarty.const.TL_THEME_IMG_DIR}/icon_edit.png"/>
 				     {/if}  
 				  </a>
			</td>
			<td>
				{$testproject.notes|strip_tags|strip|truncate:#TESTPROJECT_NOTES_TRUNCATE#}
			</td>
			<td width="10%">
				{$testproject.tc_prefix}
			</td>
			<td class="clickable_icon">
				{if $testproject.option_reqs eq 1} 
  					<img style="border:none" 
  				            title="{$labels.testproject_alt_requirement_feature}" 
  				            alt="{$labels.testproject_alt_requirement_feature}" 
  				            src="{$smarty.const.TL_THEME_IMG_DIR}/apply_f2_16.png"/>
  				{else}
  					&nbsp;        
  				{/if}
			</td>
			<td class="clickable_icon">
				{if $testproject.active eq 1} 
  					<img style="border:none" 
  				            title="{$labels.testproject_alt_active}" 
  				            alt="{$labels.testproject_alt_active}" 
  				            src="{$smarty.const.TL_THEME_IMG_DIR}/apply_f2_16.png"/>
  				{else}
  					&nbsp;        
  				{/if}
			</td>
			{if $canManage == "yes"}
			<td class="clickable_icon">
				  <img style="border:none;cursor: pointer;" 
				       alt="{$labels.testproject_alt_delete}"
					   title="{$labels.testproject_alt_delete}" 
					   onclick="delete_confirmation({$testproject.id},'{$testproject.name|escape:'javascript'}',
					                                '{$del_msgbox_title}','{$warning_msg}');"
				     src="{$smarty.const.TL_THEME_IMG_DIR}/trash.png"/>
			</td>
			{/if}
		</tr>
		{/foreach}

	</table>

{/if}
</div>

 {if $canManage}
 <div class="groupBtn">
    <form method="post" action="{$createAction}">
      <input type="submit" name="create" value="{$labels.btn_create}" />
    </form>
  </div>
 {/if}
</div>

{* *}
{if $doAction == "reloadAll"}
	<script type="text/javascript">
	top.location = top.location;
	</script>
{else}
  {if $doAction == "reloadNavBar"}
	<script type="text/javascript">
  // remove query string to avoid reload of home page,
  // instead of reload only navbar
  var href_pieces=parent.titlebar.location.href.split('?');
	parent.titlebar.location=href_pieces[0];
	</script>
  {/if}
{/if}

</body>
</html>
