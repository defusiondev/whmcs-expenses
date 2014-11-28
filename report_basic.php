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
    
    $postfields["action"] = "getcurrencies";
    $results = localAPI("getcurrencies");

    $sql = "
        SELECT i.id, i.date, i.subtotal, i.tax, i.tax2, i.total, i.userid, c.firstname, c.lastname, c.companyname, c.currency as ccurrency
        FROM `tblclients` as c, `tblinvoices` as i
            WHERE i.userid = c.id AND UNIX_TIMESTAMP(i.date) >= $time1 AND UNIX_TIMESTAMP(i.date) <= $time2   
        ";
    if ($exec = mysql_query($sql)) {
        if (mysql_num_rows($exec) > 0) {


            $html .= "
                    <div class='tablebg'>
                        <div style='border: 1px solid rgb(204, 204, 204); padding: 10px; width: auto;'>
                            <form action='$modulelink&report=download' method='POST'>
                            <input type='hidden' name='date1' value='$time1' />
                            <input type='hidden' name='date2' value='$time2' />
                            <input type='hidden' name='type' value='{$_REQUEST['type']}' />
                            <input type='submit' value='Download Invoice list CSV' name='submit' />
                            </form>
                        </div>
                        <br />
                    ";
            $html .= "
                    <table class='datatable' cellpadding='3' cellspacing='1' border='0' width='100%'>
                        <thead>
                            <tr>
                                <th width='15%'>
                                    Invoice ID
                                </th>
                                <th width='15%'>
                                    Date
                                </th>
                                <th>
                                    Tax 1
                                </th>
                                <th>
                                    Tax 2
                                </th>
                                <th>
                                    Total
                                </th>
                                <th>
                                    Client Name
                                </th>
                                <th>
                                    Client Organization
                                </th>
                            </tr>
                        </thead>
                    ";
            $i = 0;
            while ($expense = mysql_fetch_array($exec)) {
                $c_query = mysql_query("SELECT currency from tblclients where id='" . $expense['userid'] . "'") or die(mysql_error());
                $c_result = mysql_fetch_array($c_query);
                $date_1 = date($date_format_insert, strtotime($expense['date']));
                
                $currency_symbol = '';
                foreach ($results['currencies']['currency'] as $cr) {
                    if ($c_result['currency'] == $cr['id']) {
                        $currency_symbol = $cr['prefix'];
                        break;
                    }
                }
                
                $i++;
                $taxed_amount = ($expense['tax1'] == 0 || trim($expense['tax1']) == '' ? 0 : ($expense['tax1'] * $expense['amount']) / 100 );

                $html .= "
                        <tbody>
                            <tr valign='top'>
                                <td>" . $expense['id'] . "</td>
                                <td>
                                    {$date_1}
                                    " .
                        "</td>
                                <td>
                                    {$currency_symbol}{$expense['tax']}
                                </td>
                                <td>
                                    {$currency_symbol}{$expense['tax2']}
                                </td>
                                <td>" .
                                    $currency_symbol . $expense['total']
                                    ."<br /></td>
                                <td><b>" .
                                    $expense['firstname'] . " " . $expense['lastname']
                                    . "</b></td>
                                <td>
                                    {$expense['companyname']}
                                </td>
                            </tr>";
            }
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
                                    Invoice ID
                                </th>
                                <th width='15%'>
                                    Date
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
                                    Client Name
                                </th>
                                <th>
                                    Client Organization
                                </th>
                                <th>
                                    Download CSV
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                            <tr>
                                <td colspan='8'>
                                    No Matching Invoice in database
                                </td>
                            </tr>
                    </table>
                    ";
        }
    } else {
        $html .= "Error in query : <b>" . $query . "</b>";
    }

    echo $html;
}
?>