<?php
if (!isset($db_name)) {
    //$ePath = substr($_SERVER['SCRIPT_FILENAME'], 0, -12) . "";
    $ePath - dirname(__FILE__);
}

# Module Configuration
function expenses_config() {
    $configarray = array(
        "name" => "Expenses",
        "version" => "2.1.21",
        "author" => "Nobody",
        "language" => "english",
        "fields" => array(
            "licencekey" => array("FriendlyName" => "Licence Key", "Type" => "text", "Size" => "100","Description" => "Enter module Licence Key", "Default" => ""),
            "localkey" => array("FriendlyName" => "", "Type" => "hidden", "Size" => "255","Description" => ""),
            "dateformat" => array("FriendlyName" => "Date Format", "Type" => "radio", "Options" => "dd-mm-yy,mm/dd/yy", "Default" => "mm/dd/yy"),
        )
    );
    return $configarray;
}

# Module Activation Hook
function expenses_activate() {

    # Create Custom DB Table
    $query1 = "
            CREATE TABLE IF NOT EXISTS `mod_categories` (
              `id` int(8) NOT NULL AUTO_INCREMENT,
              `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
              `description` text COLLATE utf8_unicode_ci NOT NULL,
              `active` int(1) NOT NULL,
              `modified` bigint(12) NOT NULL,
              `created` bigint(12) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
            ";
    $query2 = "
            CREATE TABLE IF NOT EXISTS `mod_expenses` (
              `id` int(8) NOT NULL AUTO_INCREMENT,
                `invoice_num` int(11) NOT NULL,
                `currency` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
                `amount` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
                `fee` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
              `date` bigint(12) NOT NULL,
              `vendor` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
              `category_id` int(8) NOT NULL,
              `notes` text COLLATE utf8_unicode_ci NOT NULL,
              `taxes` int(1) NOT NULL,
              `inclusive` int(1) NOT NULL,
              `tax1` varchar(8) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
              `tax2` varchar(8) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
              `recurring` int(1) NOT NULL,
              `frequency` int(2) NOT NULL,
              `untill` int(1) NOT NULL,
              `end_date` bigint(12) NOT NULL,
              `assign_to_client` int(1) NOT NULL,
              `client_id` int(8) NOT NULL,
              `project_id` int(8) NOT NULL,
              `billed` int(1) NOT NULL DEFAULT '1' COMMENT '0 => not assigned to client, 1 => unbilled, 2 => invoiced',
                          `attachment` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
                          `invoice_date` bigint(12) NOT NULL,
              `modified` bigint(12) NOT NULL,
              `created` bigint(12) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
            ";
    $query3 = "
            CREATE TABLE IF NOT EXISTS `mod_vendors` (
              `id` int(8) NOT NULL AUTO_INCREMENT,
              `vendor` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
            ";
    $query4 = "
            CREATE TABLE IF NOT EXISTS `mod_projects` (
              `id` int(8) NOT NULL AUTO_INCREMENT,
              `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
              `description` text COLLATE utf8_unicode_ci NOT NULL,
              `client_id` int(8) NOT NULL,
              `status` int(1) NOT NULL,
              `last_invoiced` bigint(12) NOT NULL,
              `modified` bigint(12) NOT NULL,
              `created` bigint(12) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
                ";
    $query5 = "
            CREATE TABLE IF NOT EXISTS `mod_expenses_settings` (
              `id` int(8) NOT NULL AUTO_INCREMENT,
              `localkey` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                          `licencekey` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                          `dateformat` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                          `created` DATETIME ,
                          `modified` DATETIME ,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
            ";
    
    if (mysql_query($query1) && mysql_query($query2) && mysql_query($query3) && mysql_query($query4) && mysql_query($query5)) {
        return array('status' => 'success', 'description' => 'Activated successfully');
    } else {
        return array('status' => 'error', 'description' => 'Module could not be activated successfully. Additional tables could not be created');
    }
}

# Module Deactivation Hook
function expenses_deactivate() {
    # Remove Custom DB Table
    $query1 = "DROP TABLE IF EXISTS `mod_categories`";
    $query2 = "DROP TABLE IF EXISTS `mod_expenses`";
    $query3 = "DROP TABLE IF EXISTS `mod_vendors`";
    $query4 = "DROP TABLE IF EXISTS `mod_projects`";
    $query5 = "DROP TABLE IF EXISTS `mod_expenses_settings`";
    if (mysql_query($query1) && mysql_query($query2) && mysql_query($query3) && mysql_query($query4) && mysql_query($query5)) {
        return array('status' => 'success', 'description' => 'Deactivated successfully');
    } else {
        return array('status' => 'error', 'description' => 'Not Deactivated');
    }
    # Return Result
}

# Module Upgrade Hook
function expenses_upgrade($vars) {
  $version = $vars['version'];

  # Run SQL Updates for all versions < 1.1.
  if ($version < 1.1) {
    $query = 'ALTER TABLE  `mod_expenses` CHANGE  `tax2`  `inclusive` VARCHAR(8) NOT NULL';
    $result = mysql_query($query);

    $query = "ALTER TABLE mod_expenses ADD invoice_num int(11) NOT NULL AFTER id";
    $result = mysql_query($query);
    
    $query = "ALTER TABLE mod_expenses ADD currency varchar(16), ADD `fee` varchar(16) NOT NULL DEFAULT '0', ADD tax2 VARCHAR(8) DEFAULT '0', ADD attachment varchar(150) AFTER invoice_num";
    $result = mysql_query($query);
    
    $query_config="ALTER TABLE mod_expenses_settings ADD dateformat varchar(255) AFTER licencekey";
    $result_config = mysql_query($query_config);
  }
} 

# Module Output Function
function expenses_output($vars) {
    require_once('functions.php');
    $modulelink = $vars['modulelink'];
    $licensekey = $vars['licencekey'];
    $localkey = $vars['localkey'];
    
    $_LANG = $vars['_lang'];

    $html = '';
    if (isset($_REQUEST['ajax']) || $_REQUEST['report'] == 'download') {
        include($ePath . "ajax.php");
    } else {
        $html .= '<link href="../modules/addons/expenses/css/form.css" rel="stylesheet" type="text/css" />';
        $html .= '<link href="../modules/addons/expenses/css/form2.css" rel="stylesheet" type="text/css" />';
        if(!isset($_GET['action']) || $_GET['action']!='download'){
        exp_menu($modulelink, false);    
        }
        
    }
    
    # Get request variables
    $category = isset($_REQUEST['category']) ? trim($_REQUEST['category']) : "";
    $expense = isset($_REQUEST['expense']) ? trim($_REQUEST['expense']) : "";
    $vendor = isset($_REQUEST['vendor']) ? trim($_REQUEST['vendor']) : "";
    $report = isset($_REQUEST['report']) ? trim($_REQUEST['report']) : "";
    $ajax = isset($_REQUEST['ajax']) ? trim($_REQUEST['ajax']) : "";

    if ($_GET['module'] == 'expenses' && $_GET['category'] == 'list') {
        exp_category_list();
    } elseif ($_GET['module'] == 'expenses' && $_GET['category'] == 'add') {
        exp_category_add();
    } elseif ($_GET['module'] == 'expenses' && $_GET['category'] == 'edit') {
        exp_category_edit();
    } elseif ($_GET['module'] == 'expenses' && $_GET['category'] == 'delete') {
        exp_category_delete();
    } elseif ($_GET['module'] == 'expenses' && $_GET['vendor'] == 'add') {
        exp_vendor_add();
    } elseif ($_GET['module'] == 'expenses' && $_GET['vendor'] == 'edit') {
        exp_vendor_edit();
    } elseif ($_GET['module'] == 'expenses' && $_GET['vendor'] == 'delete') {
        exp_vendor_delete();
    } elseif ($_GET['module'] == 'expenses' && $_GET['vendor'] == 'list') {
        exp_vendor_list();
    } elseif ($_GET['module'] == 'expenses' && $_GET['expense'] == 'invoice') {
        exp_expense_invoice();
    } elseif ($_GET['module'] == 'expenses' && $_GET['expense'] == 'add') {
        exp_expense_add();
    } elseif ($_GET['module'] == 'expenses' && $_GET['expense'] == 'edit') {
        exp_expense_edit();
    } elseif ($_GET['module'] == 'expenses' && $_GET['expense'] == 'delete') {
        exp_expense_delete();
    } elseif ($_GET['module'] == 'expenses' && $_GET['report'] == 'list') {
        exp_report_list();
    } elseif ($_GET['module'] == 'expenses' && $_GET['report'] == 'download') {
        exp_report_download();
    } elseif ($_GET['module'] == 'expenses' && $_GET['report'] == 'stats') {
        exp_stats();
    } elseif($_GET['module'] == 'expenses' && $_GET['action'] == 'download'){ 
        exp_download_attachment($id);
    } else {
        exp_expense_list();
    }

    echo $html;
}

function exp_category_list() {
    global $modulelink;
    include_once('category_list.php');
}

function exp_category_add() {
    global $modulelink;
    include_once 'category_add.php';
}

function exp_category_edit() {
    global $modulelink;
    include_once('category_edit.php');
}

function exp_category_delete() {
    global $modulelink;
    include_once('category_delete.php');
}

function exp_vendor_add() {
    global $modulelink;
    include_once('vendor_add.php');
}

function exp_vendor_edit() {
    global $modulelink;
    include_once('vendor_edit.php');
}

function exp_vendor_delete() {
    global $modulelink;
    include_once('vendor_delete.php');
}

function exp_vendor_list() {
    global $modulelink;
    include_once('vendor_list.php');
}

function exp_expense_invoice() {
    global $modulelink;
    include_once('expense_invoice.php');
}

function exp_expense_add() {
    global $modulelink;
    include_once('expense_add.php');
}

function exp_expense_edit() {
    global $modulelink;
    include_once('expense_edit.php');
}

function exp_expense_delete() {
    global $modulelink;
    include_once('expense_delete.php');
}

function exp_expense_list() {
    global $modulelink;
    include_once('expense_list.php');
}

function exp_report_list() {
    global $modulelink;
    switch ($_REQUEST['type']) {
        case 0:
            include_once 'report_list.php';
            break;
        case 1:
            include_once 'report_basic.php';
            break;
    }
}

function exp_report_download() {
    global $modulelink;
    switch ($_REQUEST['type']) {
        case 0:
            include_once 'report_download.php';
            break;
        case 1:
            include_once 'report_download_basic.php';
            break;
    }
}

function exp_stats() {
    global $modulelink;
    include_once 'stats.php';
}

function exp_download_attachment(){
    global $modulelink;
    include_once 'download_attachment.php';
}
?>