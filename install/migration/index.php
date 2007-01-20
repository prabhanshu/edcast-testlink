<?php
/* 
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: index.php,v 1.3 2007/01/20 14:21:07 franciscom Exp $ 

20060218 - franciscom
*/

session_start();
$_SESSION['session_test'] = 1;

// 20060523 - franciscom - configure before creating a new release
$_SESSION['testlink_version']='1.7 beta';
$operation='Migration from 1.6.2';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Testlink <?php echo ($_SESSION['testlink_version'] . "-" . $operation) ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <style type="text/css">
             @import url('../css/style.css');
        </style>
</head>	

<body>
<table border="0" cellpadding="0" cellspacing="0" class="mainTable">
  <tr class="fancyRow">
    <td><span class="headers">&nbsp;<img src="./img/dot.gif" alt="" style="margin-top: 1px;" />&nbsp;TestLink <?php echo $_SESSION['testlink_version'] ?> </span></td>
    <td align="right"><span class="headers"><?php echo $operation ?></span></td>
  </tr>
  <tr class="fancyRow2">
    <td colspan="2" class="border-top-bottom smallText" align="right">&nbsp;</td>
  </tr>
  <tr align="left" valign="top">
    <td colspan="2"><table width="100%"  border="0" cellspacing="0" cellpadding="1">
      <tr align="left" valign="top">
        <td class="pad" id="content" colspan="2">
			<p class="headers">
      Migration Process
      </p>
      
      <ul>
      <li> Migration is supported ONLY from version 1.6.2 MySQL to 1.7.0 MySQL</li>
      <li>No changes will be made to the 1.6.2 database (source database)</li>
      <li>The following items IDs will be preserved:
          <ul>
          <li>
          Bug ID
          <li>
          Build ID
          <li>
          Keyword ID
          <li>
          Requirement ID
          <li>
          Test case ID
          <li>
          User ID
          </ul>
      </li>    
      <li>    
      Test cases added to test plans, but without corresponding Test case specification<br> 
      (i.e. the spec has been deleted) WILL BE LOST.
      </li>    
      </ul>
      <p>
      <p class="headers">
      THIS MIGRATION PROCESS IS NOT 100% AUTOMATIC.
      </p>
      <ul>
      <li><span class="headers">STEP ONE:</span> Add this page to your bookmarks or save the URL.
      <li><span class="headers">STEP TWO:</span> Go back to the main installation screen and start a New Installation.
      <li><span class="headers">STEP THREE:</span> After a successful installation, return to this page and click
			<a href="migration_start.php?installationType=<?php echo $operation?>"><b>here</b></a> to start the migration.
			</ul>
		</td>
      </tr>
    </table></td>
  </tr>
  <tr class="fancyRow2">
    <td class="border-top-bottom smallText">&nbsp;</td>
    <td class="border-top-bottom smallText" align="right">&nbsp;</td>
  </tr>
</table>
</body>
</html>