<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: custom_config.inc.php,v $
 *
 * @version $Revision: 1.14 $
 * @modified $Date: 2008/09/25 20:28:33 $ by $Author: franciscom $
 *
 * SCOPE:
 * Constants and configuration parameters used throughout TestLink DEFINED BY USERS.
 *
 * Use this file to overwrite configuration parameters (variables and defines)
 * present in:
 *             config.inc.php
 *             cfg/const.inc.php
 *-----------------------------------------------------------------------------
*/
//$tlCfg->req_cfg->child_requirements_mgmt=ENABLED;
//$g_spec_cfg->steps_results_layout='horizontal';

// Hint: After doing configuration changes, clean you Browser's cookies and cache 
//
// $tlCfg->gui->text_editor['all'] = array( 'type' => 'fckeditor', 
//                                          'toolbar' => 'tl_default', 
//                                          'configFile' => 'cfg/tl_fckeditor_config.js');

// Copy this to custom_config.inc.php if you want use 'tinymce' as default.
//$tlCfg->gui->text_editor['all'] = array( 'type' => 'tinymce');
// 
// Copy this to custom_config.inc.php if you want use 'nome' as default.
// $tlCfg->gui->text_editor['all'] = array( 'type' => 'none');

// Suggested for BETTER Performance with lot of testcases
//$tlCfg->gui->text_editor['execution'] = array( 'type' => 'none');

// Enable and configure this if you want to have different
// webeditor type in different TL areas
// You can not define new areas without making changes to php code
//
// $tlCfg->gui->text_editor['execution'] = array( 'type' => 'none');  // BETTER Performance with lot of testcases
// 
// This configuration is useful only if default type is set to 'fckeditor'
// $tlCfg->gui->text_editor['design'] = array('toolbar' => 'tl_mini');
// 
// $tlCfg->gui->text_editor['testplan'] = array( 'type' => 'none');
// $tlCfg->gui->text_editor['build'] = array( 'type' => 'fckeditor','toolbar' => 'tl_mini');
// $tlCfg->gui->text_editor['testproject'] = array( 'type' => 'tinymce');
// $tlCfg->gui->text_editor['role'] = array( 'type' => 'tinymce');
// $tlCfg->gui->text_editor['requirement'] = array( 'type' => 'none');
// $tlCfg->gui->text_editor['requirement_spec'] = array( 'type' => 'none');

// SMTP server Configuration ("localhost" is enough in the most cases)
//$g_smtp_host        = 'localhost';  # SMTP server MUST BE configured  

# Configure using custom_config.inc.php
//$g_tl_admin_email     = 'tl_admin@127.0.0.1'; # for problem/error notification 
//$g_from_email         = 'testlink@127.0.0.1';  # email sender
//$g_return_path_email  = 'francisco@127.0.0.1';
//
# Urgent = 1, Not Urgent = 5, Disable = 0
//$g_mail_priority = 5;   

# Taken from mantis for phpmailer config
#define ("SMTP_SEND",2);
#$g_phpMailer_method = SMTP_SEND;

// Configure only if SMTP server requires authentication
// $g_smtp_username    = '';  # user  
// $g_smtp_password    = '';  # password 
// 
// 
// 
// 
// // TRUE  -> the whole execution history for the choosen build will be showed
// // FALSE -> just last execution for the choosen build will be showed [STANDARD BEHAVIOUR]
// $tlCfg->exec_cfg->history_on = TRUE;
// 
// $tlCfg->exec_cfg->show_testsuite_contents = ENABLED;

// TRUE  ->  test case VERY LAST (i.e. in any build) execution status will be displayed
// FALSE -> only last result on current build.  [STANDARD BEHAVIOUR]
// $tlCfg->exec_cfg->show_last_exec_any_build = TRUE;
// 
// // TRUE  ->  History for all builds will be shown
// // FALSE ->  Only history of the current build will be shown  [STANDARD BEHAVIOUR]
// $tlCfg->exec_cfg->show_history_all_builds = TRUE;
// 
//$tlCfg->treemenu_type='JTREE';

// $tlCfg->gui->custom_fields->types = array(100 => 'radio head');
// $tlCfg->gui->custom_fields->possible_values_cfg = array('radio head' => 1);

//$g_log_level='DEBUG';

//$g_tree_type='JTREE';
//$g_tree_show_testcase_id=1;
//$g_exec_cfg->enable_tree_testcase_counters=1;
//$g_exec_cfg->enable_tree_colouring=1;


//$g_tc_status["tcstatus_1"] = 'q';
//$g_tc_status["tcstatus_2"] = 'w';
//$g_tc_status_css = array_flip($g_tc_status);
//$g_tc_status_verbose_labels["tcstatus_1"] = 'test_status_new_one';
//$g_tc_status_verbose_labels["tcstatus_2"] = 'test_status_new_two';
//$g_tc_status_for_ui["tcstatus_1"] = 'test_status_new_one';
//$g_tc_status_for_ui["tcstatus_2"] = 'test_status_new_one';

//$g_tc_status_for_ui_default="blocked";


// $tlCfg->results['status_code'] = array ( 
//         "failed"        => 'f', 
//         "blocked"       => 'b', 
//         "passed"        => 'p', 
//         "not_run"       => 'n', 
//         "not_available" => 'x', 
//         "unknown"       => 'u', 
//         "all"           => 'a' 
// ); 
// 
// $tlCfg->results['status_label'] = array( 
//         "passed"                => "test_status_passed", 
//         "failed"                => "test_status_failed", 
//         "blocked"               => "test_status_blocked", 
//         "not_run"               => "test_status_not_run", 
// //    "all"                   => "test_status_all_status", 
//         "not_available"    => "test_status_not_available", 
// //      "unknown"          => "test_status_unknown" 
// ); 
// 
// $tlCfg->results['status_label_for_exec_ui'] = array( 
//         "passed"  => "test_status_passed", 
//         "failed"  => "test_status_failed", 
//         "blocked" => "test_status_blocked", 
//         "not_run" => "test_status_not_run",
//         "not_available" => "test_status_not_available" 
// ); 
// 
// $tlCfg->results['default_status'] = "not_run"; 



?>