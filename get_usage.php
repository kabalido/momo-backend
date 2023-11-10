<?php
include_once('MomoPayment.class.php');
include_once('DatabaseFactory.class.php');

$msisdn = $_GET['msisdn'];

//$msisdn = '966601124';

$db = DatabaseFactory::getDatabase(DatabaseType::ORACLE);

$query="select case when b.chargetype='E' then 'ROAMING SMS'
    when   b.chargetype='J' then 'ROAMING Incoming' 
    when   b.chargetype='K' then 'ROAMING Data' 
    when   b.chargetype ='P' then 'Local Data'
    when   b.chargetype ='D' then 'Roaming voice'
    when   b.chargetype ='O' then 'Voice International'
    when   b.chargetype ='L' then 'Voice Local'
    when   b.chargetype ='F' then 'Call forwarding'
    when   b.chargetype ='S'then 'SMS local'
    when   b.chargetype ='R'then 'Rental'
    else 'other'
    end call_type,
    ceil(sum(b.billamount)) as usage from histcalls b where subno='$msisdn' and b.transdate between trunc((sysdate),'month') and TRUNC(LAST_DAY(sysdate)) 
    group by
    case when b.chargetype='E' then 'ROAMING SMS'
    when   b.chargetype='J' then 'ROAMING Incoming' 
    when   b.chargetype='K' then 'ROAMING Data' 
    when   b.chargetype ='P' then 'Local Data'
    when   b.chargetype ='D' then 'Roaming voice'
    when   b.chargetype ='O' then 'Voice International'
    when   b.chargetype ='L' then 'Voice Local'
    when   b.chargetype ='F' then 'Call forwarding'
    when   b.chargetype ='S'then 'SMS local'
    when   b.chargetype ='R'then 'Rental'
    else 'other'
    end";


$rows = $db->executeQuery($query);
//print_r($rows);
$db->close();

sleep(3);

//header('Content-Type: application/json');
echo json_encode($rows);

