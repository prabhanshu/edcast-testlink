{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: resultsReqs.tpl,v 1.5 2006/03/08 22:22:07 kevinlevy Exp $
Purpose: report REQ coverage 
Author Martin Havlat 

20051004 - fm - added print button
20051126 - scs - added escaping of spec
20051204 - mht - removed obsolete print button
20050305 - kl - fixed mantis bug 335 by refering to fields passed,failed,blocked,not_run instead of covered
*}
{include file="inc_head.tpl"}

<body>

<h1>
		<img alt="{lang_get s='help'}: {lang_get s='req_spec'}" class="help" 
	src="icons/sym_question.gif" style="float: right;"
	onclick="javascript:open_popup('{$helphref}requirementsCoverage.html');" />
	{lang_get s='title_result_req_testplan'} {$arrReqSpec[$selectedReqSpec]|escape} - {lang_get s='title_test_plan'}
</h1>

<div class="workBack">

<div class="onright">
<form method="get">{lang_get s='req_spec_change'}<br />
	<select name="idSRS" onchange="form.submit()">
		{html_options options=$arrReqSpec selected=$selectedReqSpec}
	</select>
</form>
</div>

{* METRICS *}
<table class="invisible">
<tr><td>{lang_get s='req_total_count'}</td><td>{$arrMetrics.expectedTotal}</td></tr>
<tr><td>{lang_get s='req_title_covered'}</td><td>{$arrMetrics.coveredByTestPlan}</td></tr>
<tr><td>{lang_get s='req_title_uncovered'}</td><td>{$arrMetrics.uncoveredByTestPlan}</td></tr>
<tr><td>{lang_get s='req_title_nottestable'}</td><td>{$arrMetrics.notTestable}</td></tr>
</table>

</div>


<div class="workBack">
<h2>{lang_get s='req_title_passed'}</h2>

{section name=row loop=$arrCoverage.passed}
{if $smarty.section.row.first}
<table class="simple">
	<tr>
		<th>{lang_get s="req"}</th>
		<th>{lang_get s="testcases"}</th>
	</tr>
{/if}
	<tr>
		<td><span class="bold"><a href="lib/req/reqSpecView.php?editReq={$arrCoverage.passed[row].id}&idSRS={$selectedReqSpec}">
			{$arrCoverage.passed[row].title|escape}</a></span></td>
		<td>{$arrCoverage.passed[row].tcList}</td>
	</tr>
{if $smarty.section.row.last}
</table>
{/if}
{sectionelse}
	<p class="bold">{lang_get s='none'}</p>
{/section}
</div>



<div class="workBack">
<h2>{lang_get s='req_title_failed'}</h2>

{section name=row loop=$arrCoverage.failed}
{if $smarty.section.row.first}
<table class="simple">
	<tr>
		<th>{lang_get s="req"}</th>
		<th>{lang_get s="testcases"}</th>
	</tr>
{/if}
	<tr>
		<td><span class="bold"><a href="lib/req/reqSpecView.php?editReq={$arrCoverage.failed[row].id}&idSRS={$selectedReqSpec}">
			{$arrCoverage.failed[row].title|escape}</a></span></td>
		<td>{$arrCoverage.failed[row].tcList}</td>
	</tr>
{if $smarty.section.row.last}
</table>
{/if}
{sectionelse}
	<p class="bold">{lang_get s='none'}</p>
{/section}
</div>



<div class="workBack">
<h2>{lang_get s='req_title_blocked'}</h2>

{section name=row loop=$arrCoverage.blocked}
{if $smarty.section.row.first}
<table class="simple">
	<tr>
		<th>{lang_get s="req"}</th>
		<th>{lang_get s="testcases"}</th>
	</tr>
{/if}
	<tr>
		<td><span class="bold"><a href="lib/req/reqSpecView.php?editReq={$arrCoverage.blocked[row].id}&idSRS={$selectedReqSpec}">
			{$arrCoverage.blocked[row].title|escape}</a></span></td>
		<td>{$arrCoverage.blocked[row].tcList}</td>
	</tr>
{if $smarty.section.row.last}
</table>
{/if}
{sectionelse}
	<p class="bold">{lang_get s='none'}</p>
{/section}
</div>



<div class="workBack">
<h2>{lang_get s='req_title_notrun'}</h2>

{section name=row loop=$arrCoverage.not_run}
{if $smarty.section.row.first}
<table class="simple">
	<tr>
		<th>{lang_get s="req"}</th>
		<th>{lang_get s="testcases"}</th>
	</tr>
{/if}
	<tr>
		<td><span class="bold"><a href="lib/req/reqSpecView.php?editReq={$arrCoverage.not_run[row].id}&idSRS={$selectedReqSpec}">
			{$arrCoverage.not_run[row].title|escape}</a></span></td>
		<td>{$arrCoverage.not_run[row].tcList}</td>
	</tr>
{if $smarty.section.row.last}
</table>
{/if}
{sectionelse}
	<p class="bold">{lang_get s='none'}</p>
{/section}
</div>

</body>
</html>