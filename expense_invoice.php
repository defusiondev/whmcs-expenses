<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //  die('assorisation');
    /*
     * Create Invoice in WHMCS for the said cient
     */

    /*
     * Load expense details from db
     */
    $id = filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING);
    $sql_e = "SELECT * FROM `mod_expenses` WHERE id = $id LIMIT 1";
    if ($exec_e = mysql_query($sql_e)) {
        if (mysql_num_rows($exec_e) > 0) {
            $expense = mysql_fetch_array($exec_e);
        } else {
            header("Location:$modulelink&expense=list&msgid=16");
        }
    }

    if (trim($expense['vendor']) != '' && is_numeric($expense['vendor'])) {
        $sql_v = "SELECT * FROM `mod_vendors` WHERE `id` = {$expense['vendor']}";
        $exec_v = mysql_query($sql_v);
        $vendor_ = mysql_fetch_array($exec_v);
    }

    /*
     * Set variables
     */
    $amount = $expense['amount'];
    $date = date("m/d/Y", $expense['date']);
    $vendor = $expense['vendor'];
    $vendor_id = $vendor_['id'];

    // $taxes = $expense['taxes'];
    $taxes = $expense['tax1'];
    $tax_1 = $expense['tax1'];
    $tax_2 = $expense['tax2'];

    $assign_to_client = $expense['assign_to_client'];
    $client_id = $expense['client_id'];

    $sql_e_d = "
        SELECT e.*, c.name, cl.firstname, cl.lastname, p.name as pname, v.vendor as ven
        FROM `mod_expenses` as e
            LEFT JOIN `mod_categories` as c
                ON (e.category_id = c.id)
            LEFT JOIN `mod_projects` as p
                ON (e.project_id = p.id)
            LEFT JOIN `tblclients` as cl
            ON    (e.client_id = cl.id)
            LEFT JOIN `mod_vendors` as v
            ON    (e.vendor = v.id)
        WHERE e.id = $id LIMIT 1;
        ";
    if ($exec_e_d = mysql_query($sql_e_d)) {
        $expense_d = mysql_fetch_array($exec_e_d);
    }

    /**
     * Invoice create in WHMCS
     */
    $postfields["username"] = $username;
    $postfields["password"] = md5($password);
    $postfields["action"] = "createinvoice";
    $postfields["userid"] = $client_id;
    $postfields["date"] = date("Ymd");
    $postfields["duedate"] = date("Ymd");
    $postfields["paymentmethod"] = $_POST['payment_method'];
    $postfields["taxrate"] = ($taxes == 0) ? 0 : $tax_1;
    $postfields["sendinvoice"] = false;

    $time_stamp = $date;

    $postfields["itemdescription1"] = " $time_stamp : {$vendor_['vendor']}" . (". " . $expense_d['firstname'] . " " . $expense_d['lastname'] . ", " . $expense_d['pname']);
    $postfields["itemamount1"] = $expense['amount'];
    $postfields["itemtaxed1"] = 1;
     $taxed_amount = ($expense['tax1'] == 0 || trim($expense['tax1']) == '' ? 0 : ($expense['tax1'] * $expense['amount']) / 100 );
    if ($expense['inclusive'] == '1') {
        $expense['amount'] = $expense['amount'] - $taxed_amount;
    } else {
        $expense['amount'] = $expense['amount'] + $taxed_amount;
    }
    $taxed_amount1 = ($expense['tax2'] == 0 || trim($expense['tax2']) == '' ? 0 : ($expense['tax2'] * $expense['amount']) / 100 );

    $postfields["itemdescription2"] = " $time_stamp : {$vendor_['vendor']}" . (". " . $expense_d['firstname'] . " " . $expense_d['lastname'] . ", " . $expense_d['pname']);

    if ($expense['inclusive'] == '1') {
        $expense['amount'] = $postfields["itemamount1"];
    } else {
        $expense['amount'] = $expense['amount'] + $taxed_amount1;
    }
    $postfields["itemamount2"] = $expense['amount'];
    $postfields["itemtaxed2"] = 1;

    $postfields["itemamount2"];
    $values = array(
        'userid' => $postfields["userid"],
        'date' => $postfields["date"],
        'duedate' => $postfields["duedate"],
        'paymentmethod' => $postfields["paymentmethod"],
        'sendinvoice' => $postfields["sendinvoice"],
        'itemdescription' => $postfields["itemdescription1"],
        'itemamount' => $postfields["itemamount2"],
        'itemtaxed' => $postfields["itemtaxed1"]
    );
    $data = localAPI('createinvoice', $values);

    if ($data['result'] == 'success') {
        $invoiceId = $data['invoiceid'];
        $sql2 = "UPDATE `mod_expenses` SET `billed` = 2,`invoice_num`='$invoiceId',invoice_date='" . time() . "' WHERE `id` = $id";
        mysql_query($sql2);
        
        $sql3 = "UPDATE `tblinvoices` SET `tax`='".$expense['tax1']."',`tax2`='".$expense['tax2']."' WHERE `id` = $id";
        mysql_query($sql3); 
    }

    echo "Generated Invoice ID: " . $invoiceId;

    // Invoice generated
} else {

    /**
     * Fetch Payment Methods code begin
     */
    $postfields["username"] = $username;
    $postfields["password"] = md5($password);
    $postfields["action"] = "getpaymentmethods";
    $data = localAPI($postfields["action"]);

    $select = "";
    if ($data['totalresults'] > 0) {
        $select .= "<select name='payment_method'>";
        for ($i = 0; $i < $data['totalresults']; $i++) {
            $select .= "<option value='{$data['paymentmethods']['paymentmethod'][$i]['module']}' >";
            $select .= $data['paymentmethods']['paymentmethod'][$i]['displayname'] . " - " . $data['paymentmethods']['paymentmethod'][$i]['module'];
            $select .= "</option>";
        }
        $select .= "</select>";
    } else {
        $select .= "<select name='payment_method'>";
        $select .= "<option value='0' >";
        $select .= "No Payment Gateway";
        $select .= "</option>";
        $select .= "</select>";
    }
    $id = filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING);

    $form = "<div style='border:1px solid #CCCCCC; padding: 10px; width:auto;'>
                Select Payment Method
                <form action='" . $modulelink . "&expense=invoice' method='post'>
                    <input type='hidden' name='id' value='$id' />
                    <br />
                    Payment Method: $select
                    <br />
                    <input type='submit' name='submit' value='Create Invoice' />
                </form>
            </div>
        ";
    echo $form;
}
?>