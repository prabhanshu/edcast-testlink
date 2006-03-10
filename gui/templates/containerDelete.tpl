{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: containerDelete.tpl,v 1.5 2006/03/10 07:42:42 franciscom Exp $ 
Purpose: smarty template - delete containers in test specification

20060309 - franciscom
*}
{include file="inc_head.tpl"}

<body>
<div class="workBack">

{include file="inc_title.tpl" title="Delete $level $objectName"}
{include file="inc_update.tpl" result=$sqlResult item=$level refresh="yes"}

{if $sqlResult == ''}
  <h2>{lang_get s='delete_notice'}</h2>
	<p>{lang_get s='question_del'} {$level|escape}?</p>

	<form method="post" 
	      action="lib/testcases/containerEdit.php?sure=yes&objectID={$objectID|escape}">
	
	<input type="submit" name="delete_testsuite" value="{lang_get s='btn_yes_del_comp'}" />
	</form>
{/if}

</div>
</body>
</html>