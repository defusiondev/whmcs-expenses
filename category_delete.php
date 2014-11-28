<?php
    $id = filter_var($_REQUEST['id'], FILTER_VALIDATE_INT);
    $sql = "SELECT * FROM `mod_categories` WHERE `id` = $id LIMIT 1";
    $exec = mysql_query($sql);
    if(mysql_num_rows($exec) > 0) {
        $sql = "DELETE FROM `mod_categories` WHERE `id` = $id LIMIT 1";
        if(mysql_query($sql)) {
            header("Location:$modulelink&category=list&msgid=5");
        } else {
            header("Location:$modulelink&category=list&msgid=6");
        }
    } else {
        header("Location:$modulelink&category=list&msgid=8");
    }
    
?>