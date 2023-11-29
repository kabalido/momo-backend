<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');

//$momoSubno = $_GET['momo_subno'];
$momoSubno = '966601924';

//$amount = intval($_GET['amount']);
$amount = 10;

//$invoice = $_GET['invoice'];
$invoice = '437099';

//$invSubno = $_GET['inv_subno'];
$invSubno = '245966601971';


function saveTransaction($mydb, $params, $data)
{
    global $invoice,  $invSubno;
    $msisdn = $params['msisdn'];
    $amount = $params['amount'];
    //$params['payeeId']
    //$invoice = $params['note'];
    $env = $params['env'];

    $transId = $data['transid'];
    $statusCode = $data['madapi']['statusCode'];
    $madapiTransId = $data['madapi']['transid'];
    $status = $data['madapi']['status'];
    $httpStatus = $data['httpStatus'];

    $query = "INSERT INTO cdrs_transaction (transid, subno, amount, receiver, madapi_status_code, madapi_status, madapi_transid, invoice_subno, invoice_nr, flex_fld1, flex_fld2) values 
       ('$transId', '$msisdn', $amount, 'MTN_POSTPAID', '$statusCode', '$status', '$madapiTransId', '$invSubno' , $invoice ,  '$httpStatus', '$env')";
    return $mydb->executeNonQuery($query);
}

try {

    $db = DatabaseFactory::getDatabase(DatabaseType::MYSQL);

    $momo = MoMoPayment::getInstance('test', $db);
    //$momo->printConfig();
    // Make payment request on MOMO - ECW and saving all informations into the DB for later retrieve
    // $msisdn, $amount, $payeeId, $note, $funcCallback
    $callbackUrl = "https://172.25.50.178:3000/pay_test/callback_receiver.php";
    $momo->makePayment('245' . $momoSubno, $amount, 'MADAPI.SP', $invoice, $callbackUrl ,  function ($params, $res) {
        global $db;
        /*
        
        $params['msisdn']
        $params['amount']
        $params['payeeId']
        $params['note']
        $params['env']

        $arrResponse['httpStatus']
        $arrResponse['status'] = TRUE;
        $arrResponse['transid']
        $arrResponse['madapi']['statusCode']
        $arrResponse['madapi']['transid']
        $arrResponse['madapi']['status']

        */
        if ($res['status']) {
            $ok = saveTransaction($db, $params, $res);
            if ($ok)
                echo "OK";
            else
                echo "NOT OK";
        }
    });
    $db->close();
} catch (Exception $ex) {
    echo "Exception: " . $ex->getMessage() . "\n";
}
