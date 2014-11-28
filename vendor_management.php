<?php
switch($vendor) {
    case 'list' :
        include_once 'vendor_list.php';
        break;
    case 'add' :
        include_once 'vendor_add.php';
        break;
    case 'edit' :
        include_once 'vendor_edit.php';
        break;
    case 'delete' :
        include_once 'vendor_delete.php';
        break;
}
?>