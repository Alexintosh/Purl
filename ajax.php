<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
ini_set('max_execution_time', 900);

require_once('utils.php');

Utils::parse_url(urldecode($_POST['data']['url']));
?>
