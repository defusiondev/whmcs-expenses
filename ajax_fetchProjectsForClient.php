<?php
    $client_id = filter_var($_REQUEST['client_id'], FILTER_VALIDATE_INT);

    $sql = "SELECT * FROM `mod_projects` WHERE `client_id` = $client_id;";
    if($exec = mysql_query($sql)) {
        if(mysql_num_rows($exec) > 0) {
            $select = '';
            while($project = mysql_fetch_array($exec)) {
                $select .= "<option value='{$project['id']}' >";
                $select .= $project['name'];
                $select .= "</option>";
            }
        } else {
            $select = "<option value='0'>--No Projects--</option>";
        }
    } else {
        $select = "<option value='0'>--No Projects--</option>";
    }
    echo $select;
?>
