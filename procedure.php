<?php
include_once('DatabaseFactory.class.php');
include('Utils.class.php');
$db = DatabaseFactory::getDatabase(DatabaseType::ORACLE);
$t = Utils::makePaymentTabs($db->conn, '966601971', 6749);
echo "$t\n";
$db->close();
