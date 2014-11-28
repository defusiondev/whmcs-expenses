<?php

$dateformat = mysql_query("SELECT * FROM tbladdonmodules where module='expenses' and setting='dateformat' order by setting DESC limit 1") or die(mysql_error());
$rrows = mysql_fetch_array($dateformat);
$newdateformat = $rrows['value']; // date format 

if ($newdateformat == 'dd-mm-yy') {
    $date_format_insert = 'd-m-Y';
} else {
    $date_format_insert = 'm/d/Y';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $client_id = $_REQUEST['client_id'];
    $cat_id = $_REQUEST['cat_id'];

    $time1 = strtotime($_REQUEST['date1'].' 00:00:00');
    $time2 = strtotime($_REQUEST['date2'].' 23:59:59');
    
     $sql = "
        SELECT e.*, c.name, c.id as cid, cl.firstname, cl.lastname, p.name as pname, v.vendor as ven, e.notes
        FROM `mod_expenses` as e
            LEFT JOIN `mod_categories` as c
                ON (e.category_id = c.id)
            LEFT JOIN `mod_projects` as p
                ON (e.project_id = p.id)
            LEFT JOIN `tblclients` as cl
                ON    (e.client_id = cl.id)
            LEFT JOIN `mod_vendors` as v
                ON    (e.vendor = v.id)
         WHERE e.date >= $time1 AND e.date <= $time2"
            . ($client_id == 0 ? "" : " AND e.client_id=$client_id")
            . ($cat_id == 0 ? "" : " AND c.id=$cat_id")
            . " ORDER BY e.date desc
        ";
    if ($exec = mysql_query($sql)) {
        if (mysql_num_rows($exec) > 0) {

            $html .= "
                    <div class='tablebg'>
                        <div style='border: 1px solid rgb(204, 204, 204); padding: 10px; width: auto;'>
                            <form action='$modulelink&report=download' method='POST'>
                            <input type='hidden' name='date1' value='$time1' />
                            <input type='hidden' name='date2' value='$time2' />
                            <input type='submit' value='Download CSV' name='submit' />
                            </form>
                        </div>
                        <br />
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
                                    Tax 1
                                </th>
                                <th>
                                    Tax 2
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
                            </tr>
                        </thead>
                    ";
            $i = 0;
            $total_amount = 0;
            $total_tax = 0;
            $results = localAPI("getcurrencies");
            
                while ($expense = mysql_fetch_array($exec)) {                    
                    if(!empty($expense['attachment'])){
                        $file = $expense['attachment'].'<a href="'.$modulelink.'&action=download&id='.$expense['id'].'"><img src="../modules/addons/expenses/images/download.png"/></a>';  
                    }else{
                        $file = '';
                    }
                    
                    if($expense['recurring'] == 1) {
                        $recurring_date = exp_getRecurringDate($expense['date'], $expense['frequency'], $expense['untill'], $expense['end_date']);
                    }
                    $i++;

                    $amount_col = $expense['amount'];
                    $total_col = $expense['amount'];

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

                    $total_amount += $amount_col;
                    $total_tax += ($taxed_amount + $taxed_amount2);
                    
                    $html .= "
                        <tbody>
                            <tr valign='top'>
                                <td>".
                                    date($date_format_insert, $expense['date']).
                                    ($expense['recurring'] == 1 ? "<br /><span style='color:#AAAAAA'>Recurring Date:<br />$recurring_date</span>" : "")
                                ."</td>
                                <td>
                                    {$expense['name']}
                                </td>
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
                            </tr>
                        ";
                }

            
            $total_total = $total_amount + $total_tax;
            $total_amount = number_format($total_amount, 2, '.', ' ');
            $total_tax = number_format($total_tax, 2, '.', ' ');
            $total_total = number_format($total_total, 2, '.', ' ');
            $html .= "
                    <tr>
                        <td colspan='7'>
                            &nbsp;<br /><br />
                        </td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            Amount:
                        </td>
                        <td colspan='1'>
                            <b>{$expense['currency']}$total_amount</b>
                        </td>
                        <td colspan='4'>
                            &nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            Tax:
                        </td>
                        <td colspan='1'>
                            <b>{$expense['currency']}$total_tax</b>
                        </td>
                        <td colspan='4'>
                            &nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            Total:
                        </td>
                        <td colspan='1'>
                            <b>{$expense['currency']}$total_total</b>
                        </td>
                        <td colspan='4'>
                            &nbsp;
                        </td>
                    </tr>
                ";
            $html .= "
                        </tbody>
                    </table>
                    ";
            $html .= "</div>";
        } else {
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
                                    Notes
                                </th>
                                <th>
                                    Amount
                                </th>
                                
                                <th>
                                    Tax
                                </th>
                                <th>
                                    Total
                                </th>
                                <th>
                                    Vendor
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan='7' align='center'>No records in database matching your query</td>
                            </tr>
                        </tbody>
                    </table>
                    ";
            //$html .= "No Expense in database.<br /> <a href='$modulelink&expense=add'>Click here</a> to add new expense.";
        }
    } else {
        $html .= "Error in query : <b>$query</b>";
    }
    echo $html;
} else {
    $clients_exist = false;
    $sql = "SELECT id,firstname,lastname,companyname FROM tblclients order by firstname";
    if ($exec = mysql_query($sql)) {
        if (is_resource($exec) && mysql_num_rows($exec) > 0) {
            $clients_exist = true;
            $clients = array();
            while ($clients_ = mysql_fetch_array($exec)) {
                $clients[] = $clients_;
            }
        }
    }
    if ($clients_exist) {
        $select = "<select id='client_id' name='client_id'>";
        $select .= "<option value='0' >All Clients</option>";

        foreach ($clients as $client) {
            $select .= "<option value='{$client['id']}' >";
            $select .= $client['firstname'] . " " . $client['lastname'];
            if ($client['companyname'] != '')
                $select .= ' (' . $client['companyname'] . ')';
            $select .= "</option>";
        }
        $select .= "</select>";
    }


    $cats_exist = false;
    $sql = "SELECT * FROM mod_categories";
    if ($exec = mysql_query($sql)) {
        if (is_resource($exec) && mysql_num_rows($exec) > 0) {
            $cats_exist = true;
            $cats = array();
            while ($cats_ = mysql_fetch_array($exec)) {
                $cats[] = $cats_;
            }
        }
    }
    if ($cats_exist) {
        $select_cats = "<select id='cat_id' name='cat_id'>";
        $select_cats .= "<option value='0' >All Categories</option>";

        foreach ($cats as $cat) {
            $select_cats .= "<option value='{$cat['id']}' >";
            $select_cats .= $cat['name'];
            $select_cats .= "</option>";
        }
        $select_cats .= "</select>";
    }
    ?>
    <script type="text/javascript">
        $(function() {
            $("#date1").datepicker({ dateFormat: "<?php echo $newdateformat; ?>" });
            $("#date2").datepicker({ dateFormat: "<?php echo $newdateformat; ?>" });
        });
    </script>
    <form action="<?php echo $modulelink ?>&report=list" name="report" method="post">
        <table width="700" border="0">
            <tr>
                <td class="textname_box">
                    <b>Select Client</b>:
                </td>
                <td  class="text_project1">
                    <?php
                    echo $select;
                    ?>
                </td>
            </tr>
            <tr>
                <td class="textname_box">
                    <b>Select Categories</b>:
                </td>
                <td  class="text_project1">
                    <?php
                    echo $select_cats;
                    ?>
                </td>
            </tr>
            <tr>
                <td class="textname_box">
                    <b>Date Range</b>:
                </td>
                <td  class="text_project1">
                    <input type="text" name="date1" id="date1" value="<?php echo date($date_format_insert, strtotime('-1 month')) ?>" />
                    &nbsp; &nbsp; to &nbsp; &nbsp;
                    <input type="text" name="date2" id="date2"  value="<?php echo date($date_format_insert) ?>">
                </td>
            </tr>
            <tr>
                <td class="textname_box">
                    <b>Report Type</b>:
                </td>
                <td style="padding:5px 0 0 20px;">
                    <p style="margin-bottom:5px;"><label><input type="radio" name="type" value="0" checked="checked" /> Expenses</label></p>
                    <p><label><input type="radio" name="type" value="1" /> Invoice List</label></p>
                </td>
            </tr>
            <tr>
                <td class="textname_box">
                    &nbsp;
                </td>
                <td  class="text_project1">
                    <input type="submit" name="generate" id="generate" value="View Report" />
                </td>
            </tr>
        </table>
    </form>
    <?php
}
?>