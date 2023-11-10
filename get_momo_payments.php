<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');

$msisdn = '245'.$_GET['msisdn'];

//$msisdn = '245966601924';

$db = DatabaseFactory::getDatabase(DatabaseType::MYSQL);

$query = "select transid, amount, madapi_status, transdate, invoice_nr from cdrs_transaction where subno='$msisdn'";


$rows = $db->executeQuery($query);
$db->close();

header('Content-Type: application/json');
echo json_encode($rows);

