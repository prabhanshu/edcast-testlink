{* TestLink Open Source Project - http://testlink.sourceforge.net/ *}
{* $Id: resultsBuild.tpl,v 1.5 2006/11/26 20:30:40 kevinlevy Exp $ *}
{* Purpose: smarty template - show Test Results of one build *}
{include file="inc_head.tpl"}
{*
	20051126 - scs - added escaping of tpname
*}

<body>

<h1>{$tpName|escape} {lang_get s='title_met_of_build'} {$buildName|escape}</h1>
<div class="workBack">

{include file="inc_res_by_prio.tpl"}
{include file="inc_res_by_comp.tpl"}
{include file="inc_res_by_ts.tpl"} 
{include file="inc_res_by_keyw.tpl"}

</div>

</body>
</html>