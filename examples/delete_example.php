<?php

// includes
require_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new cps\CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // Deleting one
  $deleteRequest = new cps\CPS_DeleteRequest('id1');
  $cpsConnection->sendRequest($deleteRequest);

  // Deleting multiple
  $deleteRequest = new cps\CPS_DeleteRequest(array('id2', 'id3'));
  $cpsConnection->sendRequest($deleteRequest);
} catch (cps\CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>