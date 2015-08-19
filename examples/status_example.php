<?php

// includes
require_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // Retrieving status information
  $statusRequest = new CPS_StatusRequest();
  $statusResponse = $cpsConnection->sendRequest($statusRequest);
  $status = $statusResponse->getStatus();

  foreach ($status['shard'] as $shard)
    foreach ($shard['replica'] as $replica)
      echo 'Status ' . $replica['status']['index']['status'] . ' <br>';
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>

