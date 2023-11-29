<?php

class MoMoPayment
{

  private static $momoObj = null;
  private $config = null;
  private $env = 'test';
  private $token = null;
  private $issueAt = -1;
  private $expiresIn = -1;
  private ?IDatabase $db = null;

  private function __construct(string $env, IDatabase $db)
  {
    $this->env = $env;
    $this->db = $db;
    $this->config = $this->getConfig($env, $db);
  }

  public static function getInstance($env, $db)
  {
    if (self::$momoObj == null) {
      self::$momoObj = new MoMoPayment($env, $db);
    }
    return self::$momoObj;
  }

  public function getConfig($env)
  {
    $rows = $this->db->executeQuery("select * from config where env_type='$env'");
    return $rows;
  }

  public function printConfig()
  {
    print_r($this->config);
  }


  private function saveTokenInfo($tokenObj)
  {
    $env = $this->env;
    $token = $tokenObj->access_token;
    $issueAt = $tokenObj->issued_at;
    $expiresIn = $tokenObj->expires_in;

    //$this->issueAt = intdiv($issueAt, 1000);
    //$this->expiresIn = $expiresIn;
    //echo "\n--------------saiving token\n";
    $query = "UPDATE token_info SET token='$token', created_at=$issueAt, expires_in=$expiresIn, transdate=now() WHERE env_type='$env'";
    //echo "\n$query\n";
    return $this->db->executeNonQuery( $query );
  }

  private function saveTokenInfoLocal($tokenObj)
  {
    //$env = $this->env;
    /*$this->token = $tokenObj->access_token;
    $issueAt = $tokenObj->issued_at;
    $expiresIn = $tokenObj->expires_in;

    $this->issueAt = intdiv($issueAt, 1000);
    $this->expiresIn = $expiresIn;*/
    return true;
  }


  private function saveTransaction($msisdn, $invMsisdn, $amount, $invNum, $data)
  {

    $transId = $data['transid'];
    $statusCode = $data['madapi']['statusCode'];
    $madapiTransId = $data['madapi']['transid'];
    $status = $data['madapi']['status'];
    $httpStatus = $data['httpStatus'];


    //return $this->saveTransaction($msisdn, $invMsisdn, $amount, $invNum, $resp);

    $env = $this->env;

    $query = "INSERT INTO cdrs_transaction (transid, subno, amount, receiver, madapi_status_code, madapi_status, madapi_transid, invoice_subno, invoice_nr, flex_fld1, flex_fld2) values 
       ('$transId', '$msisdn', $amount, 'MTN_POSTPAID', '$statusCode', '$status', '$madapiTransId', '$invMsisdn' , $invNum ,  '$httpStatus', '$env')";
    return $this->db->executeNonQuery($query);
  }

  public function getTokenCache()
  {
    $env = $this->env;
    if ($this->db->getDbType() === DatabaseType::MYSQL){
      $query = "SELECT  token from token_info where env_type='$env' and TIMESTAMPDIFF ( SECOND, from_unixtime(created_at/1000, '%Y-%m-%d %H:%i:%s'), NOW() ) < expires_in - 300";
      //echo "\nMYSQL DB\n";
    }
    else
        $query = "SELECT  token from token_info where env_type='$env' and ((sysdate - (TO_DATE( '1970-01-01', 'YYYY-MM-DD' ) + NUMTODSINTERVAL( floor(created_at/ 1000), 'SECOND' )))*24*60*60) < expires_in - 300";
    $arr = $this->db->executeQuery($query);
    return $arr;
  }

  // validation using purely PHP
  public function isTokenValid2()
  {
    $expiresIn = $this->expiresIn;
    $issuedAt = $this->issueAt;
    $seconds = time() - $issuedAt - 300;
    echo "Token seconds expires = $seconds\n";

    return $seconds <= $expiresIn;
  }


  public function getAuthInfo()
  {

    // lookup in the cache if the token is still valid
    $tokenCacheArr = $this->getTokenCache();
    if (is_array($tokenCacheArr) && count($tokenCacheArr) > 0) {
      // token not yet expired. Must reuse it

      //echo "------------- Retrieving token from cache -----------\n";
      $this->token = $tokenCacheArr[0]['token'];
      return ['response' => TRUE, 'token' => $tokenCacheArr[0]['token'], 'curl_error' => '', 'from_cache' => TRUE, 'httpStatus' => ''];
    }
    //echo "------------- taking new token -----------\n";
    $endPointURL = $this->config[0]['base_url'] . '/oauth/access_token?grant_type=client_credentials';
    $madapiAuth  = $this->config[0]['auth_madapi'];

    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $endPointURL,
      CURLOPT_RETURNTRANSFER => true,
      //CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 5,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $madapiAuth,
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/x-www-form-urlencoded",
      ],
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $arrResp = ['response' => FALSE, 'token' => '', 'curl_error' => '', 'from_cache' => FALSE, 'httpStatus' => $httpStatusCode];
    if ($err) {
      //echo "cURL Error #:" . $err;
      $arrResp['curl_error'] = $err;
    } else {
      $obj = json_decode($response);
      // We save token information into the DB for later use and check validity
      $this->saveTokenInfo($obj);
      $arrResp['response'] = TRUE;
      $arrResp['token'] = $obj->access_token;
    }
    $this->token =  $arrResp['token'];

    return $arrResp;
  }

  public function makePayment($msisdn, $amount, $payeeId, $note, $url, $funcCallback)
  {
    $madApiResp = $this->makeMadapiPaymentRequest($msisdn, $amount, $payeeId, $note, $url);
    $arrParInfo = [];
    $arrParInfo['msisdn'] =  $msisdn;
    $arrParInfo['amount'] =  $amount;
    $arrParInfo['payeeId'] =  $payeeId;
    $arrParInfo['note'] =  $note;
    $arrParInfo['env'] = $this->env;
    // callback after performing payment
    $funcCallback($arrParInfo, $madApiResp);
  }


  private function makeMadapiPaymentRequest($msisdn, $amount, $payeeId, $note, $url)
  {
    $this->getAuthInfo();
    // TODO Check if token was retrieved successfully.
    $token = $this->token;
    //echo "token = $token\n";
    $endPointURL = $this->config[0]['base_url'] . '/payments';
    $momoAuth = $this->config[0]['auth_momo'];
    $correlatorId = str_replace(".", "", uniqid("", true)); // Unique ID
    $body = <<<BODY
    {
    "correlatorId": "$correlatorId",
    "callingSystem": "GNB_MOMO_PAYMENT",
    "transactionType": "Debit",
    "targetSystem": "ECW",
    "callbackURL": "$url",
    "channel": "MOMO",
    "externalTransactionId": "$correlatorId",
    "amount": {
        "amount": $amount,
        "units": "XOF"
    },
    "taxAmount": {
        "amount": 0,
        "units": "XOF"
    },
    "totalAmount": {
        "amount": $amount,
        "units": "XOF"
    },
    "payer": {
        "payerIdType": "MSISDN",
        "payerId": "$msisdn",
        "payerNote": "$note"
    },
    "payee": [
        {
            "amount": {
                "amount": $amount,
                "units": "XOF"
            },
            "taxAmount": {
                "amount": 0,
                "units": "XOF"
            },
            "totalAmount": {
                "amount": $amount,
                "units": "XOF"
            },
            "payeeIdType": "USER",
            "payeeId": "$payeeId",
            "payeeNote": null
        }
    ],
    "countryCode": "GNB"
}
BODY;
    //echo "$body\n";
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $endPointURL,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 5,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Content-Type: application/json",
        "X-Authorization: $momoAuth"
      ],
    ]);
    $arrResponse = ['status' => FALSE, 'curl_error' => '', 'httpStatus' => 0, 'madapi' => []];
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $arrResponse['httpStatus'] = $httpCode;
    curl_close($ch);
    if ($err) {
      $arrResponse['status'] = FALSE;
      $arrResponse['curl_error'] = $err;
      echo "cURL Error #:" . $err;
    } else {
      // All OK
      $ob = json_decode($response);
      $arrResponse['status'] = TRUE;
      $arrResponse['transid'] = $correlatorId;
      $arrResponse['madapi']['statusCode'] = $ob->statusCode;
      $arrResponse['madapi']['transid'] = $ob->providerTransactionId;
      $arrResponse['madapi']['status'] = $ob->data->status;
    }
    return $arrResponse;
  }

  public function getTransactionInfo($transId, $correlatorId)
  {

    $this->getAuthInfo();
    $token = $this->token;
    echo "getTransaction method - TOken = $token\n";
    $endPointURL = $this->config[0]['base_url'] . "/payments/$correlatorId/transactionStatus";
    $momoAuth = $this->config[0]['auth_momo'];

    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $endPointURL,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 50,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Content-Type: application/json",
        "X-Authorization: $momoAuth",
        "transactionId: $transId"
      ],
    ]);

    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    $arrResponse = ['status' => FALSE, 'curl_error' => '', 'httpStatus' => $httpStatus, 'madapi' => []];
    curl_close($ch);
    if ($err) {
      $arrResponse['status'] = FALSE;
      $arrResponse['curl_error'] = $err;
    } else {
      $ob = json_decode($response);
      print_r($ob);
      if ($httpStatus != 200) {
        $arrResponse['madapi']['statusCode'] = $ob->statusCode;
        $arrResponse['madapi']['statusMessage'] = $ob->statusMessage;
        $arrResponse['madapi']['supportMessage'] = $ob->supportMessage;
        return $arrResponse;
      }
      $arrResponse['status'] = TRUE;
      $arrResponse['madapi']['statusCode'] = $ob->statusCode;
      $arrResponse['madapi']['statusMessage'] = $ob->statusMessage;
      $arrResponse['madapi']['madTransId'] = $ob->providerTransactionId;
      $arrResponse['madapi']['momoStatus'] = $ob->data->status;
      if ($ob->data->status == 'SUCCESSFUL')
        $arrResponse['madapi']['momoFinancialId'] = $ob->data->financialTransactionId;
    }
    return $arrResponse;
  }
} // end class MoMoPayment

/*include('MySql.class.php');

try{
	$mysql =  MySql::getInstance();
  $momo = MoMoPayment::getInstance('test', $mysql);
  $momo->printConfig();
  print_r($momo->makePayment('245966601471', 10));
  $mysql->close();
}
catch(Exception $e){
  echo "This is the error message ".$e->getMessage();

}*/
