{* 
Testlink Open Source Project - http://testlink.sourceforge.net/
$Id: usersview.tpl,v 1.19 2007/10/11 08:59:15 franciscom Exp $

Purpose: smarty template - users overview

rev :
     20071007 - franciscom - delete user refactoring
     20071002 - azl - BUGID 1093. 
     20070829 - jbarchibald - BUGID 1000 - Testplan User Role Assignments
     
     20070120 - franciscom - role_colour management improved
     20070106 - franciscom - added order by login and order by role
*}

{lang_get s='warning_delete_user' var="warning_msg"}

{include file="inc_head.tpl" openHead="yes"}
{include file="inc_del_onclick.tpl"}

<script type="text/javascript">
var o_label ="{lang_get s='user'}";
var del_action=fRoot+"lib/usermanagement/usersview.php?operation=delete&user=";

var warning_delete_user = "{lang_get s='warning_delete_user'}";
</script>

</head>

<body {$body_onload}>

{* 20071002 - azl - fix for bug 1093. Don't show this content if user doesn't have permissions *}
{if $mgt_users == "yes"}
	
	<h1>{lang_get s='title_user_mgmt'}</h1>
	
	{***** TABS *****}
	<div class="tabMenu">
	{if $mgt_users == "yes"}
		<span class="unselected"><a href="lib/usermanagement/usersedit.php">{lang_get s='menu_new_user'}</a></span> 
		<span class="selected">{lang_get s='menu_view_users'}</span>
	{/if}
	{if $role_management == "yes"}
		<span class="unselected"><a href="lib/usermanagement/rolesedit.php">{lang_get s='menu_define_roles'}</a></span> 
	{/if}
		<span class="unselected"><a href="lib/usermanagement/rolesview.php">{lang_get s='menu_view_roles'}</a></span> 
	{if $tproject_user_role_assignment == "yes"}
		<span class="unselected"><a href="lib/usermanagement/usersassign.php?feature=testproject">{lang_get s='menu_assign_testproject_roles'}</a></span> 
	{/if}	
	{if $tp_user_role_assignment == "yes"}
		<span class="unselected"><a href="lib/usermanagement/usersassign.php?feature=testplan">{lang_get s='menu_assign_testplan_roles'}</a></span>
	{/if}
	</div>
	
	
	{***** existing users form *****}
	<div class="workBack">
		<form method="post" action="lib/usermanagement/usersview.php" name="usersview" id="usersview">
		<input type="hidden" id="operation" name="operation" value="">
		<input type="hidden" id="order_by_role_dir" name="order_by_role_dir" value="{$order_by_role_dir}">
		<input type="hidden" id="order_by_login_dir" name="order_by_login_dir" value="{$order_by_login_dir}">
		<input type="hidden" id="user_order_by" name="user_order_by" value="{$user_order_by}">
	
	  {include file="inc_update.tpl" result=$result item="user" action="$action" user_feedback=$user_feedback}
	
		<table class="simple" width="95%">
			<tr>
				<th {if $user_order_by == 'order_by_login'}style="background-color: #c8dce8;color: black;"{/if}>
				    {lang_get s='th_login'}
				    <img src="{$smarty.const.TL_THEME_IMG_DIR}/order_{$order_by_login_dir}.gif" 
				         title="{lang_get s='order_by_login'} {lang_get s=$order_by_login_dir}"
				         onclick="usersview.operation.value='order_by_login';
				                  usersview.user_order_by.value='order_by_login'; 
				                  usersview.submit();">
				</th>
	
				<th>{lang_get s='th_first_name'}</th>
				<th>{lang_get s='th_last_name'}</th>
				<th>{lang_get s='th_email'}</th>
				
				<th {if $user_order_by == 'order_by_role'}style="background-color: #c8dce8;color: black;"{/if}>
				    {lang_get s='th_role'}
	    			<img src="{$smarty.const.TL_THEME_IMG_DIR}/order_{$order_by_role_dir}.gif" 
	    			     title="{lang_get s='order_by_role_descr'} {lang_get s=$order_by_role_dir}"
	    			     onclick="usersview.operation.value='order_by_role';
	    			              usersview.user_order_by.value='order_by_role'; 
	      			            usersview.submit();">
				</th>
				
				<th>{lang_get s='th_locale'}</th>	
				<th style="width:50px;">{lang_get s='th_active'}</th>	
				<th style="width:50px;">{lang_get s='th_delete'}</th>
			</tr>
			
			{section name=row loop=$users start=0}
				{assign var="r_d" value=$users[row].role_description}
				
			<tr {if $role_colour[$r_d] neq ''} style="background-color: {$role_colour[$r_d]};" {/if}>
				<td><a href="lib/usermanagement/usersedit.php?user_id={$users[row].id}"> 
				    {$users[row].login|escape}
			      {if $gsmarty_gui->show_icon_edit}
				      <img title="{lang_get s='alt_edit_user'}" 
				           alt="{lang_get s='alt_edit_user'}" src="{$smarty.const.TL_THEME_IMG_DIR}/icon_edit.png"/>
				    {/if}       
				    </a>
				</td>
				<td>{$users[row].first|escape}</td>
				<td>{$users[row].last|escape}</td>
				<td>{$users[row].email|escape}</td>
				<td>{$users[row].role_description|escape}</td>
				<td>
					{assign var="lc" value="$users[row]"}
					{$optLocale[$lc.locale]|escape}
				</td>
				<td>
					{if $users[row].active eq 1}
					{lang_get s='Yes'}
					{else}
					{lang_get s='No'}
					{/if}
				</td>
				<td>
				  <img style="border:none;cursor: pointer;"  
               alt="{lang_get s='alt_delete_user'}"
					     title="{lang_get s='alt_delete_user'}" 
					     onclick='delete_confirmation({$users[row].id},
					                                "{$users[row].login|escape:'javascript'}","{$warning_msg}");'
				       src="{$smarty.const.TL_THEME_IMG_DIR}/trash.png"/>
				</td>
			</tr>
			{/section}
		</table>
		</form>
	</div>
	
	{*  BUGID 0000103: Localization is changed but not strings *}
	{if $update_title_bar == 1}
	{literal}
	<script type="text/javascript">
		parent.titlebar.location.reload();
	</script>
	{/literal}
	{/if}
	{if $reload == 1}
	{literal}
	<script type="text/javascript">
		top.location.reload();
	</script>
	{/literal}
	{/if}
{else}
	{lang_get s='no_permissions_for_action'}<br />
	<a href="{$base_href}" alt="Home">Home</a>
{/if}
</body>
</html>