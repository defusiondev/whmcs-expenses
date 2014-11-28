<?php
$dateformat = mysql_query("SELECT * FROM tbladdonmodules where module='expenses' and setting='dateformat' order by setting DESC limit 1") or die(mysql_error());
$rrows = mysql_fetch_array($dateformat);
$newdateformat = $rrows['value']; // date format 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $error = array();

    // Check amount
    if (trim($_POST['amount']) == '' || !is_numeric($_POST['amount'])) {
        $error['amount'] = 'Amount cannot be blank and must be numeric';
    } else {
        $amount = filter_var($_POST['amount'], FILTER_SANITIZE_STRING);
    }
    
    // Check fee
    if (trim($_POST['fee']) != '' && !is_numeric($_POST['fee'])) {
        $error['fee'] = 'Fee must be blank or numeric';
    } else {
        $fee = filter_var($_POST['fee'], FILTER_SANITIZE_STRING);
    }    

    // Check Date
    if (trim($_POST['date']) == '') {
        $error['date'] = 'Date cannot be blank';
    } else {
        $date = filter_var($_POST['date'], FILTER_SANITIZE_STRING);
    }

    // Check Category
    if (trim($_POST['category']) == '' || !is_numeric($_POST['category']) || $_POST['category'] == 0) {
        $error['category'] = 'Category is blank or invalid';
    } else {
        $category_id = filter_var($_POST['category'], FILTER_SANITIZE_STRING);
    }

    // Check Vendor
    $new_vendor = false;
    if ($_POST['vendor_list'] == '0' && trim($_POST['vendor_text']) == '') {
        $vendor = '';
        $vendor_id = 0;
    } else {
        if (trim($_POST['vendor_text']) != '') {
            $vendor = (filter_var($_POST['vendor_text'], FILTER_SANITIZE_STRING));
            $new_vendor = true;
        } else {
            $vendor_id = (filter_var($_POST['vendor_list'], FILTER_SANITIZE_STRING));
        }
    }

    if ($new_vendor) {
        $sql_v_check = "SELECT * FROM `mod_vendors` WHERE `vendor` = '$vendor' LIMIT 1 ";
        $exec_v = mysql_query($sql_v_check);
        if (mysql_num_rows($exec_v) == 0) {
            $sql_v = "INSERT INTO `mod_vendors` (`vendor`) VALUES ('$vendor')";
            mysql_query($sql_v);
            $vendor_id = mysql_insert_id();
        } else {
            $vendor_r = mysql_fetch_array($exec_v);
            $vendor_id = $vendor_r['id'];
        }
    }

    // Check taxes
    $taxes = 1;
    $tax_1 = strtolower(filter_var($_POST['tax1'], FILTER_SANITIZE_STRING));
    $tax_2 = strtolower(filter_var($_POST['tax2'], FILTER_SANITIZE_STRING));

    $inclusive = strtolower(filter_var($_POST['inclusive'], FILTER_SANITIZE_STRING));
    if ($inclusive == 1) {
        //Reduce the taxed percentage from amount
        // Wrong formulae by JD - was out of my mind I guess.
        //$amount = $amount - ( ($tax_1/100)*$amount );
        // Correct formulae by
        //$amount = ($amount/(100+$tax_1))*100;
        $inclusive = '1';
    }

    //    $tax_2 = ($inclusive == 1 ? 1 : 0);
    // Check Recurring Info
    $recurring = 0;
    $freq = 0;
    $untill = 0;
    $end_date = 0;
    if (!isset($_POST['recurring']) || $_POST['recurring'] == 0 || trim($_POST['recurring']) == '') {
        $recurring = 0;
    } else {
        $recurring = 1;
        $freq = strtolower(filter_var($_POST['frequency'], FILTER_SANITIZE_STRING));
        $untill = strtolower(filter_var($_POST['untill'], FILTER_SANITIZE_STRING));
        $end_date = strtolower(filter_var($_POST['end_date'], FILTER_SANITIZE_STRING));
        if ($freq == 0) {
            $error['frequency'] = "Recurring Frequency Required";
        }
        if ($untill == 2 && $end_date == '') {
            $error['end_date'] = "Recurring End Date required";
        }
    }

    // Check Client Info
    $assign_to_client = 0;
    $client_id = 0;
    $project_id = 0;
    if ($_POST['client_id'] == 0 || trim($_POST['client_id']) == '') {
        $assign_to_client = 0;
    } else {
        $assign_to_client = 1;
        $client_id = strtolower(filter_var($_POST['client_id'], FILTER_SANITIZE_STRING));
        $project_id = strtolower(filter_var($_POST['project_id'], FILTER_SANITIZE_STRING));
    }

    $insert = false;
    if (count($error) > 0) {
        $insert = false;
    } else {
        $insert = true;
    }


    //----- Convert Date to timestamp format
    $endDate = strtotime($_REQUEST['end_date']);
    $date_db = strtotime($_REQUEST['date']);
    
    $notes = mysql_escape_string($_POST['notes']);

    if ($insert) {
        if (!empty($_FILES['ex_file']['name'])) {
            global $attachments_dir;
            $dir = $attachments_dir;
            $uploaddir = $dir.'/';

            $file = $_FILES['ex_file']['name']; // uploaded file name in database 

            move_uploaded_file($_FILES['ex_file']['tmp_name'], $uploaddir . $file);
        } else {
            $file = '';
        }

        $currency = $_POST['currency'];
        $time = time();
        echo $sql = "
            INSERT INTO 
                `mod_expenses`
                    (`currency`,`amount`, `fee`, `date`, `category_id`, `vendor`, `taxes`,`inclusive`, `tax1`, `tax2`, `recurring`, `frequency`, `untill`, `end_date`, `assign_to_client`, `client_id`, `project_id`, `created`, `notes`,`attachment`)
                VALUES
                    ('$currency','$amount', '$fee', '$date_db', '$category_id', '$vendor_id', '$taxes','$inclusive', '$tax_1', '$tax_2', '$recurring', '$freq', '$untill', '$endDate', '$assign_to_client', '$client_id', '$project_id', $time, '$notes','$file');
            "; 
        if (mysql_query($sql)) {
            header("Location:$modulelink&expense=list&msgid=9");
            exit();
        } else {
            header("Location:$modulelink&expenses=list&msgid=10");
        }
    }
} {

    $clients_exist = false;
    $sql = "SELECT id,firstname,companyname,lastname FROM tblclients order by firstname";
    $clientresult = localAPI("getclients", array('limitstart' => '0', 'limitnum' => '10000000'));

    if ($clientresult['numreturned'] > 0) {
        foreach ($clientresult['clients']['client'] as $client) {
            if ($project['client_id'] == $client['id']) {
                $select_client_list .= "<option selected='selected' value='{$client['id']}' >";
            } else {
                $select_client_list .= "<option value='{$client['id']}' >";
            }
            $select_client_list .= $client['firstname'] . " " . $client['lastname'];
            if ($client['companyname'] != '')
                $select_client_list .= ' (' . $client['companyname'] . ')';
            $select_client_list .= "</option>";
        }
    }
    /**
     * Fetch TAX RATES from tbltax table
     */
    // Tax 1

    $sql_t = "SELECT * FROM `tbltax` ORDER BY `name`;";
    if ($exec_t = mysql_query($sql_t)) {
        $tax1 .= "<option value='0' >No tax</option>";
        if (mysql_num_rows($exec_t) > 0) {
            while ($taxrate = mysql_fetch_array($exec_t)) {
                if ($taxrate['taxrate'] == $tax_1) {
                    $tax1 .= "<option selected='selected' value='{$taxrate['taxrate']}' >" . $taxrate['name'] . " (" . $taxrate['taxrate'] . ")" . "</option>";
                } else {
                    $tax1 .= "<option value='{$taxrate['taxrate']}' >" . $taxrate['name'] . " (" . $taxrate['taxrate'] . ")" . "</option>";
                }
            }
        }
    }

    // Tax 2
    if ($exec_t = mysql_query($sql_t)) {
        $tax2 .= "<option value='0' >No tax</option>";
        if (mysql_num_rows($exec_t) > 0) {
            while ($taxrate = mysql_fetch_array($exec_t)) {
                if ($taxrate['taxrate'] == $tax_2) {
                    $tax2 .= "<option selected='selected' value='{$taxrate['taxrate']}' >" . $taxrate['name'] . " (" . $taxrate['taxrate'] . ")" . "</option>";
                } else {
                    $tax2 .= "<option value='{$taxrate['taxrate']}' >" . $taxrate['name'] . " (" . $taxrate['taxrate'] . ")" . "</option>";
                }
            }
        }
    }


    /**
     * Fetch VENDORs from mod_vendors table
     */
    $sql_v = "SELECT * FROM `mod_vendors` ORDER BY `vendor`;";
    if ($exec_v = mysql_query($sql_v)) {
        $vendor_select .= "<option value='0' >--Select Vendor--</option>";
        if (mysql_num_rows($exec_v) > 0) {
            while ($vend = mysql_fetch_array($exec_v)) {
                if ($vend['vendor'] == $vendor) {
                    $vendor_select .= "<option selected='selected' value='{$vend['id']}' >" . $vend['vendor'] . "</option>";
                } else {
                    $vendor_select .= "<option value='{$vend['id']}' >" . $vend['vendor'] . "</option>";
                }
            }
        }
    }
    ?>
    <script type="text/javascript">
        $(function() {
            $("#date_expense").datepicker({ dateFormat: "<?php echo $newdateformat; ?>" });
            $("#end_date").datepicker({ dateFormat: "<?php echo $newdateformat; ?>" });
            $('#amountbox').focus();
        });
        function loadProjectForClient() {
            client_id = $("#client_id").val();
            url = '../modules/addons/expenses/expenses.php?' + 'ajax=true&action=fetchProjectsForClient&client_id=' + client_id;
            $("#project_id").load(url);
        }
        function loadProjectForPostData() {
            client_id = $("#client_id").val();
            url = '../modules/addons/expenses/expenses.php?' + 'ajax=true&action=fetchProjectsForPostData&client_id=' + client_id+"&project_id="+<?php echo (isset($project_id) ? $project_id : "0") ?>;
            $("#project_id").load(url);
        }
    </script>
    <?php
        if (is_array($error) && count($error) > 0) {
            $error_msg = '';
            $errors=implode('<br> ',$error);
            
            echo "<div class='errormessagebox' style=' width: 786px;'>".$errors."</div>";
        }
        
        if (trim($date) == '') {
            if ($newdateformat == 'mm/dd/yy')
                $date = date("m/d/Y");
            else
                $date = date("d-m-Y");
        }
        
        $results = localAPI("getcurrencies"); // calling internal api for currency  added   on 14 may 2012 
    ?>

    <fieldset style="margin: 0 auto; width: 800px; border-color: #f7f7f7;">
        <form name="expense_add" method="post" enctype="multipart/form-data" action="<?php echo $modulelink ?>&expense=add">
            <input type="hidden" name="currency" value="<?php echo $results['currencies']['currency'][0]['prefix'] ?>"/>
            <table width="700" border="0">
                <tr valign="top">
                    <td>
                        <table width="100%" border="0">
                            <tr>
                                <td valign="top" class="text_newproject">New Expense</td>
                            </tr>
                            <tr>
                                <td valign="top" class="txt_projectinfo">
                                    Expense Information
                                </td>
                            </tr>
                            <tr>
                                <td valign="top">
                                    <table width="100%" border="0" cellspacing="0">
                                        <?php /* <tr>
                                            <td width="21%" class="textname_box">Currency</td>
                                            <td width="79%" class="text_project1">
                                                <select  name="currency">
                                                    <?php for ($i = 0; $i < count($results['currencies']['currency'][$i]); $i++) { ?>
                                                        <option value="<?php echo $results['currencies']['currency'][$i]['prefix']; ?>" ><?php echo $results['currencies']['currency'][$i]['prefix'] . ' ' . $results['currencies']['currency'][$i]['code']; ?></option>
                                                    <?php } ?>
                                                </select>
                                            </td>
                                        </tr> */ ?>
                                        <tr>
                                            <td width="21%" class="textname_box">Amount <span style="color:red">*</span></td>
                                            <td width="79%" class="text_project1">
                                                <input type="text" name="amount" id="amountbox" value="<?php echo $amount ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <td width="21%" class="textname_box">Vendor <span style="color:red">*</span></td>
                                            <td width="79%" class="text_project1">
                                                <select name="vendor_list" id="vendor_list" style="vertical-align: top">
                                                    <?php
                                                    echo $vendor_select;
                                                    ?>
                                                </select>
                                                or
                                                <input type="text" name="vendor_text" id="vendor_text" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="21%" class="textname_box">Category <span style="color:red">*</span></td>
                                            <td width="79%" class="text_project1">
                                                <select name="category" id="category">
                                                    <option value="0">--Select Category--</option>
                                                    <?php
                                                    $sql = "SELECT * FROM `mod_categories` ORDER BY `name`;";
                                                    if ($exec = mysql_query($sql)) {
                                                        if (mysql_num_rows($exec) > 0) {
                                                            $select_category = '';
                                                            while ($category = mysql_fetch_array($exec)) {
                                                                if ($category['id'] == $category_id) {
                                                                    $select_category .= "<option selected='selected' value='{$category['id']}'>{$category['name']}</option>";
                                                                } else {
                                                                    $select_category .= "<option value='{$category['id']}'>{$category['name']}</option>";
                                                                }
                                                            }
                                                            echo $select_category;
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="21%" class="textname_box">Date <span style="color:red">*</span></td>
                                            <td width="79%" class="text_project1">
                                                <input type="text" name="date" id="date_expense" value="<?php echo $date ?>" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="textname_box">
                                                Notes
                                            </td>
                                            <td class="text_project1">
                                                <textarea name="notes" rows="5" cols="40"></textarea>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td width="21%" class="textname_box">Fee <span style="color:red"></span></td>
                                            <td width="79%" class="text_project1">
                                                <input type="text" name="fee" id="feebox" value="<?php echo $fee ?>" />
                                            </td>
                                        </tr>
                                        
                                        <tr>
                                            <td width="21%" class="textname_box">Attachment</td>
                                            <td width="79%" class="text_project1">
                                                <input type="file" name="ex_file" id="ex_file">
                                            </td>
                                        </tr>                                        
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td valign="middle" class="txt_projectinfo">
                                    Tax Information
                                </td>
                            </tr>
                            <tr>
                                <td valign="top">
                                    <table width="100%" border="0" cellspacing="0">
                                        <tr>
                                            <td width="21%" class="textname_box">Tax Level 1</td>
                                            <td width="79%" class="text_project1">
                                                <select name="tax1" id="tax1">
                                                    <?php
                                                    echo $tax1;
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="21%" class="textname_box">Tax Level 2 </td>
                                            <td width="79%" class="text_project1">
                                                <select name="tax2" id="tax2">
                                                    <?php
                                                    echo $tax2;
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>    
                                        <tr>
                                            <td width="21%" class="textname_box">Inclusive </td>
                                            <td width="79%" class="text_project1">
                                                <input type="checkbox" name="inclusive" value="1" checked="checked" />
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" class="txt_projectinfo">
                                    Recurring Information
                                </td>
                            </tr>
                            <tr>
                                <td valign="top">
                                    <table width="100%" border="0" cellspacing="0">
                                        <tr>
                                            <td width="21%" class="textname_box">Recurring </td>
                                            <td width="79%" class="text_project1">
                                                <input type="checkbox" name="recurring" value="1" <?php echo ($recurring == 1 ? ' checked="checked"' : '') ?> />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="21%" class="textname_box">Frequency </td>
                                            <td width="79%" class="text_project1">
                                                <select name="frequency" id="frequency">
                                                    <option value="0">--Select--</option>
                                                    <?php
                                                    $options = exp_recurringFrequencyOptions();
                                                    $select_options = '';
                                                    foreach ($options as $k => $option) {
                                                        if ($k == $freq) {
                                                            $select_options .= "<option selected='selected' value='{$k}'>{$option}</option>";
                                                        } else {
                                                            $select_options .= "<option value='{$k}'>{$option}</option>";
                                                        }
                                                    }
                                                    echo $select_options;
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="21%" class="textname_box">Until </td>
                                            <td width="79%" class="text_project1">
                                                <select name="untill" id="untill">
                                                    <option value="1" <?php echo ($untill == 1 ? ' selected="selected"' : '') ?>>Forever</option>
                                                    <option value="2" <?php echo ($untill == 2 ? ' selected="selected"' : '') ?>>End Date</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="21%" class="textname_box">If "End Date" </td>
                                            <td width="79%" class="text_project1">
                                                <input type="text" name="end_date" id="end_date" value="" />
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td valign="middle" class="txt_projectinfo">
                                    Assign to Client
                                </td>
                            </tr>
                            <tr>
                                <td valign="top">
                                    <table width="100%" border="0" cellspacing="0">
                                        <tr>
                                            <td width="21%" class="textname_box">Client </td>
                                            <td width="79%" class="text_project1">
                                                <select name="client_id" id="client_id" onchange="loadProjectForClient();">
                                                    <option value="0">--Select Client--</option>
                                                    <?php
                                                    echo $select_client_list;
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="21%" class="textname_box">Project </td>
                                            <td width="79%" class="text_project1">
                                                <select name="project_id" id="project_id">
                                                    <option value="0">--Select--</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <script type="text/javascript">
                                            loadProjectForPostData();
                                        </script>
                                        <tr>
                                            <td class="textname_box">&nbsp;</td>
                                            <td class="text_project1">
                                                <br />
                                                <input type="submit" name="submit" value="Add Expense" />
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top">&nbsp;</td>
                            </tr>
                            <tr>
                                <td valign="top">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </form>
    </fieldset>
    <?php
}
?>