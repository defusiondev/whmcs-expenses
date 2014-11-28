<?php
    # db connection
    require_once dirname(__FILE__).'/../../../dbconnect.php';
    require_once dirname(__FILE__).'/functions.php';
    
    $now = strtotime('today midnight');
    
    $frequencies = array(
        'last week' => strtotime('-1 week midnight'),
        'fortnight' => strtotime('-2 weeks midnight'),
        'four weeks' => strtotime('-4 weeks midnight'),
        '1 month' => strtotime('-1 month midnight'),
        '2 months' => strtotime('-2 months midnight'),
        '3 months' => strtotime('-3 months midnight'),
        '6 months' => strtotime('-6 months midnight'),
        'yearly' => strtotime('-1 year midnight'),
        '2 years' => strtotime('-2 years midnight'),
        '3 years' => strtotime('-3 years midnight'),
    );
    
    $x = 1;
    foreach ($frequencies as $key => $date) {
        echo "- Running for {$key}\n";
        $sql = 'SELECT * FROM mod_expenses WHERE `date` = "'.$date.'" AND frequency = '.$x.' AND recurring = 1 AND (untill = 1 OR (untill = 2 AND end_date <= "'.$now.'"))';
        $res = mysql_query($sql) or die("Error fetching data.\n");
        if (mysql_num_rows($res) < 1) {
            echo "No recurring expenses found.\n";
        } else {
            echo mysql_num_rows($res)." expenses found, processing...\n";
            while ($row = mysql_fetch_assoc($res)) {
                unset($row['id']);
                unset($row['attachment']);
                unset($row['billed']);
                unset($row['modified']);
                foreach ($row as $rk => $rv) {
                    $row[$rk] = mysql_escape_string($rv);
                }
                $row['date'] = $now;
                $row['created'] = time();
                $new_sql = 'INSERT INTO mod_expenses(`'.implode('`,`', array_keys($row)).'`) VALUES("'.implode('","', $row).'")';
                $new_res = mysql_query($new_sql) or die('Unable to save expense - '.$new_sql."\n");
                echo "> expense for {$row['amount']} added today\n";
            }
        }
        $x++;
    }
?>