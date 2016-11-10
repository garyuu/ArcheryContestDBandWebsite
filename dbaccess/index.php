<?php
require_once('mysql_services.php');
require_once('hash_key.conf.php')

$command = $_POST['command'];
$signature = $_POST['signature'];
$hash_algo = 'sha256';
$hash_key = HASH_KEY;

if (hash_hmac($hash_algo, $command, $hash_key) != $signature)
    $resp = array('status' => "Denied");
else
{
    $resp = array('status' => "Access");
    $data = json_decode($command, true);
    switch ($data['action']){
    case "allplayerlist":
        $resp = sql_allplayerlist($data, $resp);
        break;
    case "allwavelist":
        $resp = sql_allwavelist($data, $resp);
        break;
    case "playerlistofposition":
        $resp = sql_playerlistofposition($data, $resp);
        break;
    case "savewave":
        $resp = sql_savewave($data, $resp);
        break;
    case "teamscorelist":
        $resp = sql_teamscorelist($data, $resp);
        break;
    default:
        $resp['error'] = "Unknown command";
    } // End switch
}
echo json_encode($resp);
?>
