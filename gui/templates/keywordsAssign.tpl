{* 
TestLink Open Source Project - http://testlink.sourceforge.net/ 
$Id: keywordsAssign.tpl,v 1.5 2006/04/10 09:07:39 franciscom Exp $
Purpose: smarty template - assign keywords to one or more test cases
Andreas Morsing : changed action to updated 
*}
{include file="inc_head.tpl" openHead='yes'}
<script language="JavaScript" src="gui/javascript/OptionTransfer.js" type="text/javascript"></script>
<script language="JavaScript" src="gui/javascript/expandAndCollapseFunctions.js" type="text/javascript"></script>

<script language="JavaScript">
var {$opt_cfg->js_ot_name} = new OptionTransfer("{$opt_cfg->from->name}","{$opt_cfg->to->name}");
{$opt_cfg->js_ot_name}.saveRemovedLeftOptions("{$opt_cfg->js_ot_name}_removedLeft");
{$opt_cfg->js_ot_name}.saveRemovedRightOptions("{$opt_cfg->js_ot_name}_removedRight");
{$opt_cfg->js_ot_name}.saveAddedLeftOptions("{$opt_cfg->js_ot_name}_addedLeft");
{$opt_cfg->js_ot_name}.saveAddedRightOptions("{$opt_cfg->js_ot_name}_addedRight");
{$opt_cfg->js_ot_name}.saveNewLeftOptions("{$opt_cfg->js_ot_name}_newLeft");
{$opt_cfg->js_ot_name}.saveNewRightOptions("{$opt_cfg->js_ot_name}_newRight");
</script>
</head>

<body onLoad="{$opt_cfg->js_ot_name}.init(document.forms[0])">

<div class="workBack">

    <h1>{lang_get s='title_keywords'}</h1>
    {* tabs *}
    <div class="tabMenu">
    	<span class="unselected"><a href="lib/keywords/keywordsView.php"
    			target='mainframe'>{lang_get s='menu_manage_keywords'}</a></span> 
    	<span class="selected">{lang_get s='menu_assign_kw_to_tc'}</span> 
    </div>
    
    {include file="inc_update.tpl" result=$sqlResult item=$level action='updated'}
    
    
    {* data form *}
    <div style="margin-top: 25px;">
    	<form method="post" action="lib/keywords/keywordsAssign.php?data={$data}&edit={$level}">
      {* 20060409 - franciscom *}
      {include file="opt_transfer.inc.tpl" option_transfer=$opt_cfg}
    	<input type="submit" name="assign{$level}" value="{lang_get s='btn_assign'}" />
    	</form>
    </div>
</div>
</body>
</html>