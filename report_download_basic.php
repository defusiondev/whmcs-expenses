<?php
if( $_SERVER["REQUEST_METHOD"] == "POST") {
    $dateformat = mysql_query("SELECT * FROM tbladdonmodules where module='expenses' and setting='dateformat' order by setting DESC limit 1") or die(mysql_error());
    $rrows = mysql_fetch_array($dateformat);
    $newdateformat = $rrows['value']; // date format 
    
    if ($newdateformat == 'dd-mm-yy') {
        $date_format_insert = 'd-m-Y';
    } else {
        $date_format_insert = 'm/d/Y';
    }

    // Load both dates
    $time1 = $_REQUEST['date1'];
    $time2 = $_REQUEST['date2'];
    $results = localAPI("getcurrencies");

    $sql = "
        SELECT i.id, i.date, i.subtotal, i.tax, i.tax2, i.total, i.userid, c.firstname, c.lastname, c.companyname, c.currency as ccurrency
        FROM `tblclients` as c, `tblinvoices` as i
            WHERE i.userid = c.id AND UNIX_TIMESTAMP(i.date) >= $time1 AND UNIX_TIMESTAMP(i.date) <= $time2   
        ";
        
        if($exec = mysql_query($sql)) {
            if (mysql_num_rows($exec) > 0) {

                $html .= "Invoice ID, Date, Amount, Tax, Tax 2, Total, Client, Organization";
                $i = 0;
                while($expense = mysql_fetch_array($exec)) {
                    $c_query = mysql_query("SELECT currency from tblclients where id='" . $expense['userid'] . "'") or die(mysql_error());
                    $c_result = mysql_fetch_array($c_query);
                        
                    $currency_symbol = '';
                    foreach ($results['currencies']['currency'] as $cr) {
                        if ($c_result['currency'] == $cr['id']) {
                            $currency_symbol = $cr['prefix'];
                            break;
                        }
                    }
                    
                    $i++;
                    $taxed_amount = ($expense['tax1'] == 0 || trim($expense['tax1']) == '' ? 0 : number_format((($expense['tax1'] * $expense['amount'])/100), 2, '.', ' ') );
                    
                    $date_1 = date($date_format_insert, strtotime($expense['date']));
                    
                    $html .= "\n{$expense['id']}, {$date_1}, $currency_symbol{$expense['subtotal']}, $currency_symbol{$expense['tax']},$currency_symbol{$expense['tax2']},$currency_symbol{$expense['total']}, {$expense['firstname']} {$expense['lastname']}, {$expense['companyname']}";
                }
                
                $html .= "";
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename='.'WHMCS_Invoices_'.date('Y-m-d', $time1).'_to_'.date('Y-m-d', $time2).'.csv');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                //header('Content-Length: ' . filesize($file));
                ob_clean();
                flush();
                echo ($html);
                exit;
            } else {
                $html .= "No Expense in database.<br /> <a href='$modulelink&expense=add'>Click here</a> to add new expense.";
            }
        } else {
            $html .= "Error in query : <b>$query</b>";
        }
}
?>