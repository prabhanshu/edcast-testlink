{*
TestLink Open Source Project - http://testlink.sourceforge.net/ 
$Id: tcStepEdit.tpl,v 1.2 2010/01/06 17:44:43 franciscom Exp $ 
Purpose: create/edit test case step

rev: 
     
*}

{assign var="cfg_section" value=$smarty.template|basename|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}

{lang_get var="labels"
          s="warning,warning_step_number,btn_save,cancel,warning_unsaved"}

{include file="inc_head.tpl" openHead='yes' jsValidate="yes" editorType=$gui->editorType}

{include file="inc_del_onclick.tpl"}

<script type="text/javascript">
var warning_step_number = "{$labels.warning_step_number}";
var alert_box_title = "{$labels.warning}";

{literal}
function validateForm(f)
{
  var status_ok = true;
	value = parseInt(f.step_number.value);
	if( isNaN(value) || value <= 0)
	{
		alert_message(alert_box_title,warning_step_number);
		selectField(f,'step_number');
		return false;
	}
	return true;
}
{/literal}
</script>
</head>

<body onLoad="focusInputField('step')">
<h1 class="title">{$gui->main_descr}</h1> 

<div class="workBack" style="width:97%;">

{if $gui->user_feedback != ''}
	<div>
		<p class="info">{$gui->user_feedback}</p>
	</div>
{/if}

{if $has_been_executed}
    {lang_get s='warning_editing_executed_tc' var="warning_edit_msg"}
    <div class="messages" align="center">{$warning_edit_msg}</div>
{/if}

<form method="post" action="lib/testcases/tcEdit.php" name="tcStepEdit"
      onSubmit="return validateForm(this);">
	<input type="hidden" name="testcase_id" value="{$gui->tcase_id}" />
	<input type="hidden" name="tcversion_id" value="{$gui->tcversion_id}" />
	<input type="hidden" name="doAction" value="" />
 	<input type="hidden" name="show_mode" value="{$gui->show_mode}" />
	<input type="hidden" name="step_id" value="{$gui->step_id}" />

	<div class="groupBtn">
		<input id="do_update" type="submit" name="do_update" 
		       onclick="doAction.value='{$gui->operation}'" value="{$labels.btn_save}" />
		
		<input type="button" name="go_back" value="{$labels.cancel}" 
		       onclick="history.back();"/>
	</div>	

	{assign var=this_template_dir value=$smarty.template|dirname}
	{include file="$this_template_dir/tcStepEditViewer.tpl"}
	
	<div class="groupBtn">
		<input id="do_update" type="submit" name="do_update" 
		       onclick="doAction.value='{$gui->operation}'" value="{$labels.btn_save}" />
		<input type="button" name="go_back" value="{$labels.cancel}" 
		       onclick="history.back();"/>
	</div>	
</form>

<script type="text/javascript" defer="1">
   	document.forms[0].step_number.focus();
</script>

</div>
</body>
</html>
