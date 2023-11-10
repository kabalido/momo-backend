<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');

$db = DatabaseFactory::getDatabase(DatabaseType::MYSQL);

$query = "select id, title, description, url from ads";


$rows = $db->executeQuery($query);
$db->close();

header('Content-Type: application/json');
echo json_encode($rows);

