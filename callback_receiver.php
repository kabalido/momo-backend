<?php

// Testing:
// curl -X POST -H 'Content-type: application/json' -d @data.json http://localhost/madapitest/callback_receiver.php
// 
/*
$post_content = <<<END
{
    "receiverInfo": {
        "fromFri": "FRI:OVA_NAME/USER",
        "communicationchannel": "",
        "referenceid": "rrt-879470230037662228-c-geu1-18757-66252245-1",
        "status": "SUCCESSFUL"
    },
    "transactionid": "625476",
    "externaltransactionid": "654bc0a305bd6723561756"
}
END;
 */
//$request_type = 'POST';
$request_type = $_SERVER['REQUEST_METHOD'];
if ($request_type != 'POST') {
    echo "Only post is allowed!";
    exit;
}

include_once('DatabaseFactory.class.php');
include('MySql.class.php');
include('Utils.class.php');

$post_content = file_get_contents('php://input');
$ob = json_decode($post_content);

$callBackStatus = $ob?->receiverInfo?->status;
$ovaInfo = $ob?->receiverInfo?->fromFri;
$madapiTransid = $ob?->receiverInfo?->referenceid;
$momoTransid = $ob?->transactionid;
$appTransid = $ob?->externaltransactionid;
$success = FALSE;
if ($callBackStatus == "SUCCESSFUL") {
    $db = MySql::getInstance();
    $query = "SELECT subno, amount, invoice_subno, invoice_nr FROM cdrs_transaction WHERE transid='$appTransid'";
    $data = $db->executeQuery($query);
    $subno = $data[0]['subno'];
    $amount = $data[0]['amount'];
    $invSubno = substr( $data[0]['invoice_subno'], -9 ); // remove 245 prefixi to match numbers on tabs
    $invoice = $data[0]['invoice_nr'];
    $dateTime = new DateTime();
    $dateFormatted = $dateTime->format("d-m-Y H:i:s");
    $update = "UPDATE cdrs_transaction SET madapi_status='$callBackStatus', momo_transid='$momoTransid', momo_response_date=now() WHERE transid='$appTransid' and madapi_status = 'PENDING'";
    if ($db->executeNonQuery($update)) {
        $odb = DatabaseFactory::getDatabase(DatabaseType::ORACLE);
        $stat = Utils::makePaymentTabs($odb->conn, $invSubno, $amount );
        if ($stat == '0'){
            $success = TRUE;
            $userMessage = "Voce efetuou um pagamento em $dateFormatted, montante de $amount CFA referente a fatura $invoice,  ref: $appTransid";
            // send SMS to the user informing about the payment.
            Utils::send_sms($userMessage, 'MoMo', $subno);
        }
        $odb->close();
    }
    $db->close();
}
if ($success){
  header("Content-Type: application/json");
  echo json_encode(['statusCode' => '0000', 'externaltransactionid' => $appTransid]);
}
else{
  header("Content-Type: application/json");
  echo json_encode(['statusCode' => '4444', 'externaltransactionid' => $appTransid]);
}

?>
