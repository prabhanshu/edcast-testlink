{* 
Testlink Open Source Project - http://testlink.sourceforge.net/
$Id: usersView.tpl,v 1.7 2008/02/12 08:08:34 franciscom Exp $

Purpose: smarty template - users overview
*}
{include file="inc_head.tpl" openHead="yes"}
{include file="inc_del_onclick.tpl"}

{lang_get s='warning_delete_user' var="warning_msg"}
{lang_get s='delete' var="del_msgbox_title" }
<script type="text/javascript">
	var del_action=fRoot+"lib/usermanagement/usersView.php?operation=delete&user=";
</script>
</head>

{assign var="action_create_user" value="lib/usermanagement/usersEdit.php?doAction=create"}
{assign var="action_assign_users" value="lib/usermanagement/usersAssign.php?feature=testproject"}
{assign var="action_edit_user" value="lib/usermanagement/usersEdit.php?doAction=edit&user_id="}

{lang_get var="labels"
          s="title_user_mgmt,th_login,title_user_mgmt,th_login,th_first_name,th_last_name,th_email,
             th_role,order_by_role_descr,order_by_role_dir,th_locale,th_active,th_api,th_delete,
             alt_edit_user,Yes,No,alt_delete_user,no_permissions_for_action,
             order_by_login,order_by_login_dir"}

<body {$body_onload}>

{if $mgt_users == "yes"}
	
	<h1>{$labels.title_user_mgmt}</h1>
	{***** TABS *****}
  {include file="usermanagement/tabsmenu.tpl"}
	
	{***** existing users form *****}
	<div class="workBack">
		<form method="post" action="lib/usermanagement/usersView.php" name="usersview" id="usersview">
		<input type="hidden" id="operation" name="operation" value="" />
		<input type="hidden" id="order_by_role_dir" name="order_by_role_dir" value="{$order_by_role_dir}" />
		<input type="hidden" id="order_by_login_dir" name="order_by_login_dir" value="{$order_by_login_dir}" />
		<input type="hidden" id="user_order_by" name="user_order_by" value="{$user_order_by}" />
	
	  {include file="inc_update.tpl" result=$result item="user" action="$action" user_feedback=$user_feedback}
	
		<table class="simple" width="95%">
			<tr>
				<th {if $user_order_by == 'order_by_login'}style="background-color: #c8dce8;color: black;"{/if}>
				    {$labels.th_login}
				    <img src="{$smarty.const.TL_THEME_IMG_DIR}/order_{$order_by_login_dir}.gif" 
				         title="{$labels.order_by_login} {lang_get s=$order_by_login_dir}"
						     alt="{$labels.order_by_role_descr} {lang_get s=$order_by_role_dir}"
				         onclick="usersview.operation.value='order_by_login';
				                  usersview.user_order_by.value='order_by_login'; 
				                  usersview.submit();" />
				</th>
	
				<th>{$labels.th_first_name}</th>
				<th>{$labels.th_last_name}</th>
				<th>{$labels.th_email}</th>
				
				<th {if $user_order_by == 'order_by_role'}style="background-color: #c8dce8;color: black;"{/if}>
				    {$labels.th_role}
	    			<img src="{$smarty.const.TL_THEME_IMG_DIR}/order_{$order_by_role_dir}.gif" 
	    			     title="{$labels.order_by_role_descr} {lang_get s=$order_by_role_dir}"
						 alt="{$labels.order_by_role_descr} {lang_get s=$order_by_role_dir}"
	    			     onclick="usersview.operation.value='order_by_role';
	    			              usersview.user_order_by.value='order_by_role'; 
	      			            usersview.submit();" />
				</th>
				
				<th>{$labels.th_locale}</th>	
				<th style="width:50px;">{$labels.th_active}</th>
				{if $api_ui_show eq 1}
					<th style="width:50px;">{$labels.th_api}</th>
				{/if}
				<th style="width:50px;">{$labels.th_delete}</th>
			</tr>
			
			{section name=row loop=$users start=0}
				{assign var="user" value="$users[row]"}
				{assign var="userLocale" value=$user->locale}
				{assign var="r_d" value=$user->globalRole->name}
				{assign var="userID" value=$user->dbID}

				<tr {if $role_colour[$r_d] neq ''} style="background-color: {$role_colour[$r_d]};" {/if}>
				<td><a href="{$action_edit_user}{$user->dbID}"> 
				    {$user->login|escape}
			      {if $gsmarty_gui->show_icon_edit}
				      <img title="{$labels.alt_edit_user}" 
				           alt="{$labels.alt_edit_user}" src="{$smarty.const.TL_THEME_IMG_DIR}/icon_edit.png"/>
				    {/if}       
				    </a>
				</td>
				<td>{$user->firstName|escape}</td>
				<td>{$user->lastName|escape}</td>
				<td>{$user->emailAddress|escape}</td>
				<td>{$r_d|escape}</td>
				<td>
				 {$optLocale[$userLocale]|escape}
				</td>
				<td align="center">
					{if $user->bActive eq 1}
				  		<img style="border:none" title="{$labels.alt_active_user}" 
  				                             alt="{$labels.alt_active_user}"  src="{$checked_img}"/>
  			  {else}
  				    &nbsp;        
        	{/if}
				</td>
				<td align="center">
				  <img style="border:none;cursor: pointer;"  
               alt="{$labels.alt_delete_user}"
					     title="{$labels.alt_delete_user}" 
					     onclick="delete_confirmation({$user->dbID},'{$user->login|escape:'javascript'}',
					                                  '{$del_msgbox_title}','{$warning_msg}');"
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
	{$labels.no_permissions_for_action}<br />
	<a href="{$base_href}" alt="Home">Home</a>
{/if}
</body>
</html>