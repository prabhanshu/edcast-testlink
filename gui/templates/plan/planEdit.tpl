{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: planEdit.tpl,v 1.2 2007/12/06 14:41:43 franciscom Exp $

Purpose: smarty template - create Test Plan
Revisions:

20070214 - franciscom -
BUGID 628: Name edit � Invalid action parameter/other behaviours if �Enter� pressed  
Bug confirmed on IE

*}

{include file="inc_head.tpl" openHead="yes" jsValidate="yes"}
{literal}
<script type="text/javascript">
{/literal}
var warning_empty_tp_name = "{lang_get s='warning_empty_tp_name'}";
{literal}
function validateForm(f)
{
  if (isWhitespace(f.testplan_name.value)) 
  {
      alert(warning_empty_tp_name);
      selectField(f, 'testplan_name');
      return false;
  }
  return true;
}


function manage_copy_ctrls(container_id,display_control_value,hide_value)
{
 o_container=document.getElementById(container_id);

 if( display_control_value == hide_value )
 {
   o_container.style.display='none';
 }
 else
 {
    o_container.style.display='';
 }
}
</script>
{/literal}
</head>

<body>
{assign var="cfg_section" value=$smarty.template|basename|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}

<h1>{lang_get s='testplan_title_tp_management'}</h1>

<div class="workBack">
{include file="inc_update.tpl" user_feedback=$user_feedback 
         result=$sqlResult item="TestPlan" action="add"}

	<h2>
	{if $tplan_id eq 0}
		{lang_get s='testplan_title_create'}
		{assign var='form_action' value='create'} 
	{else}
		{lang_get s='testplan_title_edit'} 
		{assign var='form_action' value='update'} 
	{/if}
	{lang_get s='testplan_title_for_project'} {$tproject_name|escape}</h2>

	<form method="post" name="testplan_mgmt" id="testplan_mgmt"
	      action="lib/plan/planEdit.php?action={$form_action}"
	      onSubmit="javascript:return validateForm(this);">
	
	<input type="hidden" id="tplan_id" name="tplan_id" value="{$tplan_id}">
	<table class="common" width="80%">
	
		<tr><th>{lang_get s='testplan_th_name'}</th>
			<td><input type="text" name="testplan_name" 
			           size="{#TESTPLAN_NAME_SIZE#}" 
			           maxlength="{#TESTPLAN_NAME_MAXLEN#}" 
			           value="{$tpName|escape}"/>
  				{include file="error_icon.tpl" field="testplan_name"}
			</td>
		</tr>	
		<tr><th>{lang_get s='testplan_th_notes'}</th>
			<td >{$notes}</td>
		</tr>
		{if $tplan_id eq 0}
			<tr><th>{lang_get s='testplan_question_create_tp_from'}</th>
			<td>
				<select name="copy_from_tplan_id" 
				        onchange="manage_copy_ctrls('copy_controls',this.value,'0')">
				<option value="0">{lang_get s='opt_no'}</option>
				{foreach item=testplan from=$tplans}
					<option value="{$testplan.id}">{$testplan.name|escape}</option>
				{/foreach}
				</select>
      
      <div id="copy_controls" style="display:none;">
      {assign var=this_template_dir value=$smarty.template|dirname}
      {include file="$this_template_dir/inc_controls_planEdit.tpl"}
      </div>
			</td>
			</tr>
		{else}
			<tr><td>
				{lang_get s='testplan_th_active'}
				<input type="checkbox" name="active" 
				{if $tpActive eq 1}
					checked="checked"
				{/if}
				/>
      </td></tr>
		{/if}
	
	  {* 20070127 - franciscom *}
	  {if $cf neq ''}
	  <tr> 
	    <td  colspan="2">
     <div class="custom_field_container">
     {$cf}
     </div>
	    </td>
	  </tr>
	  {/if}
	</table>

	<div class="groupBtn">	
		
		{* BUGID 628: Name edit � Invalid action parameter/other behaviours if �Enter� pressed. *}
		{if $tplan_id eq 0}
		  <input type="hidden" name="do_action" value="do_create">
		  <input type="submit" name="do_create" value="{lang_get s='btn_testplan_create'}"
		         onclick="do_action.value='do_create'"/>
		{else}
		
		  <input type="hidden" name="do_action" value="do_update">
		  <input type="submit" name="do_update" value="{lang_get s='btn_upd'}"
		         onclick="do_action.value='do_update'"/>

		{/if}

		<input type="button" name="go_back" value="{lang_get s='cancel'}"  onclick="javascript:history.back()"/>

	</div>

	</form>

<p>{lang_get s='testplan_txt_notes'}</p>
	
</div>


</body>
</html>
