{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: planAddTCNavigator.tpl,v 1.8 2008/06/23 06:23:33 franciscom Exp $
show test specification tree 

rev:20080622 - franciscom - ext js tree support
    20080429 - franciscom - keyword filter multiselect
*}

{lang_get var="labels" 
          s='keywords_filter_help,
             btn_update_menu,title_navigator,keyword,test_plan,keyword,caption_nav_filter_settings'}

{assign var="keywordsFilterDisplayStyle" value=""}
{if $gui->keywordsFilterItemQty == 0}
    {assign var="keywordsFilterDisplayStyle" value="display:none;"}
{/if}

{if $tlCfg->treemenu_type == 'EXTJS'}
    {include file="inc_head.tpl" openHead="yes"}
    {include file="inc_ext_js.tpl"}

    {literal}
    <script type="text/javascript">
    treeCfg = {tree_div_id:'tree',root_name:"",root_id:0,root_href:"",
               loader:"", enableDD:false, dragDropBackEndUrl:''};
    </script>
    {/literal}
    
    <script type="text/javascript">
    treeCfg.loader='{$gui->ajaxTree->loader}';
    treeCfg.root_name='{$gui->ajaxTree->root_node->name}';
    treeCfg.root_id={$gui->ajaxTree->root_node->id};
    treeCfg.root_href='{$gui->ajaxTree->root_node->href}';
    
    // Not allowed in this feature
    // treeCfg.enableDD='{$gui->ajaxTree->dragDrop->enabled}';
    // treeCfg.dragDropBackEndUrl='{$gui->ajaxTree->dragDrop->BackEndUrl}';
    </script>
    
    <script type="text/javascript" src='gui/javascript/tcTree.js'>
    </script>


{else}
    {include file="inc_head.tpl" jsTree="yes" openHead="yes"}
{/if}

<script type="text/javascript">
{literal}
function pre_submit()
{
	document.getElementById('called_url').value=parent.workframe.location;
	return true;
}
</script>
{/literal}
</head>
<body>

<h1 class="title">{$labels.title_navigator}</h1>
<div style="margin: 3px;">
<form method="post" id="planAddTCNavigator" onSubmit="javascript:return pre_submit();">
	<input type="hidden" id="called_by_me" name="called_by_me" value="1" />
 	<input type="hidden" id="called_url" name="called_url" value="" />

	<table class="smallGrey" width="100%">
		<caption>
			{$labels.caption_nav_filter_settings}
			{include file="inc_help.tpl" helptopic="hlp_executeFilter"}
		</caption>
		<tr>
			<td>{$labels.test_plan}</td>
			<td>
				<select name="tplan_id" onchange="pre_submit();this.form.submit()">
			    {html_options options=$gui->map_tplans selected=$gui->tplan_id}
				</select>
			</td>
		</tr>
		<tr style="{$keywordsFilterDisplayStyle}">
			<td>{$labels.keyword}</td>
			<td><select name="keyword_id[]" title="{$labels.keywords_filter_help}"
			            multiple="multiple" size={$gui->keywordsFilterItemQty}>
			    {html_options options=$gui->keywords_map selected=$gui->keyword_id}
				</select>
			</td>
			<td>
     			 {html_radios name='keywordsFilterType' 
                   	options=$gui->keywordsFilterType->options
                   	selected=$gui->keywordsFilterType->selected }
			</td>
		</tr>
		<tr>
			<td>
				<input type="submit" value="{$labels.btn_update_menu}" name="doUpdateTree" />
			</td>
		</tr>
	</table>
</form>
</div>

{* 20080622 - franciscom *}
{if $tlCfg->treemenu_type == 'EXTJS'}
    <div id="tree" style="overflow:auto; height:300px;width:250px;border:1px solid #c3daf9;"></div>
{else}
   <div class="tree" id="tree">
   	{$gui->tree}
   </div>
{/if}


{* 20061030 - update the right pane *}
<script type="text/javascript">
{if $gui->src_workframe != ''}
	parent.workframe.location='{$gui->src_workframe}';
{else}
	{if $gui->do_reload}
		  parent.workframe.location.reload();
	{/if}
{/if}
</script>
</body>
</html>