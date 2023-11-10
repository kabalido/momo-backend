<?php
//namespace App\Http\Kudilib;
class Utils{

    private static function send_notification($notification) {
      $url = 'https://fcm.googleapis.com/fcm/send';
      /*if($device_type == "Android"){
            $fields = [
                'to' => $registatoin_ids,
                //'data' => $notification
            ];
      } else {
            $fields = array(
                'to' => $registatoin_ids,
                'notification' => $notification
            );
      }*/
      // Firebase API Key
      $headers = array('Authorization:key=AAAAKJN3K5U:APA91bG9tUlAb-7E6w5JwszCxViuEhTKAs8fd6l5AsOFTxfGfDHCs_MZtr_okMoxauXmnJBCRoXVaNunRtJiBUdYg0ccB1Fv6HY5rmi_KTTUvvmGnA6dDAy283ZQHefPRVs3JEMD-y_l','Content-Type:application/json');
     // Open connection
      $ch = curl_init();
      // Set the url, number of POST vars, POST data
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      // Disabling SSL Certificate support temporarly
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));
      $result = curl_exec($ch);
      //echo $result;
      if ($result === FALSE) {
          die('Curl failed: ' . curl_error($ch));
      }
      curl_close($ch);
  }
  
  public static function send_push_notification($title, $message, $token){
        $notification = [
            'title' => $title,
            'body' => $message,
        ];
        $arrNotification= [];

        $arrData = [
        'key1' => 'This is key 1',
        'key2' => 'This is the key 2'
        ];

        $arrNotification['to'] = $token;
        $arrNotification['notification'] = $notification;
        $arrNotification['data'] = $arrData;
        $arrNotification["sound"] = "default";
   
        self::send_notification($arrNotification);

    }
    public static function send_sms($input_str, $from, $to){

        $message = urlencode($input_str);
        $message = str_replace( array( '%5Cn' ), "%0A", $message );
        $ulr = "http://192.168.100.39:14013/cgi-bin/sendsms?to=$to&from=$from&user=mtn&password=mtn&text=$message&dlr-mask=1&smsc-id=SMPP_BULK";
        file_get_contents($ulr);
    }

    public static function makePaymentTabs($conn, $subno, $amount){
      //$db = DatabaseFactory::getDatabase(DatabaseType::ORACLE);
      $stmt = oci_parse($conn,'BEGIN eve_make_all_payment(:SUBNO, :AMOUNT, :OUTPUT); END;');                     
      oci_bind_by_name($stmt,':SUBNO', $subno, -1, SQLT_CHR );
      oci_bind_by_name($stmt,':AMOUNT', $amount, -1,  SQLT_INT );           
      // Declare your cursor         
      $OUTPUT_CUR = oci_new_cursor($conn);
      oci_bind_by_name($stmt,":OUTPUT", $output, -1, SQLT_CHR);    
      // Execute statement               
      oci_execute($stmt);
      return $output; 
    }
}

