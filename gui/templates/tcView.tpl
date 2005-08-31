{* TestLink Open Source Project - http://testlink.sourceforge.net/ *}
{* $Id: tcView.tpl,v 1.6 2005/08/31 19:21:37 schlundus Exp $ *}
{* Purpose: smarty template - view test case in test specification *}
{* Revisions:
20050828 - fm - localize_date
20050820 - fm - access $testcase by name not by ordinal layout
20050528 - fm - I18N
20050830 - MHT - Added REQs
*}

{include file="inc_head.tpl"}
<body>
<div class="workBack">


{* 20050820 - fm *}
{section name=row loop=$testcase}
<h1>{lang_get s='title_test_case'} {$testcase[row].title|escape} </h1>


{if $modify_tc_rights == "yes" }

	{include file="inc_update.tpl" result=$sqlResult item="TestCase" refresh="yes"}

	<form method="post" action="lib/testcases/tcEdit.php?&testcaseID={$testcase[row].id}">
		<input type="submit" name="editTC"   value="{lang_get s='btn_edit'}">
		<input type="submit" name="deleteTC" value="{lang_get s='btn_del'}">
		<input type="submit" name="moveTC"   value="{lang_get s='btn_mv_cp'}">
	</form>
	
{/if}

	<table width="90%" class="simple" border="0">
		<tr>
			<th  colspan="2">{lang_get s='th_test_case_id'}{$testcase[row].id} :: {lang_get s='title_test_case'} {$testcase[row].title|escape}</th>
		</tr>
		<tr>
			<td class="bold" colspan="2">{lang_get s='version'} {$testcase[row].version|escape}</td>
		</tr>
		<tr >
			<td class="bold" colspan="2">{lang_get s='summary'}</td>
		</tr>
		<tr>
			<td  colspan="2">{$testcase[row].summary}</td>
		</tr>
		<tr>
			<td class="bold" width="50%">{lang_get s='steps'}</td>
			<td class="bold" width="50%">{lang_get s='expected_results'}</td>
		</tr>
		<tr>
			<td>{$testcase[row].steps}</td>
			<td>{$testcase[row].exresult}</td>
		</tr>
		<tr>
			<td  colspan="2"><a href="lib/keywords/keywordsView.php" 
				target="mainframe" class="bold">{lang_get s='keywords'}</a> {$testcase[row].keywords|escape}
			</td>
		</tr>
	{if $opt_requirements == TRUE && $view_req_rights == "yes"}
		<tr>
			<td>{lang_get s='Requirements'}</td>
			<td>{section name=item loop=$arrReqs}
				{$arrReqs[item].title|escape}<br />
			{sectionelse}
				{lang_get s='none'}
			{/section}</td>
		</tr>
	{/if}
	</table>
	
	<div>
		<p>{lang_get s='title_created'}&nbsp;{localize_date d=$testcase[row].create_date }&nbsp;{lang_get s='by'}&nbsp;
		   {$testcase[row].author|escape}
		{if $testcase[row].reviewer ne ""}
		<br />{lang_get s='title_last_mod'}&nbsp;{localize_date d=$testcase[row].modified_date}&nbsp;{lang_get s='by'}&nbsp;
		      {$testcase[row].reviewer|escape}
		{/if}
		</p>
	</div>
{/section}

</body>
</html>