<?php
    $client_id = filter_var($_REQUEST['client_id'], FILTER_VALIDATE_INT);
    $project_id = filter_var($_REQUEST['project_id'], FILTER_VALIDATE_INT);

    $sql = "SELECT * FROM `mod_projects` WHERE `client_id` = $client_id ;";
    if($exec = mysql_query($sql)) {
        if(mysql_num_rows($exec) > 0) {
            $select = '';
            while($project = mysql_fetch_array($exec)) {
                if($project['id'] == $project_id) {
                    $select .= "<option selected='selected' value='{$project['id']}' >";
                } else {
                    $select .= "<option value='{$project['id']}' >";
                }
                $select .= $project['name'];
                $select .= "</option>";
            }
        } else {
            $select = "<option value='0'>---</option>";
        }
    } else {
        $select = "<option value='0'>--Select Project---</option>";
    }
    echo $select;
?>
