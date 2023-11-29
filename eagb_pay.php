<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');

$post_content = file_get_contents('php://input');
//$post_content = '{"msisdn":"966601924","meter":"01451821407","price":600,"requestNumber":1}';

$ob = json_decode($post_content);
//print_r($ob);
$msisdn = $ob->msisdn;
$meter = $ob->meter;
$price = $ob->price;
//$requestNumber =  $ob->requestNumber;


function saveTransaction($mydb, $params, $data)
{
    global $meter, $price;
    $subno = $params['msisdn'];
    //$amount = $params['amount'];
    //$params['payeeId']
    //$invoice = $params['note'];
    $env = $params['env'];

    $transId = $data['transid'];
    $statusCode = $data['madapi']['statusCode'];
    $madapiTransId = $data['madapi']['transid'];
    $status = $data['madapi']['status'];
    $httpStatus = $data['httpStatus'];

    $query = "INSERT INTO cdrs_eagb (transid, subno, madapi_status_code, madapi_status, madapi_transid, eagb_meter, eagb_price, flex_fld1, flex_fld2) values 
       ('$transId', '$subno', '$statusCode', '$status', '$madapiTransId', '$meter', $price, '$httpStatus', '$env')";
    return $mydb->executeNonQuery($query);
}

try {

    $db = DatabaseFactory::getDatabase(DatabaseType::MYSQL);

    $momo = MoMoPayment::getInstance('test', $db);
    //$momo->printConfig();
    // Make payment request on MOMO - ECW and saving all informations into the DB for later retrieve
    // $msisdn, $amount, $payeeId, $note, $funcCallback
    $urlCallback = "https://172.25.50.178:3000/pay_test/eagb_callback.php";
    $momo->makePayment('245'.$msisdn, $price, 'MADAPI.SP', "EAGB.$msisdn", $urlCallback, function ($params, $res) {   
        global $db;
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
