<?php
include_once('DatabaseFactory.class.php');
include('Utils.class.php');

// Testing:
// curl -X POST -H 'Content-type: application/json' -d @data.json http://localhost/madapitest/callback_receiver.php
// 

/*$post_content = <<<END
{
    "receiverInfo": {
        "fromFri": "FRI:OVA_NAME/USER",
        "communicationchannel": "",
        "referenceid": "rrt-4405967700965415441-b-geu1-4152-192424985-1",
        "status": "SUCCESSFUL"
    },
    "transactionid": "625476",
    "externaltransactionid": "656754d0dcc42713544933"
}
END;*/

//$request_type = 'POST';
$request_type = $_SERVER['REQUEST_METHOD'];
if ($request_type != 'POST') {
    echo "Only post is allowed!";
    exit;
}


function buyEagbCredit($params, $transid,  $ecwid){
  $msisdn = $params[0]['subno'];
  $meter = $params[0]['eagb_meter'];
  $price = $params[0]['eagb_price'];
  $url ="http://10.100.2.179:90/mtngb-eagb-mobile?msisdn=$msisdn&meter=$meter&price=$price&requestNumber=1&transactionId=$transid&method=PAYMENT&ecwTransactionId=$ecwid";
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $output = curl_exec($ch);
  curl_close($ch);
  return json_decode($output);

}

$post_content = file_get_contents('php://input');
$ob = json_decode($post_content);

$callBackStatus = $ob?->receiverInfo?->status;
$ovaInfo = $ob?->receiverInfo?->fromFri;
$madapiTransid = $ob?->receiverInfo?->referenceid;
$momoTransid = $ob?->transactionid;
$appTransid = $ob?->externaltransactionid;
$success = FALSE;
if ($callBackStatus == "SUCCESSFUL") {
  $db = DatabaseFactory::getDatabase(DatabaseType::MYSQL);

  $query = "SELECT subno, eagb_meter, eagb_price FROM cdrs_eagb WHERE transid='$appTransid'";
  $data = $db->executeQuery($query);

  $jsonobj = buyEagbCredit($data, $appTransid, $momoTransid);

  //print_r($jsonobj);
  $status = $jsonobj->status;

  if ($status == 0){
    
    $message = $jsonobj->message;
    $name = $jsonobj->name;
    $meter = $jsonobj->meter;
    $msisdn = $jsonobj->msisdn;
    $kwh = $jsonobj->kwh;
    $price = $jsonobj->price;
    $token = str_replace(' ', '',  $jsonobj->token);
    $eagbTransId = $jsonobj->eagbTransactionId;

    $update = "UPDATE cdrs_eagb SET madapi_status='$callBackStatus', momo_transid='$momoTransid', momo_response_date=now(), eagb_status='$status',
      eagb_message='$message', eagb_name='$name', eagb_kwh=$kwh, eagb_token='$token'
      WHERE transid='$appTransid' and madapi_status = 'PENDING'";

    $stat = $db->executeNonQuery($update);

    if ($stat){
        $dateTime = new DateTime();
        $dateFormatted = $dateTime->format("d-m-Y H:i:s");

        $success = TRUE;
        $userMessage = "Pagamento feito em $dateFormatted, montante de $price CFA referente a compra de luz. Codigo: $token,  ref: $appTransid";
        // send SMS to the user informing about the payment.
        Utils::send_sms($userMessage, 'MoMo', $msisdn);
    }

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
