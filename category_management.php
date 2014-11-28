<?php
switch($category) {
    case 'list' :
        include_once 'category_list.php';
        break;
    case 'add' :
        include_once 'category_add.php';
        break;
    case 'edit' :
        include_once 'category_edit.php';
        break;
    case 'delete' :
        include_once 'category_delete.php';
        break;
}
?>