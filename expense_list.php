<?php
$dateformat = mysql_query("SELECT * FROM tbladdonmodules where module='expenses' and setting='dateformat' order by setting DESC limit 1") or die(mysql_error());
$rrows = mysql_fetch_array($dateformat);
$newdateformat = $rrows['value']; // date format 

if ($newdateformat == 'dd-mm-yy') {
    $date_format_insert = 'd-m-Y';
} else {
    $date_format_insert = 'm/d/Y';
}

    if(isset($_REQUEST['msgid']) && is_numeric($_REQUEST['msgid'])) {
        $msg = exp_msgid2value($_REQUEST['msgid']);
                $html.="<div class='infobox1'><strong>".$msg."</strong></div>";
    }
    $offset = filter_var($_REQUEST['offset'], FILTER_SANITIZE_STRING);
    if(trim($offset) == '') {
        $offset = 0;
    }

    /*
    $sql = "
        SELECT e.*, c.name, cl.firstname, cl.lastname, p.name
        FROM `mod_expenses` as e, `mod_categories` as c, `mod_projects` as p, `tblclients` as cl
        WHERE e.category_id = c.id AND e.client_id = cl.id AND e.project_id = p.id
        ORDER BY e.date desc";
    */
    $length = 10;
    $sql = "
        SELECT e.*, c.name, cl.firstname, cl.lastname, p.name as pname, v.vendor as ven, e.notes
        FROM `mod_expenses` as e
            LEFT JOIN `mod_categories` as c
                ON (e.category_id = c.id)
            LEFT OUTER JOIN `mod_projects` as p
                ON (e.project_id = p.id)
            LEFT JOIN `tblclients` as cl
            ON    (e.client_id = cl.id)
            LEFT JOIN `mod_vendors` as v
            ON    (e.vendor = v.id)
        ORDER BY e.date desc
        LIMIT $offset, $length
        ";

    $sql_c = "
        SELECT count(*) as `count`
        FROM `mod_expenses` as e
            LEFT JOIN `mod_categories` as c
                ON (e.category_id = c.id)
            LEFT OUTER JOIN `mod_projects` as p
                ON (e.project_id = p.id)
            LEFT JOIN `tblclients` as cl
            ON    (e.client_id = cl.id)";
    $exec_c = mysql_query($sql_c);
    $result_c = mysql_fetch_array($exec_c);
       
    if($result_c['count'] > 0) {
        if($exec = mysql_query($sql)) {
            if (mysql_num_rows($exec) > 0) {
                $html .= '
                    <script language="JavaScript">
                        function doDelete(id) {
                            if (confirm("Are you sure you want to delete this Expense?")) {
                                window.location="'.$modulelink.'&expense=delete&id="+id;
                            }
                        }
                    </script>
                        ';

                $html .= "<b><a href='$modulelink&expense=add'>Add new Expense</a></b><br /><br />";
                $html .= "
                    <div class='tablebg'>
                    ";
                $html .= "
                    <table class='datatable' cellpadding='3' cellspacing='1' border='0' width='100%'>
                        <thead>
                            <tr>
                                <th width='15%'>
                                    Date
                                </th>
                                <th width='15%'>
                                    Category
                                </th>
                                <th>
                                    Client
                                </th>
                                <th>
                                    Amount
                                </th>
                                <th>
                                    Tax
                                </th>
                                 <th>
                                    Tax2
                                </th>                                            
                                <th>
                                    Fee
                                </th>
                                <th>
                                    Total
                                </th>
                                <th>
                                    Vendor
                                </th>
                                <th>
                                    Notes
                                </th>
                                <th>
                                    Attachment
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
                while ($expense = mysql_fetch_array($exec)) {                    
                    if(!empty($expense['attachment'])){
                        $file = $expense['attachment'].'<a href="'.$modulelink.'&action=download&id='.$expense['id'].'"><img src="../modules/addons/expenses/images/download.png"/></a>';  
                    }else{
                        $file = '';
                    }
                    
                    if($expense['recurring'] == 1) {
                        $recurring_date = exp_getRecurringDate($expense['date'], $expense['frequency'], $expense['untill'], $expense['end_date'], $date_format_insert);
                    }
                    $i++;

                    $amount_col = $expense['amount'];
                    $total_col = $expense['amount'];

                    #$taxed_amount = ($expense['tax1'] == 0 || trim($expense['tax1']) == '' ? 0 : ($expense['tax1'] * $expense['amount'])/100);
                    #$taxed_amount2 = ($expense['tax2'] == 0 || trim($expense['tax2']) == '' ? 0 : ($expense['tax2'] * ($expense['amount']+$taxed_amount))/100);
                    
                    
                    if ($expense['inclusive']=='1') {
                        $itotal_tax = 0;
                        $itotal_tax += $expense['tax1'];
                        $itotal_tax += $expense['tax2'];
                        
                        $base_amount = $expense['amount'] / (100+$itotal_tax);
                        
                        if (is_numeric($expense['tax1']) && $expense['tax1'] > 0) {
                            $taxed_amount = round($base_amount * $expense['tax1'], 2);
                        } else {
                            $taxed_amount = 0;
                        }
    
                        if (is_numeric($expense['tax2']) && $expense['tax2'] > 0) {
                            $taxed_amount2 = round($base_amount * $expense['tax2'], 2);
                        } else {
                            $taxed_amount2 = 0;
                        }
                        
                        $amount_col -= $taxed_amount;
                        $amount_col -= $taxed_amount2;
                    } else {
                        if (is_numeric($expense['tax1']) && $expense['tax1'] > 0) {
                            $taxed_amount = $expense['amount'] / 100;
                            $taxed_amount *= $expense['tax1'];
                        } else {
                            $taxed_amount = 0;
                        }
    
                        if (is_numeric($expense['tax2']) && $expense['tax2'] > 0) {
                            $taxed_amount2 = $expense['amount'] / 100;
                            $taxed_amount2 *= $expense['tax2'];
                        } else {
                            $taxed_amount2 = 0;
                        }                        
                        
                        $total_col += $taxed_amount;
                        $total_col += $taxed_amount2;
                    }
                    
                    if (is_numeric($expense['fee']) && $expense['fee'] > 0)
                        $total_col += $expense['fee'];
                    else
                        $expense['fee'] = 0;

                        
                    $html .= "
                        <tbody>
                            <tr valign='top'>
                                <td>".
                                    date($date_format_insert, $expense['date']).
                                    ($expense['recurring'] == 1 ? "<br /><span style='color:#AAAAAA'>Recurring:<br />$recurring_date</span>" : "")
                                ."</td>
                                <td>
                                    {$expense['name']}
                                    <br />".
                                "</td>
                                <td>".
                                    "<b>{$expense['firstname']}</b><br />
                                    {$expense['pname']}"
                                ."</td>
                                <td>".$expense['currency'].
                                    number_format($amount_col, 2, '.', ' ')
                                    ."<br />".
                                    ($expense['client_id'] == 0
                                        ?
                                            ""
                                        :
                                            ($expense['billed'] == 1
                                                ?
                                                    "<span style='color:red'>unbilled</span>
                                                    (<a href=$modulelink&expense=invoice&id={$expense['id']}>bill</a>)
                                                    "
                                                :
                                                    ($expense['billed'] == 2
                                                        ?
                                                            "<span style='color:gray'>Invoiced</span>"
                                                        :
                                                            ""
                                                    )
                                            )
                                    )
                                ."</td>
                                <td>".$expense['currency'].
                                    number_format($taxed_amount, 2, '.', ' ')
                                ."<br />({$expense['tax1']}%)</td>
                                <td>".$expense['currency'].
                                    number_format($taxed_amount2, 2, '.', ' ')
                                ."<br />({$expense['tax2']}%)</td>
                                <td>
                                    ".$expense['currency'].number_format($expense['fee'], 2, '.', ' ')."
                                </td>                                
                                <td><b>".
                                    $expense['currency'].number_format($total_col, 2, '.', ' ')
                                ."</b></td>
                                <td>
                                    {$expense['ven']}
                                </td>
                                <td>
                                    ".nl2br($expense['notes'])."
                                </td>
                                <td>
                                    {$file}        
                                 </td>
                                <td align='center'>
                                    <a href='$modulelink&expense=edit&id={$expense['id']}'><img width='16' height='16' border='0' alt='Edit' src='images/edit.gif' /></a> 
                                </td>
                                    <td align='center'>    <a href='#' onclick='doDelete({$expense['id']})'><img width='16' height='16' border='0' alt='Delete' src='images/delete.gif' /></a>
                                </td>
                            </tr>
                        ";
                }
                $html .= "
                        </tbody>
                    </table>
                    ";
                $html .= "</div>";
                if($result_c['count'] > $length) {
                    $html .= "<div>";
                    if( ($offset-$length) >= 0 ) {
                        if( ($offset-$length) == 0 ) {
                            $html .= "
                                    <a href='$modulelink&expense=list'>Prev</a>
                                ";
                        } else {
                            $html .= "
                                    <a href='$modulelink&expense=list&offset=".($offset-$length)."'>Prev</a>
                                ";
                        }
                    } else {
                        $html .= "
                                Prev
                            ";
                    }
                    $html .= " | ";
                    if( ($offset+$length) < $result_c['count'] ) {
                        $html .= "
                                <a href='$modulelink&expense=list&offset=".($offset+$length)."'>Next</a>
                            ";
                    } else {
                        $html .= "
                                Next
                            ";
                    }
                    $html .= "</div>";
                }
            } else {
                $html .= "No Expense in database.<br /> <a href='$modulelink&expense=add'>Click here</a> to add new expense.";
            }
        } else {
            $html .= "Error in query : <b>$query</b>";
        }
    } else {
        $html .= "No Expense in database.<br /> <a href='$modulelink&expense=add'>Click here</a> to add new expense.";
    }
    echo $html;
?>