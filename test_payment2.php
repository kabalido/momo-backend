<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');

//$msisdn = $_GET['msisdn'];
$msisdn = '966601924';

//$amount = $_GET['amount'];
$amount = 10;

//$invoice = $_GET['invoice'];
$invoice = '457689';

$subno = "245$msisdn";
$invSubno = '245966601971';

try{

    $db = DatabaseFactory::getDatabase(DatabaseType::MYSQL);
    
    //if ($db->isConnected()){
        $momo = MoMoPayment::getInstance('test', $db);
        $momo->printConfig();
        print_r($momo->makePayment($subno, $invSubno, $amount, $invoice));
        $db->close();
    //}
}
catch(Exception $ex){
    echo "Exception: ".$ex->getMessage()."\n";
}
