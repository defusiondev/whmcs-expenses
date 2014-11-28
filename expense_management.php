<?php
switch($expense) {
    case 'invoice' :
        include_once 'expense_invoice.php';
        break;
    case 'list' :
        include_once 'expense_list.php';
        break;
    case 'add' :
        include_once 'expense_add.php';
        break;
    case 'edit' :
        include_once 'expense_edit.php';
        break;
    case 'delete' :
        include_once 'expense_delete.php';
        break;
}
?>