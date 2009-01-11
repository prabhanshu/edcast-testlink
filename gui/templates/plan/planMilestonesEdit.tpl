{lang_get var='labels' s='show_event_history,warning_empty_milestone_name,
                          warning_empty_low_priority_tcases,warning_empty_medium_priority_tcases,
                          warning_empty_high_priority_tcases,info_milestones_date,
                          warning_invalid_percentage_value,warning_must_be_number,
                          btn_cancel,warning,
                          th_name,th_date_format,th_perc_a_prio,th_perc_b_prio,th_perc_c_prio,
                          th_perc_testcases,th_delete,alt_delete_milestone'}

{assign var="cfg_section" value=$smarty.template|basename|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}

{* Configure Actions *}
{assign var="managerURL" value="lib/plan/planMilestonesEdit.php"}
{assign var="editAction" value="$managerURL?doAction=edit&tplan_id="}
{assign var="deleteAction" value="$managerURL?doAction=doDelete&tplan_id="}
{assign var="createAction" value="$managerURL?doAction=create&tplan_id="}


{include file="inc_head.tpl" jsValidate="yes" openHead="yes"}
{include file="inc_del_onclick.tpl"}

{literal}
<script type="text/javascript">
{/literal}
var alert_box_title = "{$labels.warning}";
var warning_invalid_percentage_value = "{$labels.warning_invalid_percentage_value}";
var warning_must_be_number = "{$labels.warning_must_be_number}";

var warning_empty = new Object;
warning_empty.milestone_name  = "{$labels.warning_empty_milestone_name}";
warning_empty.low_priority_tcases = "{$labels.warning_empty_low_priority_tcases}";
warning_empty.medium_priority_tcases = "{$labels.warning_empty_medium_priority_tcases}";
warning_empty.high_priority_tcases = "{$labels.warning_empty_high_priority_tcases}";



var warning_nonumeric_low_priority_tcases = 'no numeric';
{literal}

/*
  function: validateForm
            validate form inputs, doing several checks like:
            - fields that can not be empty

            if some check fails:
            1. an alert message is displayed
            2. background color of offending field is changed.

  args : f: form object

  returns: true  -> all checks ok
           false -> when a check fails
*/

function validateForm(f)
{
  var numeric_check = /[^\d]/;
  var idx;
  var obj;
  var dummy;

  // Very Important: name and id must be the same for these HTML field
  var fields2check = new Array('low_priority_tcases','medium_priority_tcases','high_priority_tcases');
    
  if (isWhitespace(f.milestone_name.value))
  {
      alert_message(alert_box_title,warning_empty.milestone_name);
      selectField(f, 'milestone_name');
      return false;
  }

  for(idx=0 ; idx <= fields2check.length; idx++)
  {
      obj = document.getElementById(fields2check[idx]);
      if (isWhitespace(obj.value))
      {
          alert_message(alert_box_title,warning_empty[fields2check[idx]]);
          selectField(f, fields2check[idx]);
          return false;
      }

      dummy=obj.value.trim();   // IMPORTANT: trim is function provided by EXT-JS library
      if( numeric_check.test(dummy) )
      {
          alert_message(alert_box_title,warning_must_be_number);
          selectField(f, fields2check[idx]);
          return false;
      }
   
      if( dummy < 0 || dummy > 100)
      {
          alert_message(alert_box_title,warning_invalid_percentage_value);
          selectField(f, fields2check[idx]);
          return false;
      }
  }

}
{/literal}
</script>

</head>

<body class="testlink">

<div class="workBack">
  {include file="inc_update.tpl" user_feedback=$gui->user_feedback}
	<h2>
	{$gui->action_descr|escape}
	{if $gui->milestone.id > 0}
		{if $gui->grants->mgt_view_events eq "yes"}
			<img style="margin-left:5px;" class="clickable" src="{$smarty.const.TL_THEME_IMG_DIR}/question.gif" 
					onclick="showEventHistoryFor('{$gui->milestone.id}','milestones')" 
					alt="{$labels.show_event_history}" title="{$labels.show_event_history}"/>
		{/if}
	{/if}
	</h2>

	<form method="post" action="lib/plan/planMilestonesEdit.php"
	      name="milestone_mgr" onSubmit="javascript:return validateForm(this);">
	
	    <input type="hidden" name="id" value="{$gui->milestone.id}"/>
	    <table class="common" style="width:80%">
		      <tr>
			    <th style="background:none;">{$labels.th_name}</th>
	        		<td>
	        			<input type="text" id="milestone_name" name="milestone_name" size="{#MILESTONE_NAME_SIZE#}"
                	  	 maxlength="{#MILESTONE_NAME_MAXLEN#}"  value="{$gui->milestone.name|escape}"/>
	              {include file="error_icon.tpl" field="milestone_name"}
	        		</td>
    	    </tr>
    	    
 	    		<tr>
			    <th style="background:none;">{$labels.th_date_format}</th>
			        <td>
	           {assign var="selected_date" value=""}
             {if $gui->milestone != null}
              {assign var="selected_date" value=$gui->milestone.target_date}
             {/if}
             {html_select_date prefix="target_date_"  time=$selected_date
                               month_format='%m' end_year="+1"
                               day_value_format="%02d"
                               field_order=$gsmarty_html_select_date_field_order}
             		<span class="italic">{$labels.info_milestones_date}</span>
		      	</td>
		      </tr>

          {if $session['testprojectOptPriority']}
		          <tr>
		          	<th style="background:none;">{$labels.th_perc_a_prio}:</th>
		          	<td>
		          		<input type="text" id="low_priority_tcases" name="low_priority_tcases" 
		          		       size="{#PRIORITY_SIZE#}" maxlength="{#PRIORITY_MAXLEN#}" 
		          		       value="{$gui->milestone.A|escape}"/>
	                {include file="error_icon.tpl" field="low_priority_tcases"}
		          	</td>
		          </tr>
		          <tr>
		          	<th style="background:none;">{$labels.th_perc_b_prio}:</th>
		          	<td>
		          		<input type="text" name="medium_priority_tcases" id="medium_priority_tcases" 
		          		       size="{#PRIORITY_SIZE#}" maxlength="{#PRIORITY_MAXLEN#}" 
		          		       value="{$gui->milestone.B|escape}"/>
	                {include file="error_icon.tpl" field="medium_priority_tcases"}
		          	</td>
		          </tr>
		          <tr>
		          	<th style="background:none;">{$labels.th_perc_c_prio}:</th>
		          	<td>
		          		<input type="text" name="high_priority_tcases" id="high_priority_tcases" 
		          		       size="{#PRIORITY_SIZE#}" maxlength="{#PRIORITY_MAXLEN#}" 
		          		       value="{$gui->milestone.C|escape}"/>
	                {include file="error_icon.tpl" field="high_priority_tcases"}
		          	</td>
		          </tr>
		       
		      {else}
		      		<tr>
			        	<th style="background:none;">{$labels.th_perc_testcases}:</th>
			          <td>
			          	<input type="hidden" name="low_priority_tcases" id="low_priority_tcases" value="0"/>
			          	<input type="hidden" name="high_priority_tcases" id="high_priority_tcases" value="0"/>
			          	<input type="text" name="medium_priority_tcases" id="medium_priority_tcases" 
			          	       size="{#PRIORITY_SIZE#}"  maxlength="{#PRIORITY_MAXLEN#}" 
			          	       value="{$gui->milestone.b|escape}"/>
			          </td>
		         </tr>
          {/if}
      </table>


	<div class="groupBtn">
		<input type="hidden" id="doAction" name="doAction" value="" />
		<input type="submit" id="create" name="create" value="{$gui->submit_button_label}"
	         onclick="doAction.value='{$gui->operation}'" />
		<input type="button" id="go_back" name="go_back" value="{$labels.btn_cancel}" 
			     onclick="javascript: history.back();"/>
	</div>
</div>
</form>
  

</body>
</html>
