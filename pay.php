<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');

$momoSubno = $_GET['momo_subno'];
//$momoSubno = '966601924';

$amount = intval($_GET['amount']);
//$amount = 10.0;

$invoice = $_GET['invoice'];
//$invoice = '457689';

$invSubno = $_GET['inv_subno'];
//$invSubno = '966601124';


try{

    $db = DatabaseFactory::getDatabase(DatabaseType::MYSQL);
    
    $momo = MoMoPayment::getInstance('test', $db);
    //$momo->printConfig();
    // Make payment request on MOMO - ECW and saving all informations into the DB for later retrieve
    $paymentResult = $momo->makePayment('245'.$momoSubno, '245'.$invSubno,  $amount, $invoice);
    if ($paymentResult)
      echo "OK";
    else
      echo "NOT OK";
    $db->close();
}
catch(Exception $ex){
    echo "Exception: ".$ex->getMessage()."\n";
}

