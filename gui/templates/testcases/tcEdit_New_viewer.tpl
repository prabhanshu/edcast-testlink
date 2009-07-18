{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: tcEdit_New_viewer.tpl,v 1.13 2009/07/18 14:43:04 franciscom Exp $
Purpose: smarty template - create new testcase

Rev:
    20090718 - franciscom - added management of custom field location
    20080512 - franciscom - BUGID 
    20061231 - franciscom - viewer for tcEdit.tpl and tcNew.tpl
*}

{* ---------------------------------------------------------------- *}
{lang_get var='labels' 
          s='tc_title,alt_add_tc_name,summary,steps,expected_results,
             execution_type,test_importance,tc_keywords,assign_requirements'}

{* Steps and results Layout management *}
{assign var="layout1" value="<br />"}
{assign var="layout2" value="<br />"}
{assign var="layout3" value="<br />"}

{if $gsmarty_spec_cfg->steps_results_layout == 'horizontal'}
	{assign var="layout1" value='<br /><table width="100%"><tr><td width="50%">'}
	{assign var="layout2" value='</td><td width="50%">'}
	{assign var="layout3" value="</td></tr></table><br />"}
{/if}
{* ---------------------------------------------------------------- *}

	<p />
	<div class="labelHolder"><label for="testcase_name">{$labels.tc_title}</label></div>
	<div>	
		<input type="text" name="testcase_name" id="testcase_name"
			size="{#TESTCASE_NAME_SIZE#}" 
			maxlength="{#TESTCASE_NAME_MAXLEN#}"
			onchange="IGNORE_UNLOAD = false"
			onkeypress="IGNORE_UNLOAD = false"
			{if isset($tc.name)}
		       value="{$tc.name|escape}"
			{else}
		   		value=""
		   	{/if}
			title="{$labels.alt_add_tc_name}"/>
  			{include file="error_icon.tpl" field="testcase_name"}
		<p />

		<div class="labelHolder">{$labels.summary}</div>
		<div>{$summary}</div>

	  {* Custom fields - with before steps & results location - 20090718 - franciscom *}
    <br />
	  {if $cf.before_steps_results neq ""}
	       <br/>
	       <div id="cfields_design_time_before" class="custom_field_container">
	       {$cf.before_steps_results}
	       </div>
	       
	  {/if}
		{$layout1}

		<div class="labelHolder">{$labels.steps}</div>
		<div>{$steps}</div>
		{$layout2}
		<div class="labelHolder">{$labels.expected_results}</div>
		<div>{$expected_results}</div>
		{$layout3}

		{if $session['testprojectOptAutomation']}
			<div class="labelHolder">{$labels.execution_type}
			<select name="exec_type" onchange="IGNORE_UNLOAD = false">
    	  	{html_options options=$execution_types selected=$tc.execution_type}
	    	</select>
			</div>
    	{/if}

	    {if $session['testprojectOptPriority']}
		   	<div>
			<span class="labelHolder">{$labels.test_importance}</span>
			<select name="importance" onchange="IGNORE_UNLOAD = false">
    	  	{html_options options=$gsmarty_option_importance selected=$tc.importance}
	    	</select>
			</div>
		{/if}
    	
    </div>

	{* Custom fields - with standard location - 20090718 - franciscom *}
	{if $cf.standard_location neq ""}
	     <br/>
	     <div id="cfields_design_time" class="custom_field_container">
	     {$cf.standard_location}
	     </div>
	{/if}

	<div>
	<a href={$gsmarty_href_keywordsView}>{$labels.tc_keywords}</a>
	{include file="opt_transfer.inc.tpl" option_transfer=$opt_cfg}
	</div>
	
	{if $gui->opt_requirements==TRUE && $gui->grants->requirement_mgmt=='yes' && isset($tc.testcase_id)}
		<div>
		<a href="javascript:openReqWindow({$tc.testcase_id})">{$labels.assign_requirements}</a>    
		</div>
	{/if}