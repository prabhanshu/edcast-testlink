{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: planAddTCNavigator.tpl,v 1.27 2010/11/13 09:53:07 franciscom Exp $
f
Scope: show test specification tree for Test Plan related features
		(the name of scripts is not correct; used more)

Revisions:    
  20101113 - franciscom - BUGID 3410: Smarty 3.0 compatibility  On JS expression SPACE is NEED 
                          BEFORE Closing curly Bracket and AFTER opening curly Bracket
  20101027 - asimon - BUGID 3946: reqirement specification tree size     
  20100428 - asimon - BUGID 3301 - removed old filter/settings form/panel and replaced
                      them with new included template inc_tc_filter_panel.tpl
  20100417 - franciscom - BUGID 2498 - filter by test case spec importance
  20100410 - franciscom - BUGID 2797 - filter by test case execution type
	20080629 - franciscom - fixed missed variable bug
  20080622 - franciscom - ext js tree support
  20080429 - franciscom - keyword filter multiselect
* ------------------------------------------------------------------------ *}

{lang_get var="labels" 
          s='keywords_filter_help,btn_apply_filter,execution_type,importance,
             btn_update_menu,title_navigator,keyword,test_plan,keyword,caption_nav_filter_settings'}

{include file="inc_head.tpl" openHead="yes"}
{include file="inc_ext_js.tpl" bResetEXTCss=1}

{* BUGID 3301 *}
{* includes Ext.ux.CollapsiblePanel *}
<script type="text/javascript" src='gui/javascript/ext_extensions.js'></script>
<script type="text/javascript">
	treeCfg = { tree_div_id:'tree',root_name:"",root_id:0,root_href:"",loader:"", 
	            enableDD:false, dragDropBackEndUrl:"",children:"" };
	Ext.onReady(function() {
		Ext.state.Manager.setProvider(new Ext.state.CookieProvider());

		// Use a collapsible panel for filter settings
		// and place a help icon in ther header
		var settingsPanel = new Ext.ux.CollapsiblePanel({
			id: 'tl_exec_filter',
			applyTo: 'settings_panel',
			tools: [{
				id: 'help',
				handler: function(event, toolEl, panel) {
					show_help(help_localized_text);
				}
			}]
		});
		var filtersPanel = new Ext.ux.CollapsiblePanel({
			id: 'tl_exec_settings',
			applyTo: 'filter_panel'
		});
	});
</script>

    {if $gui->ajaxTree->loader == ''}
        <script type="text/javascript">
        treeCfg = { tree_div_id:'tree',root_name:"",root_id:0,root_href:"",
                    loader:"", enableDD:false, dragDropBackEndUrl:'',children:"" };
        </script>

        <script type="text/javascript">
        treeCfg.root_name='{$gui->ajaxTree->root_node->name|escape:'javascript'}';
        treeCfg.root_id={$gui->ajaxTree->root_node->id};
        treeCfg.root_href='{$gui->ajaxTree->root_node->href}';
        treeCfg.children={$gui->ajaxTree->children};
        </script>
        <script type="text/javascript" src='gui/javascript/execTree.js'></script>
    {else}
        <script type="text/javascript">
        treeCfg = { tree_div_id:'tree',root_name:"",root_id:0,root_href:"",
                    root_testlink_node_type:'',useBeforeMoveNode:false,
                    loader:"", enableDD:false, dragDropBackEndUrl:'' };
        </script>
        
        <script type="text/javascript">
        treeCfg.loader = "{$gui->ajaxTree->loader}";
        treeCfg.root_name = "{$gui->ajaxTree->root_node->name|escape}";
        treeCfg.root_id = {$gui->ajaxTree->root_node->id};
        treeCfg.root_href = "{$gui->ajaxTree->root_node->href}";
        treeCfg.cookiePrefix = "{$gui->ajaxTree->cookiePrefix}";
    </script>
        
        <script type="text/javascript" src="gui/javascript/treebyloader.js">
        </script>
   {/if}
<script type="text/javascript">
function pre_submit()
{
	document.getElementById('called_url').value=parent.workframe.location;
	return true;
}
</script>


{* BUGID 3301 - js include file for simpler code, filter refactoring/redesign *}
{include file='inc_filter_panel_js.tpl'}

{* 
 * !!!!! IMPORTANT !!!!!
 * Above included file closes <head> tag and opens <body>, so this is not done here.
 *}

	
<h1 class="title">{$labels.title_navigator}</h1>
<div style="margin: 3px;">

{* BUGID 3301 *}
{include file='inc_filter_panel.tpl'}

<div id="tree" style="overflow:auto; height:100%;border:1px solid #c3daf9;"></div>

{* 20061030 - update the right pane *}
<script type="text/javascript">

</script>
</body>
</html>