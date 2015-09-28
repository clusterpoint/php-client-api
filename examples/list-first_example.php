<?php

// includes
require_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // looking up 10 first documents - list only the name
  $listLastRequest = new CPS_ListFirstRequest(array('document' => 'no', 'name' => 'yes'), 0, 10);
  $listLastResponse = $cpsConnection->sendRequest($listLastRequest);
  foreach ($listLastResponse->getDocuments() as $id => $document) {
    echo $document->name . '<br />';
  }
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>
