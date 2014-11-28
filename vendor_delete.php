<?php
    $id = filter_var($_REQUEST['id'], FILTER_VALIDATE_INT);
    $sql = "SELECT * FROM `mod_vendors` WHERE `id` = $id LIMIT 1";
    $exec = mysql_query($sql);
    if(mysql_num_rows($exec) > 0) {
        $sql = "DELETE FROM `mod_vendors` WHERE `id` = $id LIMIT 1";
        if(mysql_query($sql)) {
            header("Location:$modulelink&vendor=list&msgid=20");
        } else {
            header("Location:$modulelink&vendor=list&msgid=21");
        }
    } else {
        header("Location:$modulelink&vendor=list&msgid=22");
    }
    
?>