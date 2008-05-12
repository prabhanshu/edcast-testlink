<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later. 
 *  
 * @filesource $RCSfile: reqView.php,v $
 * @version $Revision: 1.9 $
 * @modified $Date: 2008/05/12 19:46:59 $ by $Author: franciscom $
 * @author Martin Havlat
 * 
 * Screen to view content of requirement.
 *
 * rev: 20080512 - franciscom - added showReqSpecTitle
 */
require_once('../../config.inc.php');
require_once('common.php');
require_once('attachments.inc.php');
require_once('requirements.inc.php');
require_once('requirement_mgr.class.php');
require_once('users.inc.php');
testlinkInitPage($db);

$templateCfg = templateConfiguration();
$req_mgr = new requirement_mgr($db);

$gui=new stdClass();
$gui->grants=new stdClass();
$gui->grants->req_mgmt = has_rights($db,"mgt_modify_req");

$gui->req_id = isset($_REQUEST['requirement_id']) ? intval($_REQUEST['requirement_id']) : null;
$gui->req = $req_mgr->get_by_id($gui->req_id);

$gui->showReqSpecTitle = isset($_REQUEST['showReqSpecTitle']) ? intval($_REQUEST['showReqSpecTitle']) : 0;
if( $gui->showReqSpecTitle )
{
    $gui->parent_descr = lang_get('req_spec') . TITLE_SEP . $gui->req['req_spec_title'];
}

$gui->req['coverage'] = $req_mgr->get_coverage($gui->req_id);
$gui->main_descr = lang_get('req') . TITLE_SEP . $gui->req['title'];
$gui->cfields = $req_mgr->html_table_of_custom_field_values($gui->req_id);
$gui->attachments = getAttachmentInfosFrom($req_mgr,$gui->req_id);
$gui->reqStatus=init_labels(config_get('req_status'));

$smarty = new TLSmarty();
$smarty->assign('gui',$gui);
$smarty->display($templateCfg->template_dir . $templateCfg->default_template);
?>