<?php

// includes
require_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new cps\CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // Retrieving one document
  $retrieveRequest = new cps\CPS_RetrieveRequest('id1');
  $retrieveResponse = $cpsConnection->sendRequest($retrieveRequest);
  foreach ($retrieveResponse->getDocuments() as $id => $document) {
    echo $document->category . '<br />';
  }

  // Retrieving multiple documents
  $retrieveRequest = new cps\CPS_RetrieveRequest(array('id2', 'id3'));
  $retrieveResponse = $cpsConnection->sendRequest($retrieveRequest);
  foreach ($retrieveResponse->getDocuments() as $id => $document) {
    echo $document->category . '<br />';
  }
} catch (cps\CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>