<?php

// Begin Check Function

function exp_check_license($whmcsurl, $licensing_secret_key, $licensekey, $localkey = "") {
    $checkdate = date("Ymd"); # Current date
    $usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
    $localkeydays = 0; # How long the local key is valid for in between remote checks
    $allowcheckfaildays = 4; # How many days to allow after local key expiry before blocking access if connection cannot be made
    $localkeyvalid = false;
    if ($localkey) {
        $localkey = str_replace("\n", '', $localkey); # Remove the line breaks
        $localdata = substr($localkey, 0, strlen($localkey) - 32); # Extract License Data
        $md5hash = substr($localkey, strlen($localkey) - 32); # Extract MD5 Hash
        if ($md5hash == md5($localdata . $licensing_secret_key)) {
            $localdata = strrev($localdata); # Reverse the string
            $md5hash = substr($localdata, 0, 32); # Extract MD5 Hash
            $localdata = substr($localdata, 32); # Extract License Data
            $localdata = base64_decode($localdata);
            $localkeyresults = unserialize($localdata);
            $originalcheckdate = $localkeyresults["checkdate"];
            if ($md5hash == md5($originalcheckdate . $licensing_secret_key)) {
                $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $localkeydays, date("Y")));
                if ($originalcheckdate > $localexpiry) {
                    $localkeyvalid = true;
                    $results = $localkeyresults;
                    $validdomains = explode(",", $results["validdomain"]);
                    if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                        $localkeyvalid = false;
                        $localkeyresults["status"] = "Invalid";
                        $results = array();
                    }
                    $validips = explode(",", $results["validip"]);
                    if (count($validips) > 0) {
                        if (!in_array($usersip, $validips)) {
                            $localkeyvalid = false;
                            $localkeyresults["status"] = "Invalid";
                            $results = array();
                        }
                    }
                    if (isset($results['validdirectory']) && $results["validdirectory"] != dirname(__FILE__)) {
                        $localkeyvalid = false;
                        $localkeyresults["status"] = "Invalid";
                        $results = array();
                    }
                }
            }
        }
    }
    if (!$localkeyvalid) {
        $postfields["licensekey"] = $licensekey;
        $postfields["domain"] = $_SERVER['SERVER_NAME'];
        $postfields["ip"] = $usersip;
        $postfields["dir"] = dirname(__FILE__);
        if (function_exists("curl_exec")) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $whmcsurl . "modules/servers/licensing/verify.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            curl_close($ch);
        } else {
            $fp = fsockopen($whmcsurl, 80, $errno, $errstr, 5);
            if ($fp) {
                $querystring = "";
                foreach ($postfields AS $k => $v) {
                    $querystring .= "$k=" . urlencode($v) . "&";
                }
                $header = "POST " . $whmcsurl . "modules/servers/licensing/verify.php HTTP/1.0\r\n";
                $header.="Host: " . $whmcsurl . "\r\n";
                $header.="Content-type: application/x-www-form-urlencoded\r\n";
                $header.="Content-length: " . @strlen($querystring) . "\r\n";
                $header.="Connection: close\r\n\r\n";
                $header.=$querystring;
                $data = "";
                @stream_set_timeout($fp, 20);
                @fputs($fp, $header);
                $status = @socket_get_status($fp);
                while (!@feof($fp) && $status) {
                    $data .= @fgets($fp, 1024);
                    $status = @socket_get_status($fp);
                }
                @fclose($fp);
            }
        }
        if (!$data) {
            $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($localkeydays + $allowcheckfaildays), date("Y")));
            if ($originalcheckdate > $localexpiry) {
                $results = $localkeyresults;
            } else {
                $results["status"] = "Remote Check Failed";
                return $results;
            }
        } else {
            preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
            $results = array();
            foreach ($matches[1] AS $k => $v) {
                $results[$v] = $matches[2][$k];
            }
        }
        if ($results["status"] == "Active") {
            $results["checkdate"] = $checkdate;
            $data_encoded = serialize($results);
            $data_encoded = base64_encode($data_encoded);
            $data_encoded = md5($checkdate . $licensing_secret_key) . $data_encoded;
            $data_encoded = strrev($data_encoded);
            $data_encoded = $data_encoded . md5($data_encoded . $licensing_secret_key);
            $data_encoded = wordwrap($data_encoded, 80, "\n", true);
            $results["localkey"] = $data_encoded;
        }
        $results["remotecheck"] = true;
    }
    unset($postfields, $data, $matches, $whmcsurl, $licensing_secret_key, $checkdate, $usersip, $localkeydays, $allowcheckfaildays, $md5hash);
    return $results;
}

// End Check Function

/**
 * Function list
 */
// Function to display an Array in a human-readable form
function exp_pr($array, $die = false, $dieMessage = '') {
    if (is_array($array)) {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
    } else {
        echo '<pre>';
        echo $arrays;
        echo '</pre>';
    }
    if ($die) {
        die("$dieMessage");
    } else {
        echo $dieMessage;
    }
}

function exp_msgid2value($id, $return = true) {
    switch ($id) {
        case 1:
            $msgstr = "<div class='successbox'><strong>Category added successfully!</strong></div>";
            break;
        case 2:
            $msgstr = "<div class='errorbox'><strong>Category could not be added!</strong></div>";
            break;
        case 3:
            $msgstr = "<div class='infobox'><strong>Category updated successfully!</strong><br>Your changes have been saved successfully</div>";
            break;
        case 4:
            $msgstr = "<div class='errorbox'><strong>Category could not be updated successfully!</strong></div>";
            break;
        case 5:
            $msgstr = "<div class='successbox'><strong>Category deleted successfully!</strong></div>";
            break;
        case 6:
            $msgstr = "<div class='errorbox'><strong>Category could not be deleted!</strong></div>";
            break;
        case 7:
            $msgstr = "<div class='errorbox'><strong>Category could not be added because data was inappropriate!</strong></div>";
            break;
        case 8:
            $msgstr = "<div class='errorbox'><strong>Category could not be deleted because data was inappropriate!</strong></div>";
            break;
        case 9:
            $msgstr = "<div class='successbox'><strong>Expense added successfully!</strong></div>";
            break;
        case 10:
            $msgstr = "<div class='errorbox'><strong>Expense could not be added, database issue!</strong></div>";
            break;
        case 11:
            $msgstr = "<div class='successbox'><strong>Expense deleted successfully!</strong></div>";
            break;
        case 12:
            $msgstr = "<div class='errorbox'><strong>Expense could not be deleted!</strong></div>";
            break;
        case 13:
            $msgstr = "<div class='errorbox'><strong>Expense could not be deleted because data was inappropriate!</strong></div>";
            break;
        case 14:
            $msgstr = "<div class='infobox'><strong>Expense updated successfully!</strong><br>Your changes have been saved successfully</div>";
            break;
        case 15:
            $msgstr = "<div class='errorbox'><strong>Expense could not be updated successfully!</strong></div>";
            break;
        case 16:
            $msgstr = "<div class='errorbox'><strong>Invalid Expense id!</strong></div>";
            break;
        case 17:
            $msgstr = "<div class='successbox'><strong>Vendor added successfully!</strong></div>";
            break;
        case 18:
            $msgstr = "<div class='errorbox'><strong>Vendor could not be added!</strong></div>";
            break;
        case 19:
            $msgstr = "<div class='errorbox'><strong>Vendor could not be added because data was inappropriate!</strong></div>";
            break;
        case 20:
            $msgstr = "<div class='successbox'><strong>Vendor deleted successfully!</strong><br>Your changes have been saved successfully</div>";
            break;
        case 21:
            $msgstr = "<div class='errorbox'><strong>Vendor could not be deleted!</strong></div>";
            break;
        case 22:
            $msgstr = "<div class='errorbox'><strong>Vendor could not be deleted because data was inappropriate!</strong></div>";
            break;
        case 23:
            $msgstr = "<div class='infobox'><strong>Vendor updated successfully!</strong><br>Your changes have been saved successfully</div>";
            break;
        case 24:
            $msgstr = "<div class='errorbox'><strong>Vendor could not be updated successfully!</strong></div>";
            break;
    }
    if ($return) {
        return $msgstr;
    } else {
        echo $msgstr;
    }
}

function exp_menu($modulelink, $return = true) {
    $menu = "<style>
                            #tab a{
                             background-color: #EFEFEF;
                            border-left: 1px solid #CCCCCC;
                            border-radius: 4px 4px 0 0;
                            border-right: 1px solid #CCCCCC;
                            border-top: 1px solid #CCCCCC;
                            color: #000000;
                            display: block;
                            margin: 0;
                            padding: 0 10px;
                            text-decoration: none;
                            display : inline;   
                            }
                        </style><br><br>
                        <div id='tab' style='background-color: #FFFFFF;border-bottom: 1px solid #CCCCCC;'>
                        <b><a href='$modulelink&expense=list'>Expenses</a></b>
                        
                        <b><a href='$modulelink&category=list'>Categories</a></b>
                        
                        <b><a href='$modulelink&vendor=list'>Vendors</a></b>
                    
                        <b><a href='$modulelink&report=list'>Reports</a></b>
                        
                        <b><a href='$modulelink&report=stats'>Quick Stats</a></b>
                        </div>
                <br />
                <br />
            ";
    if ($return) {
        return $menu;
    } else {
        echo $menu;
    }
}

## Any updates here require updates to CRON.PHP !!
function exp_recurringFrequencyOptions($id = null) {
    $options = array();

    $options[1] = 'Weekly';
    $options[] = '2 Weeks';
    $options[] = '4 Weeks';

    $options[] = 'Monthly';
    $options[] = '2 Months';
    $options[] = '3 Months';
    $options[] = '6 Months';

    $options[] = 'Yearly';
    $options[] = '2 Years';
    $options[] = '3 Years';

    if (is_null($id)) {
        return $options;
    } else {
        return $options[$id];
    }
}

function exp_parseXml($ret) {
    $return = false;
    if (is_object($ret)) {
        $ret = (array) $ret;
        exp_parseXml($ret);
    }
    if (is_array($ret)) {
        foreach ($ret as $k => $v) {
            if (is_object($v)) {
                $return[$k] = exp_parseXml($v);
            } else {
                $return[$k] = $v;
            }
        }
    }
    return $return;
}

function exp_convertXmlToArray($xmlContent) {
    $return = false;
    $ret = simplexml_load_string($xmlContent);
    $return = exp_parseXml($ret);
    return $return;
}

function exp_getRecurringDate($date, $frequency, $untill = '', $end_date = '') {
    $string = exp_recurringFrequencyOptions($frequency);
    if ($untill != 1) {
        $string .= ' ending '.date($date_format_insert, $end_date);
    }
    return $string;
}

function exp_get_mime_type($file) {
        // our list of mime types
        $mime_types = array(
                "pdf"=>"application/pdf"
                ,"exe"=>"application/octet-stream"
                ,"zip"=>"application/zip"
                ,"docx"=>"application/msword"
                ,"doc"=>"application/msword"
                ,"xls"=>"application/vnd.ms-excel"
                ,"ppt"=>"application/vnd.ms-powerpoint"
                ,"gif"=>"image/gif"
                ,"png"=>"image/png"
                ,"jpeg"=>"image/jpg"
                ,"jpg"=>"image/jpg"
                ,"mp3"=>"audio/mpeg"
                ,"wav"=>"audio/x-wav"
                ,"mpeg"=>"video/mpeg"
                ,"mpg"=>"video/mpeg"
                ,"mpe"=>"video/mpeg"
                ,"mov"=>"video/quicktime"
                ,"avi"=>"video/x-msvideo"
                ,"3gp"=>"video/3gpp"
                ,"css"=>"text/css"
                ,"jsc"=>"application/javascript"
                ,"js"=>"application/javascript"
                ,"php"=>"text/html"
                ,"htm"=>"text/html"
                ,"html"=>"text/html"
        );

        $extension = strtolower(end(explode('.',$file)));

        return $mime_types[$extension];
}

?>