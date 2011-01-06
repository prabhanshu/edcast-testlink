{*
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: reqCompareVersions.tpl,v 1.12 2011/01/06 14:12:09 mx-julian Exp $
 
Purpose: smarty template - compare requirement versions

revisions
  20110106 - Julian - Only 1 column for last change including localized timestamp and editor
  20101215 - Julian - Changed log message tooltip width to 500 (maximum) to avoid
                      visualization errors
  20101213 - franciscom - BUGID 4056: Requirement Revisioning - tooltip added
  20101211 - franciscom - BUGID 4056: Requirement Revisioning
  20101113 - franciscom - BUGID 3410: Smarty 3.0 compatibility  

*}

{include file="inc_head.tpl" openHead='yes' jsValidate="yes"}
{include file="inc_del_onclick.tpl"}

{lang_get var="labels"
          s="select_versions,title_compare_versions_req,version,compare,modified,modified_by,
          btn_compare_selected_versions, context, show_all,author,timestamp,timestamp_lastchange,
          warning_context, warning_context_range, warning_empty_context,warning,custom_field, 
          warning_selected_versions, warning_same_selected_versions,revision,attribute,
          custom_fields,attributes,log_message"}

<link rel="stylesheet" type="text/css" href="{$basehref}third_party/diff/diff.css">

<script type="text/javascript">
//BUGID 3943: Escape all messages (string)
var alert_box_title = "{$labels.warning|escape:'javascript'}";
var warning_empty_context = "{$labels.warning_empty_context|escape:'javascript'}";
var warning_context_range = "{$labels.warning_context_range|escape:'javascript'}";
var warning_selected_versions = "{$labels.warning_selected_versions|escape:'javascript'}";
var warning_same_selected_versions = "{$labels.warning_same_selected_versions|escape:'javascript'}";
var warning_context = "{$labels.warning_context|escape:'javascript'}";

/**
 * 
 *
 */
function tip4log(itemID)
{
	var fUrl = fRoot+'lib/ajax/getreqlog.php?item_id=';
	new Ext.ToolTip({
        target: 'tooltip-'+itemID,
        width: 500,
        autoLoad:{ url: fUrl+itemID },
        dismissDelay: 0,
        trackMouse: true
    });
}

Ext.onReady(function(){ 
{foreach from=$gui->items key=idx item=info}
  tip4log({$info.item_id});
{/foreach}
});

function triggerTextfield(field)
{
	if (field.disabled == true) {
    	field.disabled = false;
	} else {
    	field.disabled = true;
	}
}

function valButton(btn) {
    var cnt = -1;
    for (var i=btn.length-1; i > -1; i--) {
        if (btn[i].checked) {
        	cnt = i;
        	i = -1;
        }
    }
    if (cnt > -1) {
    	return true;
    }
    else {
    	return false;
    }
}

function validateForm() {
	if (isWhitespace(document.req_compare_versions.context.value)) {
	    alert_message(alert_box_title,warning_empty_context);
		return false;
	} else {
		value = parseInt(document.req_compare_versions.context.value);
		if (isNaN(value))
		{
		   	alert_message(alert_box_title,warning_context);
		   	return false;
		} else if (value < 0) {
			alert_message(alert_box_title,warning_context_range);
		   	return false;
		}
	}
	
	if (!valButton(document.req_compare_versions.version_left)
			|| !valButton(document.req_compare_versions.version_right)) {
		alert_message(alert_box_title,warning_selected_versions);
		return false;
	}
	
	for (var i=document.req_compare_versions.version_left.length-1; i > -1; i--) {
        if (document.req_compare_versions.version_left[i].checked && document.req_compare_versions.version_right[i].checked) {
        	alert_message(alert_box_title,warning_same_selected_versions);
        	return false;
        }
    }
}

</script>

</head>
<body>

{if $gui->compare_selected_versions}

	<h1 class="title">{$labels.title_compare_versions_req}</h1> 
			
		<div class="workBack" style="width:99%; overflow:auto;">	
	{$gui->subtitle}
    {if $gui->attrDiff != ''}
      <h2>{$labels.attributes}</h2>
      <table border="1" cellspacing="0" cellpadding="2" style="width:60%" class="code">
        <thead>
          <tr>
            <th style="text-align:left;">{$labels.attribute}</th>
            <th style="text-align:left;">{$gui->leftID}</th>
            <th style="text-align:left;">{$gui->rightID}</th>
          </tr>
        </thead>
        <tbody>
	      {foreach item=attrDiff from=$gui->attrDiff}
          <tr>
            <td class="{if $attrDiff.changed}del{else}ins{/if}"; style="font-weight:bold">{$attrDiff.label}</td>
            <td class="{if $attrDiff.changed}del{else}ins{/if}";>{$attrDiff.lvalue}</td>
            <td class="{if $attrDiff.changed}del{else}ins{/if}";>{$attrDiff.rvalue}</td>
          </tr>
        {/foreach}
        </tbody>
      </table>
      <p />
    {/if}
		
	  {foreach item=diff from=$gui->diff}
		<h2>{$diff.heading}</h2>
		<fieldset class="x-fieldset x-form-label-left" >
		<legend class="legend_container" >{$diff.message}</legend>
	  	  {if $diff.count > 0}{$diff.diff}{/if}
	  	  </fieldset>
	  {/foreach}
    {if $gui->cfieldsDiff != ''}
      <p />
      <h2>{$labels.custom_fields}</h2>
      <table border="1" cellspacing="0" cellpadding="2" style="width:60%" class="code">
        <thead>
        <tr>
          <th style="text-align:left;">{$labels.custom_field}</th>
          <th style="text-align:left;">{$gui->leftID}</th>
          <th style="text-align:left;">{$gui->rightID}</th>
        </tr>
        </thead>
        <tbody>
	      {foreach item=cfDiff from=$gui->cfieldsDiff}
          <tr>
            <td class="{if $cfDiff.changed}del{else}ins{/if}"; style="font-weight:bold">{$cfDiff.label}</td>
            <td class="{if $cfDiff.changed}del{else}ins{/if}";>{$cfDiff.lvalue}</td>
            <td class="{if $cfDiff.changed}del{else}ins{/if}";>{$cfDiff.rvalue}</td>
          </tr>
        {/foreach}
        </tbody>
      </table>
		{/if}
		</div>
		
{else}

	<h1 class="title">{$labels.title_compare_versions_req}</h1> 
	
	<div class="workBack" style="width:97%;">
	
	<form target="diffwindow" method="post" action="lib/requirements/reqCompareVersions.php" name="req_compare_versions" id="req_compare_versions"  
			onsubmit="return validateForm();" />			
	
	<p><input onClick="test();" type="submit" name="compare_selected_versions" value="{$labels.btn_compare_selected_versions}" /></p><br/>
	
	<table border="0" cellspacing="0" cellpadding="2" style="font-size:small;" width="100%">
	
	    <tr style="background-color:blue;font-weight:bold;color:white">
	        <th width="12px" style="font-weight: bold; text-align: center;">{$labels.version}</td>
	        <th width="12px" style="font-weight: bold; text-align: center;">{$labels.revision}</td>
	        <th width="12px" style="font-weight: bold; text-align: center;">&nbsp;{$labels.compare}</td>
	        <th style="font-weight: bold; text-align: center;">{$labels.log_message}</td>
	        <th style="font-weight: bold; text-align: center;">{$labels.timestamp_lastchange}</td>
	    </tr>
	
	{counter assign="mycount"}
	{foreach item=req from=$gui->items}
	   <tr>
	        <td style="text-align: center;">{$req.version}</td>
	        <td style="text-align: center;">{$req.revision}</td>
	        <td style="text-align: center;"><input type="radio" name="left_item_id" value="{$req.item_id}" 
	            {if $mycount == 2} 	 checked="checked"  {/if} />
	            <input type="radio" name="right_item_id" value="{$req.item_id}" {if $mycount == 1} checked="checked"	{/if}/>
	        </td>
        	{* using EXT-JS logic to open div to show info when mouse over *}
	        <td id="tooltip-{$req.item_id}">
        	{$req.log_message}
        	</td>
        	<td style="text-align: left; cursor: pointer; color: rgb(0, 85, 153);" onclick="javascript:openReqRevisionWindow({$req.item_id});">
	            <nobr>{localize_timestamp ts = $req.timestamp}, {$req.last_editor}</nobr>
	        </td>
	    </tr>
	{counter}
	{/foreach}
	
	</table><br/>
	
	<p>{$labels.context} <input type="text" name="context" id="context" maxlength="4" size="4" value="{$gui->context}" />
	<input type="checkbox" id="context_show_all" name="context_show_all" 
	onclick="triggerTextfield(this.form.context);"/> {$labels.show_all} </p><br/>
	
	<p><input type="hidden" name="requirement_id" value="{$gui->req_id}" />
	<input type="submit" name="compare_selected_versions" value="{$labels.btn_compare_selected_versions}" /></p>
	
	</form>

	</div>

{/if}

</body>

</html>
