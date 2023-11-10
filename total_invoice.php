<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');

$msisdn = $_GET['msisdn'];

//$msisdn = '966601124';

$db = DatabaseFactory::getDatabase(DatabaseType::ORACLE);

$q1 = "select subno, name, contrno from crm_user_info where subno='$msisdn' and prepost_paid='POST'";

$q2 = "select count(*) len, ceil(sum(B.ar_am_loc)) total_payment, max(b.ar_ref) ref, max(A.ar_invdate) month from ivm_invoice_record A inner join ivm_invoice_detail B on A.ar_ref =B.ar_ref where A.subno='$msisdn' and ar_status !=9";

/*$query ="select Y.contrno, Y.name, X.* from (select A.subno, count(*) len, ceil(sum(B.ar_am_loc)) total_payment, max(b.ar_ref) ref, max(A.ar_invdate) month from ivm_invoice_record A inner join ivm_invoice_detail B on A.ar_ref =B.ar_ref 
  where A.subno='$msisdn' and ar_status !=9 group by A.subno) X inner join 
crm_user_info Y on X.subno=Y.subno where Y.subno='$msisdn'";
 */

//sleep(3);

$rowsPost = $db->executeQuery($q1);
$isPost = count($rowsPost) > 0;
$arr = ['status' => 1, 'data' => null ];
if ($isPost){
  $rowsInv = $db->executeQuery($q2);
  $rowsInv[0]['NAME'] = $rowsPost[0]['NAME'];
  $arr['status'] = 0;
  $arr['data'] = $rowsInv[0];
}

$db->close();

header('Content-Type: application/json');
echo json_encode($arr);

