<?php

// includes
require_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // Looking up one document - listing name only
  $lookupRequest = new CPS_LookupRequest('id1', array('document' => 'no', 'name' => 'yes'));
  $lookupResponse = $cpsConnection->sendRequest($lookupRequest);
  foreach ($lookupResponse->getDocuments() as $id => $document) {
    echo $document->name . '<br />';
  }

  // Looking up multiple documents - listing name only
  $lookupRequest = new CPS_LookupRequest(array('id2', 'id3'), array('document' => 'no', 'name' => 'yes'));
  $lookupResponse = $cpsConnection->sendRequest($lookupRequest);
  foreach ($lookupResponse->getDocuments() as $id => $document) {
    echo $document->name . '<br />';
  }
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>