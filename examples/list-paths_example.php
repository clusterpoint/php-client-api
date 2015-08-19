<?php

// includes
require_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // Retrieving paths
  $listPathsRequest = new CPS_ListPathsRequest();
  $listPathsResponse = $cpsConnection->sendRequest($listPathsRequest);
  $paths = $listPathsResponse->getPaths();
  foreach ($paths as $path)
    echo $path . '<br>';
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>