<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');

$subno = $_GET['subno'];
$msisdn = "245$subno";
$password = sha1($_GET['password']);

//$subno = '966601124';
//$msisdn="245$subno";
//$password = sha1('12');

$dbm = DatabaseFactory::getDatabase(DatabaseType::MYSQL);
$dbo = DatabaseFactory::getDatabase(DatabaseType::ORACLE);

$query = "select subno from users where subno='$msisdn' and password = '$password' and is_active='1'";

$query2 = "select subno from crm_user_info where subno='$subno' and prepost_paid='POST'";

$updateStm = "update users set last_login_date=NOW() where subno='$msisdn'";

$rows = $dbm->executeQuery($query);
$dbm->executeNonQuery($updateStm);

$postRows = $dbo->executeQuery($query2);

$dbo->close();
$dbm->close();

$size = count($rows);
$isPost = count($postRows) > 0;

if ($size > 0)
    $arr = ['count' => $size, 'subno' => $rows[0]['subno'], 'isPost' => $isPost];
else
    $arr = ['count' => 0, 'subno' => null, 'isPost' => false];
header('Content-Type: application/json');
echo json_encode($arr);

