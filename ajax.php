<?php
$action = filter_var($_REQUEST['action'], FILTER_SANITIZE_STRING);
$ajax = '';
switch($action) {
    case 'fetchProjectsForClient':
        include 'ajax_fetchProjectsForClient.php';
        break;
    case 'fetchProjectsForPostData':
        include 'ajax_fetchProjectsForPostData.php';
        break;
}
echo $ajax;
?>
