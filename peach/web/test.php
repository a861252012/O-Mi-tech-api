<?php
$_COOKIE['abc'] = "123";


die;
$logPath =  '/app/logs/room'.date('Y-m').'.log';
echo $logPath;

$ord = json_decode('{"id":5744,"uid":100596,"roomtid":4,"created":"2017-03-11 16:53:23","starttime":"2017-03-11 16:55:00","invitetime":null,"duration":3300,"points":10000,"tickets":0,"status":0,"reuid":51866}',true);
print_r($ord);
if (!isset($ord['status'])) return false;
if ($ord['status'] == 1 || $ord['reuid'] == 0) return false;

$starttime = strtotime($ord['starttime']);
$endtime = strtotime($ord['starttime']) + $ord['duration'];

echo "bb";
if ('100596' == $ord['reuid'] && time() >= $starttime && time() <= $endtime){
    echo "aa";
    return true;
}

return false;