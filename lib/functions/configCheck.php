<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: configCheck.php,v ${file_name} $
 *
 * @version $Revision: 1.4 $
 * @modified $Date: 2006/04/24 10:38:01 ${date} ${time} $ by $Author: franciscom $
 *
 * @author Martin Havlat
 * 
 * Check configuration in login and index pages.
 * 20060103 - scs - ADOdb changes
 **/
// ---------------------------------------------------------------------------------------------------
/** check if we need to run the install program */
function checkConfiguration()
{
	clearstatcache();
	$file_to_check = "config_db.inc.php";

	if(!is_file($file_to_check))
	{
		echo "<html><body>Fatal Error. You haven't configured TestLink yet.<br/><a href='./install/index.php'>
			Click Here To Start Installation/Setup!</a></body></html>";
		exit();
	}
}

/**
 * checks if the install dir is present
 *
 * @return bool returns true if the install dir is present, false else
 *
 * @version 1.0
 * @author Andreas Morsing 
 **/
function checkForInstallDir()
{
	// 20050823
	$installer_dir = TL_ABS_PATH. DS . "install"  . DS;
	clearstatcache();
	$bPresent = false;
	if(is_dir($installer_dir))
		$bPresent = true;
	
	return $bPresent;	
}

/**
 * checks if the default password for the admin accout is still set
 *
 * @return bool returns true if the default password for the admin account is set, 
 * 				false else
 *
 * @version 1.0
 * @author Andreas Morsing 
 **/
function checkForAdminDefaultPwd(&$db)
{
	$userInfo = null;
	$bDefaultPwd = false;
	if (existLogin($db,"admin",$userInfo) && ($userInfo['password'] == md5('admin')))
		$bDefaultPwd = true;
	
	return $bDefaultPwd;
}

/**
 * builds the security notes while checking some security issues
 * these notes should be displayed!
 *
 * @return array returns the security issues, or null if none found!
 *
 * @version 1.0
 * @author Andreas Morsing 
 *  
 **/
function getSecurityNotes(&$db)
{
	$securityNotes = null;
	if (checkForInstallDir())
		$securityNotes[] = lang_get("sec_note_remove_install_dir");

	if (checkForAdminDefaultPwd($db))
		$securityNotes[] = lang_get("sec_note_admin_default_pwd");

	// 20060413 - franciscom
	if (!checkForBTSconnection())
	{
		$securityNotes[] = lang_get("bts_connection_problems");
	}
		
	return $securityNotes;
}


/**
 * checks if the connection to the Bug Tracking System database is working
 *
 * @return bool returns true if ok
 * 				false else
 *
 * @version 1.0
 * @author franciscom 
 **/
function checkForBTSconnection()
{
	global $g_bugInterface;
	$status_ok=1;
	if($g_bugInterface)
	{
		if( !$g_bugInterface->connect() )
		{
			$status_ok=0;
		}
	}
	return($status_ok);
}


?>