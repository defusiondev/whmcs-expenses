<?php
if (isset($_REQUEST['msgid']) && is_numeric($_REQUEST['msgid'])) {
    $msg = exp_msgid2value($_REQUEST['msgid']);
    $html .= "<div style='color:orange; font-weight:bold;'>$msg</div>";
}

$table = "mod_categories";
$fields = "id,name,modified";

$exec = select_query($table, $fields);

if ($exec) {
    if (mysql_num_rows($exec) > 0) {
        $html .= '
                <script language="JavaScript">
                    function doDelete(id) {
                        if (confirm("Are you sure you want to delete this Category?")) {
                            window.location="' . $modulelink . '&category=delete&id="+id;
                        }
                    }
                </script>
                    ';

        $html .= "<b><a href='$modulelink&category=add'>Add new Category</a></b><br /><br />";
        $html .= "
                <div class='tablebg'>
                ";
        $html .= "
                <table class='datatable' cellpadding='3' cellspacing='1' border='0' width='100%'>
                    <thead>
                        <tr>
                            <th>
                                Count
                            </th>
                            <th>
                                Name
                            </th>
                            <th>
                                &nbsp;
                            </th>
                            <th>
                                &nbsp;
                            </th>
                        </tr>
                    </thead>
                ";
        $i = 0;
        while ($category = mysql_fetch_array($exec)) {
            $i++;
            $html .= "
                    <tbody>
                        <tr>
                            <td>
                                $i
                            </td>
                            <td>
                                {$category['name']}
                            </td>
                            <td>
                                <a href='$modulelink&category=edit&id={$category['id']}'><img width='16' height='16' border='0' alt='Edit' src='images/edit.gif'></a>
                            </td>
                            <td>
                                <a href='#' onclick='doDelete({$category['id']})'><img width='16' height='16' border='0' alt='Delete' src='images/delete.gif'></a>
                            </td>
                        </tr>
                    ";
        }
        $html .= "
                    </tbody>
                </table>
                ";
        $html .= "</div>";
    } else {
        $html .= "No Category in database.<br /> <a href='$modulelink&category=add'>Click here</a> to add new category.";
    }
} else {
    $html .= "Error in query : <b>$query</b>";
}
echo $html;
?>