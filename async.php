<?php require __DIR__ . '/vendor/autoload.php';

use CommanderZiltoid\lempmetrics\Metrics;
$metrics = new Metrics();


$async = [];

if(isset($_GET['livecpu']) && $_GET['livecpu'] == '1'){
    $async['livecpu'] = Metrics::getCPUUsageLive();
}
if(isset($_GET['livememory']) && $_GET['livememory'] == '1'){
    $async['livememory'] = Metrics::getMemoryUsageLive();
}
if(isset($_GET['livedisk']) && $_GET['livedisk'] == '1'){
    $async['livedisk'] = Metrics::getDiskUsageLive();
}

echo json_encode($async);