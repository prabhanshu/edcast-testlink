{* Testlink: smarty template - Edit own account *}
{* $Id: userInfo.tpl,v 1.8 2007/09/11 20:11:02 franciscom Exp $ *}
{* 
*}
{assign var="cfg_section" value="login" }
{config_load file="input_dimensions.conf" section=$cfg_section}

{include file="inc_head.tpl" jsValidate="yes"}

<body>

<h1> {lang_get s='title_account_settings'} </h1>

{include file="inc_update.tpl" result=$updateResult action="updated" item="user" name=$userData[1]}


<div class="workBack">

{* <h2>{lang_get s='title_edit_personal_data'}</h2> *}

{literal}
<script type="text/javascript">
{/literal}
var warning_empty_pwd = "{lang_get s='warning_empty_pwd'}";
var warning_different_pwd = "{lang_get s='warning_different_pwd'}";
var warning_enter_less1 = "{lang_get s='warning_enter_less1'}";
var warning_enter_at_least1 = "{lang_get s='warning_enter_at_least1'}";
var warning_enter_at_least2 = "{lang_get s='warning_enter_at_least2'}";
var warning_enter_less2 = "{lang_get s='warning_enter_less2'}";
var names_max_len={#NAMES_MAXLEN#};

{literal}
function valAllText(form)
{
	if (valTextLength(form.first,names_max_len,1) && valTextLength(form.last,names_max_len,1))
	{
		return true;
	}
	return false;
}
</script>
{/literal}

<form method="post" action="lib/usermanagement/userinfo.php" onsubmit="return valAllText(this)">
	<input type="hidden" name="id" value="{$userData.id}" />
	<table class="common">
		<tr>
			<th>{lang_get s='th_login'}</th>
			<td>{$userData.login}</td>
		</tr>
		<tr>
			<th>{lang_get s='th_first_name'}</th>
			<td><input type="text" name="first" value="{$userData.first|escape}" 
			           size="{#NAMES_SIZE#}" maxlength="{#NAMES_MAXLEN#}" /></td>
		</tr>
		<tr>
			<th>{lang_get s='th_last_name'}</th>
			<td><input type="text" name="last" value="{$userData.last|escape}" 
			           size="{#NAMES_SIZE#}" maxlength="{#NAMES_MAXLEN#}" /></td>
		</tr>
		<tr>
			<th>{lang_get s='th_email'}</th>
			<td><input type="text" name="email" value="{$userData.email|escape}" 
			           size="{#EMAIL_SIZE#}" maxlength="{#EMAIL_MAXLEN#}" /></td>
		</tr>
		<tr>
			<th>{lang_get s='th_locale'}</th>
			<td>		   
				<select name="locale">
				{html_options options=$optLocale selected=$userData.locale}
				</select>	
			</td>
		</tr>
	</table>
	<div class="groupBtn">	
		<input type="submit" name="editUser" value="{lang_get s='btn_save'}" />
	</div>
</form>
<hr />

{if $external_password_mgmt eq 0 }
{* <h2>{lang_get s='title_change_your_passwd'}</h2> *}
<form name="changePass" method="post" action="lib/usermanagement/userinfo.php" 
	onsubmit="return validatePassword(document.changePass);">
	<input type="hidden" name="id" value="{$userData.id}" />
	<table class="common">
		<tr><th>{lang_get s='th_old_passwd'}</th>
			<td><input type="password" name="old" maxlength="PASSWD_SIZE" /></td></tr>
		<tr><th>{lang_get s='th_new_passwd'}</th>
			<td><input type="password" name="new1" maxlength="PASSWD_SIZE" /></td></tr>
		<tr><th>{lang_get s='th_new_passwd_again'}</th>
			<td><input type="password" name="new2" maxlength="PASSWD_SIZE" /></td></tr>
	</table>
	<div class="groupBtn">	
		<input type="submit" name="changePasswd" value="{lang_get s='btn_change_passwd'}" />
	</div>
</form>

{else}
   <p>{lang_get s='your_password_is_external'}<p>
{/if}

</div>

{if $update_title_bar == 1}
{literal}
<script type="text/javascript">
	//parent.mainframe.location = parent.mainframe.location;
	parent.titlebar.location.reload();
</script>
{/literal}
{/if}
</body>
</html>