<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');

$msisdn = $_GET['msisdn'];

//$msisdn = '966601124';

$db = DatabaseFactory::getDatabase(DatabaseType::ORACLE);

//$query = "select A.ar_ref ref, A.ar_invdate month,  sum(B.ar_am_loc) total_payment  from ivm_invoice_record A inner join ivm_invoice_detail B on A.ar_ref =B.ar_ref where A.subno='$msisdn' and ar_status !=9 group by A.ar_ref, A.ar_invdate order by A.ar_ref";

$query="select C.name, A.ar_ref ref, to_char(A.ar_invdate, 'DD/MM/YYYY') month,  ceil(sum(B.ar_am_loc)) total_payment  from ivm_invoice_record A inner join ivm_invoice_detail B on A.ar_ref =B.ar_ref 
   inner join crm_user_info C on A.subno=C.subno where A.subno='$msisdn' and ar_status !=9 group by C.name, A.ar_ref, A.ar_invdate order by A.ar_ref";

//sleep(3);

$rows = $db->executeQuery($query);
//print_r($rows);
$db->close();

header('Content-Type: application/json');
echo json_encode($rows);

