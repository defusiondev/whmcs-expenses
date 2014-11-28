<?php
if( $_SERVER["REQUEST_METHOD"] == "POST") {

    // Load both dates
     $time1 = $_REQUEST['date1'];
     $time2 = $_REQUEST['date2'];



    $sql = "
        SELECT e.*, c.name, cl.firstname, cl.lastname, p.name as pname, v.vendor as ven, e.notes
        FROM `mod_expenses` as e
            LEFT JOIN `mod_categories` as c
                ON (e.category_id = c.id)
            LEFT JOIN `mod_projects` as p
                ON (e.project_id = p.id)
            LEFT JOIN `tblclients` as cl
            ON    (e.client_id = cl.id)
            LEFT JOIN `mod_vendors` as v
            ON    (e.vendor = v.id)
        WHERE e.date >= $time1 AND e.date <= $time2
        ORDER BY e.date desc
        ";
        if($exec = mysql_query($sql)) {
            if (mysql_num_rows($exec) > 0) {

                $html .= "Date, Category, Vendor, Amount, Tax, Tax 2, Fee, Total, Client, Notes";
                $i = 0;
                while($expense = mysql_fetch_array($exec)) {
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
                    
                    
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename='.'WHMCS_Expenses_'.date('Y-m-d', $time1).'_to_'.date('Y-m-d', $time2).'.csv');
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    //header('Content-Length: ' . filesize($file));
                    ob_clean();
                    flush();

                    $html .= "\n".date('Y-m-j', $expense['date'])
                                .", {$expense['name']}". ", {$expense['ven']} , ".$expense['currency'].number_format($amount_col, 2, '.', ' ')
                                .", ".$expense['currency'].number_format($taxed_amount, 2, '.', ' ') ."({$expense['tax1']}%)"
                                .", ".$expense['currency'].number_format($taxed_amount2, 2, '.', ' ') ."({$expense['tax2']}%),".
                                      $expense['currency'].number_format($expense['fee'], 2, '.', ' ').",".
                                      $expense['currency'].number_format($total_col, 2, '.', ' ') .","
                                .", {$expense['firstname']}{$expense['pname']}"
                                .",\"{$expense['notes']}\"";
                }
                $html .= "
                    ";
                echo ($html);
                exit;
            } else {
                //$html .= "No Expense in database.<br /> <a href='$modulelink&expense=add'>Click here</a> to add new expense.";
            }
        } else {
            $html .= "Error in query : <b>$query</b>";
        }
}
?>