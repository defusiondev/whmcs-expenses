<?php

function exp_getTotal($total_col, $tax1, $tax2) {
    if (is_numeric($tax1) && $tax1 > 0) {
        $taxed_amount = $total_col / 100;
        $taxed_amount *= $tax1;
    } else {
        $taxed_amount = 0;
    }
    
    if (is_numeric($tax2) && $tax2 > 0) {
        $taxed_amount2 = $total_col / 100;
        $taxed_amount2 *= $tax2;
    } else {
        $taxed_amount2 = 0;
    }                        
        
    $total_col += $taxed_amount;
    $total_col += $taxed_amount2;

    return $total_col;
}

function exp_getExTotal($total_col, $tax1, $tax2) {
    $itotal_tax = 0;
    $itotal_tax += $tax1;
    $itotal_tax += $tax2;
    
    $base_amount = $total_col / (100+$itotal_tax);
    
    if (is_numeric($tax1) && $tax1 > 0) {
        $taxed_amount = round($base_amount * $tax1, 2);
    } else {
        $taxed_amount = 0;
    }

    if (is_numeric($tax2) && $tax2 > 0) {
        $taxed_amount2 = round($base_amount * $tax2, 2);
    } else {
        $taxed_amount2 = 0;
    }
    
    $total_col -= $taxed_amount;
    $total_col -= $taxed_amount2;
    return $total_col;
}


// for today
$date = strtotime(date('Y-m-d').' 00:00:00');
$date1 = strtotime(date('Y-m-d').' 23:59:59');

$query1 = mysql_query("SELECT amount, tax1, tax2, fee, inclusive FROM mod_expenses where `date` BETWEEN '$date' AND '$date1'") or die(mysql_error());
$tax_in_amt = 0;
$tax_ex_amt = 0;
while ($expense = mysql_fetch_assoc($query1)) {
    $ex_total_col = $expense['amount'];
    $total_col = $expense['amount'];
    
    if($expense['inclusive']!='1'){
        $total_col = exp_getTotal($total_col, $expense['tax1'], $expense['tax2']);
    } else {
        $ex_total_col = exp_getExTotal($ex_total_col, $expense['tax1'], $expense['tax2']);
    }
    
    if (is_numeric($expense['fee']) && $expense['fee'] > 0) {
        $total_col += $expense['fee'];
        $ex_total_col += $expense['fee'];
    }
        
    $tax_in_amt += $total_col;
    $tax_ex_amt += $ex_total_col;
}

// for monthly
$date = mktime(0, 0, 0, date("n"), 1);
$date1 = mktime(23, 59, 0, date("n"), date("t"));

$query1 = mysql_query("SELECT amount, tax1, tax2, fee, inclusive FROM mod_expenses where `date` BETWEEN '$date' AND '$date1'") or die(mysql_error());
$tax_in_amt1 = 0;
$tax_ex_amt1 = 0;
while ($expense = mysql_fetch_assoc($query1)) {
    $total_col = $expense['amount'];
    $ex_total_col = $expense['amount'];

    if($expense['inclusive']!='1'){
        $total_col = exp_getTotal($total_col, $expense['tax1'], $expense['tax2']);
    } else {
        $ex_total_col = exp_getExTotal($ex_total_col, $expense['tax1'], $expense['tax2']);
    }
    
    if (is_numeric($expense['fee']) && $expense['fee'] > 0) {
        $total_col += $expense['fee'];
        $ex_total_col += $expense['fee'];
    }
    
    $tax_in_amt1 += $total_col;
    $tax_ex_amt1 += $ex_total_col;
}

// for yearly
$date = strtotime('1 January '.date('Y').' 00:00:00');
$date1 = strtotime('31 December '.date('Y').' 23:59:59');

$query1 = mysql_query("SELECT amount, tax1, tax2, fee, inclusive FROM mod_expenses where `date` BETWEEN '$date' AND '$date1'") or die(mysql_error());
$tax_in_amt2 = 0;
$tax_ex_amt2 = 0;
while ($expense = mysql_fetch_assoc($query1)) {
    $total_col = $expense['amount'];
    $ex_total_col = $expense['amount'];
    
    if ($expense['inclusive']!='1') {
        $total_col = exp_getTotal($total_col, $expense['tax1'], $expense['tax2']);
    } else {
        $ex_total_col = exp_getExTotal($ex_total_col, $expense['tax1'], $expense['tax2']);        
    }
    
    if (is_numeric($expense['fee']) && $expense['fee'] > 0) {
        $total_col += $expense['fee'];
        $ex_total_col += $expense['fee'];
    }
        
    $tax_in_amt2 += $total_col;
    $tax_ex_amt2 += $ex_total_col;
}

// Income stats
$currency = localAPI('getcurrencies');
$symbol = $currency['currencies']['currency'][0]['prefix'];
$results = localAPI("getstats");

?>
<table cellspacing="1" cellpadding="3" border="0" width="100%" class="datatable">
    <tr>
        <th></th><th>Total Income</th><th>Expense excl. Taxes &amp; Fees</th><th>Expenses Incl. Tax &amp; Fees</th>
    </tr>
    <tr>
        <td>Today</td>
        <td align="right"><?php echo $results['income_today'] ?></td>
        <td align="right"><?php echo $symbol.number_format($tax_ex_amt, 2, '.', '') ?></td>
        <td align="right"><?php echo $symbol.number_format($tax_in_amt, 2, '.', '') ?></td>
    </tr>
    <tr>
        <td>This Month</td>
        <td align="right"><?php echo $results['income_thismonth'] ?></td>
        <td align="right"><?php echo $symbol.number_format($tax_ex_amt1, 2, '.', '') ?></td>
        <td align="right"><?php echo $symbol.number_format($tax_in_amt1, 2, '.', '') ?></td>
    </tr>
    <tr>
        <td>This Year</td>
        <td align="right"><?php echo $results['income_thisyear'] ?></td>
        <td align="right"><?php echo $symbol.number_format($tax_ex_amt2, 2, '.', '') ?></td>
        <td align="right"><?php echo $symbol.number_format($tax_in_amt2, 2, '.', '') ?></td>
    </tr>

</table>