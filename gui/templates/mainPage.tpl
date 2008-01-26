{* 
 Testlink Open Source Project - http://testlink.sourceforge.net/ 
 $Id: mainPage.tpl,v 1.38 2008/01/26 09:31:18 franciscom Exp $     
 Purpose: smarty template - main page / site map                 
                                                                 
 rev :                                                 
       20070523 - franciscom - nifty corners
       20070113 - franciscom - truncate on test plan name combo box
       20060908 - franciscom - removed assign risk and ownership
                               added define priority
                               added tc exec assignment
                                   
       20060819 - franciscom - changed css classes name
                               removed old comments
       
*}
{include file="inc_head.tpl" popup="yes" openHead="yes"}
<script language="JavaScript" src="{$basehref}gui/niftycube/niftycube.js" type="text/javascript"></script>
{literal}
<script type="text/javascript">
window.onload=function(){
 Nifty("div.menu_bubble");
}
</script>
{/literal}

</head>

<body>
{assign var="cfg_section" value=$smarty.template|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}
{if $securityNotes}
    {include file="inc_msg_from_array.tpl" array_of_msg=$securityNotes arg_css_class="warning_message"}
{/if}

{* Right Column                  *}
{include file="mainPageRight.tpl"}

{*   left column                 *}
{include file="mainPageLeft.tpl"}

</body>
</html>