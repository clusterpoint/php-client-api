<?php

// includes
require_once('config.php');
require_once('../cps_simple.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new cps\CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // creating a CPS_Simple instance
  $cpsSimple = new cps\CPS_Simple($cpsConnection);

  // retrieving status
  $status = $cpsSimple->status();
  foreach ($status['shard'] as $shard)
    foreach ($shard['replica'] as $replica)
      echo 'Status ' . $replica['status']['index']['status'] . ' <br>';
} catch (cps\CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>