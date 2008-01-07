{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: projectEdit.tpl,v 1.2 2008/01/07 20:13:30 franciscom Exp $
Purpose: smarty template - Edit existing product 

rev:
    20070725 - franciscom
    refactoring: if test project qty == 0 -> do not display the edit/delete tab
                 remove query string from url, to avoid redirect to home page.
     
    20070515 - franciscom
    BUGID 0000854: Test project cannot be deleted if name contains a ' (single quote)
    added escape type to escape modifier on onclick javascript event
    
    20070214 - franciscom 
    BUGID 628: Name edit � Invalid action parameter/other behaviours if �Enter� pressed. 

*}
{assign var="cfg_section" value=$smarty.template|basename|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}

{* Configure Actions *}
{assign var="managerURL" value="lib/project/projectEdit.php"}
{assign var="editAction" value="$managerURL?doAction=edit&tprojectID="}

{lang_get var="labels" s='caption_edit_tproject,caption_new_tproject,name,
                          title_testproject_management,notes,color,
                          enable_requirements,btn_upd,btn_inactivate,btn_activate,btn_del'} 


{include file="inc_head.tpl" openHead="yes" jsValidate="yes"}
{if $smarty.const.TL_TESTPROJECT_COLORING neq 'none'}
  {include file="inc_jsPicker.tpl"}
{/if}

{literal}
<script type="text/javascript">
{/literal}
var warning_empty_tproject_name = "{lang_get s='warning_empty_tproject_name'}";
{literal}
function validateForm(f)
{
  if (isWhitespace(f.tprojectName.value)) 
  {
      alert(warning_empty_tproject_name);
      selectField(f, 'tprojectName');
      return false;
  }
  return true;
}
</script>
{/literal}
</head>



<body>
<h1>{$labels.title_testproject_management}
{if $action != "delete"} - {$name|escape}{/if}
</h1>
<div class="workBack">

{if $user_feedback != ''}
  {include file="inc_update.tpl" result=$sqlResult item="product" name=$name user_feedback=$user_feedback}
{/if}	

	{* edit product form *}
	{if $found == "yes"}
		<div>
		<form name="edit_testproject" id="edit_testproject"
		      method="post" action="{$managerURL}"
		      onSubmit="javascript:return validateForm(this);">
		      
		<input type="hidden" name="tprojectID" value="{$id}" />
		<table class="common" width="80%">
			<caption>{$caption}{$name|escape}</caption>
			<tr>
				<td>{$labels.name}</td>
				<td><input type="text" name="tprojectName" 
  			           size="{#TESTPROJECT_NAME_SIZE#}" 
	  		           maxlength="{#TESTPROJECT_NAME_MAXLEN#}" 
				           value="{$name|escape}"/>
				  				{include file="error_icon.tpl" field="tprojectName"}
				</td>
			</tr>
	   <tr>
		  <td>{$labels.notes}</td>
		  <td width="80%">{$notes}</td>
	   </tr>
	   
	   {if $smarty.const.TL_TESTPROJECT_COLORING neq 'none'}
			<tr>
				<td>{$labels.color}</td>
				<td>
					<input type="text" name="color" value="{$color|escape}" maxlength="12" />
					{* this function below calls the color picker javascript function. 
					It can be found in the color directory *}
					<a href="javascript: TCP.popup(document.forms['edit_testproject'].elements['color'], '{$basehref}third_party/color_picker/picker.html');">
						<img width="15" height="13" border="0" alt="Click Here to Pick up the color" 
						src="third_party/color_picker/img/sel.gif" />
					</a>
				</td>
			</tr>
		 {/if}	
			<tr>
				<td>{$labels.enable_requirements}</td>
				<td>
					<select name="optReq">
					{html_options options=$gsmarty_option_yes_no selected=$reqs_default}
					</select>
				</td>
			</tr>

			<tr><td>
				{lang_get s='th_active'}
				<input type="checkbox" name="active" {if $active eq 1} checked="checked"	{/if} />
      </td></tr>

	
		</table>
    {if $canManage == "yes"}
		<div class="groupBtn">
    {* BUGID 628: Name edit � Invalid action parameter/other behaviours if �Enter� pressed. 
                  added hidden   *}
    <input type="hidden" name="doAction" value="{$doActionValue}" />
    <input type="submit" name="doActionButton" value="{$buttonValue}" />
		</div>
		{/if}

		</form>
	</div>
	{else}
		<p class="info">
		{if $name neq ''}
			{lang_get s='info_failed_loc_prod'} - {$name|escape}!<br />
		{/if}
		{lang_get s='invalid_query'}: {$sqlResult|escape}<p>
	{/if}
</div>
</body>
</html>
