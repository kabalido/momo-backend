<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');
include('Utils.class.php');

// MYSQL
$mdb = DatabaseFactory::getDatabase(DatabaseType::MYSQL);
$momo = MoMoPayment::getInstance('test', $mdb);
$momo->printConfig();

// Oracle
$odb = DatabaseFactory::getDatabase(DatabaseType::ORACLE);

/*
 * In oracle we can use:
 * SELECT (date2 - date1) * 24 * 60 AS minutes
 *
 * if DbType == Oracle
 *
 */
$momoSuccess = FALSE;
$rows = $mdb->executeQuery("SELECT transid, subno, amount, madapi_transid, transdate, invoice_subno, invoice_nr from cdrs_transaction where madapi_status='PENDING' AND TIMESTAMPDIFF ( MINUTE, transdate, NOW() ) >=1"); // transactions that have 60 minutes old
print_r($rows);
if ($rows) {
   foreach ($rows as $row) {
      $subno = $row['subno'];
      $amount = $row['amount'];
      $transId = $row['transid'];
      $madId = $row['madapi_transid'];
      $transdate = $row['transdate'];
	  $invSubno = substr($row['invoice_subno'], -9); // remove 245 to match TABS numbers
      $invoice = $row['invoice_nr'];
      echo "Madapi transid =$transId ----  $madId\n";
      $arr = $momo->getTransactionInfo($transId, $madId);
      print_r($arr);
      if ($arr['status'] && $arr['madapi']['statusCode'] == 'SUCCESSFUL'){
        $momoTransId = $arr['madapi']['momoFinancialId']; 
        if ($mdb->executeNonQuery("UPDATE cdrs_transaction SET madapi_status='SUCCESSFUL', momo_transid='$momoTransId', momo_response_date=now() WHERE transid='$transId'")){
			$stat = Utils::makePaymentTabs($odb->conn, $invSubno, $amount );
			if ($stat == '0'){
				$userMessage = "Voce realizou um pagamento em $transdate, montante de $amount CFA referente a fatura $invoice,  ref: $transId";
				// send SMS to the user informing about the payment.
				Utils::send_sms($userMessage, 'MoMo', $subno);
			}
		}
      }
   }
}
$mdb->close();
$odb->close();
?>
