<?php
#include_once('DatabaseFactory.class.php');
#include('MySql.class.php');
#include('Utils.class.php');

function eagbExecute($jsonResp){
    $msisdn =  $jsonResp->msisdn;
    $meter = $jsonResp->meter;
    $price = $jsonResp->price;
    $method = $jsonResp->method;
    $baseUrl = 'http://10.100.2.179:90/mtngb-eagb-mobile?';

    $transId = $method == 'PAYMENT'? 'el_'.str_replace(".", "", uniqid("", true)): '';

    //echo "$transId\n";
    //echo "msisdn=$msisdn&meter=$meter&price=$price&requestNumber=1&transactionId=$transId&method=$method\n";
    $arrParams = [
        'PREVIEW' => "msisdn=$msisdn&meter=$meter&price=$price&method=$method",
        'PAYMENT' => "msisdn=$msisdn&meter=$meter&price=$price&requestNumber=1&transactionId=$transId&method=$method",
        'REGISTERED_METER'=> "msisdn=$msisdn&method=$method", // check if user already registered
        'REGISTER'=> "msisdn=$msisdn&meter=$meter&method=$method", // register contador
        'HISTORY' => "meter=$meter&method=$method",
    ];
    $url = $baseUrl.$arrParams[$method];
    //echo "$url\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

$post_content = file_get_contents('php://input');
//$post_content = '{"msisdn":"966601471","meter":"01451821407","price":600,"method":"PAYMENT"}';

$ob = json_decode($post_content);

$result = eagbExecute($ob);

header("Content-Type: application/json;  charset=utf-8");
echo $result;

?>
