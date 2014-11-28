<?php
    if(isset($_REQUEST['msgid']) && is_numeric($_REQUEST['msgid'])) {
        $msg = exp_msgid2value($_REQUEST['msgid']);
        $html .= "<div style='color:orange; font-weight:bold;'>$msg</div>";
    }
    $sql = "
        SELECT *
        FROM `mod_vendors`
        ORDER BY vendor
        ;";
    if($exec= mysql_query($sql)) {
        if (mysql_num_rows($exec) > 0) {
            $html .= '
                <script language="JavaScript">
                    function doDelete(id) {
                        if (confirm("Are you sure you want to delete this Vendor?")) {
                            window.location="'.$modulelink.'&vendor=delete&id="+id;
                        }
                    }
                </script>
                    ';

            $html .= "<b><a href='$modulelink&vendor=add'>Add new Vendor</a></b><br /><br />";
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
            while($vendor = mysql_fetch_array($exec)) {
                $i++;
                $html .= "
                    <tbody>
                        <tr>
                            <td>
                                $i
                            </td>
                            <td>
                                {$vendor['vendor']}
                            </td>
                            <td>
                                <a href='$modulelink&vendor=edit&id={$vendor['id']}'><img width='16' height='16' border='0' alt='Edit' src='images/edit.gif'></a>
                            </td>
                            <td>
                                <a href='#' onclick='doDelete({$vendor['id']})'><img width='16' height='16' border='0' alt='Delete' src='images/delete.gif'></a>
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
            $html .= "No Vendor in database.<br /> <a href='$modulelink&vendor=add'>Click here</a> to add new vendor.";
        }
    } else {
        $html .= "Error in query : <b>$query</b>";
    }
    echo $html;
?>