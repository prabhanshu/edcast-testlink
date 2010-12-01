<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * Checks if inside a container (parent_id) exists a node with specified name.
 * Used to warn user if non-unique name is entered.
 * 
 * Importante NOTICE:
 * no check on user rights is done (IMHO is ok because is a READ ONLY Operation).
 *
 * @package 	TestLink
 * @author 		Francisco Mancardi
 * @copyright 	2010, TestLink community
 * @version    	CVS: $Id: checkNodeDuplicateName.php,v 1.3 2010/12/01 14:37:08 asimon83 Exp $
 *
 * @internal Revisions:
 * 20101010 - franciscom - created using as starting point checkDuplicateName.php by eloff
 *
 **/

require_once('../../config.inc.php');
require_once('common.php');
testlinkInitPage($db);
$data = array('success' => true, 'message' => '');

// BUGID 4066 - take care of proper escaping when magic_quotes_gpc is enabled
$_REQUEST=strings_stripSlashes($_REQUEST);

$iParams = array("node_name" => array(tlInputParameter::STRING_N,0,100),
	             "node_id" => array(tlInputParameter::INT),
	             "parent_id" => array(tlInputParameter::INT),
	             "node_type" => array(tlInputParameter::STRING_N,0,20));
$args = G_PARAMS($iParams);

$tree_manager = new tree($db);
$node_types_descr_id=$tree_manager->get_available_node_types();

// To allow name check when creating a NEW NODE => we do not have node id
$args['node_id'] = ($args['node_id'] > 0 )? $args['node_id'] : null;
$args['parent_id'] = ($args['parent_id'] > 0 )? $args['parent_id'] : null;

// for debug - 
// $xx = "\$args['node_id']:{$args['node_id']} - \$args['name']:{$args['name']}" .
//       " - \$args['parent_id']:{$args['parent_id']}";
// file_put_contents('c:\checkTCaseDuplicateName.php.ajax', $xx);                            

$check = $tree_manager->nodeNameExists($args['node_name'], $node_types_descr_id[$args['node_type']],
									   $args['node_id'],$args['parent_id']);

$data['success'] = !$check['status'];
$data['message'] = $check['msg'];

echo json_encode($data);