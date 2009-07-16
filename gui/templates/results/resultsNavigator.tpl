{* TestLink Open Source Project - http://testlink.sourceforge.net/ *}
{* $Id: resultsNavigator.tpl,v 1.7 2009/07/16 21:30:52 havlat Exp $ *}
{* Purpose: smarty template - show Test Results and Metrics *}
{* Rev :
        20081109 - franciscom - refactoring 
        20070113 - franciscom - use of smarty config file
*}
{assign var="cfg_section" value=$smarty.template|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}
{include file="inc_head.tpl" openHead="yes"}

{literal}
<script type="text/javascript">
function reportPrint(){
	parent["workframe"].focus();
	parent["workframe"].print();
}

function pre_submit()
{
 document.getElementById('called_url').value=parent.workframe.location;
 return true;
}
</script>
{/literal}
</head>

<body>

<h1 class="title">{lang_get s='title_nav_results'}</h1>

<div style="margin:0px; padding:0px;">
<form method="get" id="resultsNavigator" onSubmit="javascript:return pre_submit();">
	<input type="hidden" id="called_by_me" name="called_by_me" value="1" />
	<input type="hidden" id="called_url" name="called_url" value="" />

	<div class="menu_bar">
		<span>{lang_get s='title_report_type'}
		<select name="format" onchange="this.form.submit();">
		    {html_options options=$arrReportTypes selected=$selectedReportType}
		</select>
		</span>

		<span style="margin-left:20px;"><input type="button" name="print" value="{lang_get s='btn_print'}" 
			onclick="javascript: reportPrint();" style="margin-left:5px;" /></span>
	</div>

	<div style="margin:3px" >

		<span style="padding-right: 10px">{lang_get s='test_plan'} 
		<select name="tplan_id" onchange="pre_submit();this.form.submit()">
			{html_options options=$gui->tplans selected=$gui->tplan_id}
		</select>
		</span>
	</div>
</form>
</div>

<div style="margin:3px; padding: 15px 0px" >
{* Build href menu *}
{if $gui->do_report.status_ok }
  {foreach from=$gui->menuItems item=menu}
    <span><img src="{$smarty.const.TL_ITEM_BULLET_IMG}" />
	    <a href="{$menu.href}format={$selectedReportType}&amp;tplan_id={$gui->tplan_id}" 
	       target="workframe">{$menu.name}</a></span><br />
  
  {/foreach}
{else}
  {$gui->do_report.msg}
{/if}
</div>


<script type="text/javascript">
{if $gui->workframe != ''}
	parent.workframe.location='{$gui->workframe}';
{/if}
</script>

</body>
</html>
