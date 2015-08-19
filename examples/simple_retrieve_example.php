<?php

// includes
require_once('config.php');
require_once('../cps_simple.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // creating a CPS_Simple instance
  $cpsSimple = new CPS_Simple($cpsConnection);

  // retrieving one document
  $document = $cpsSimple->retrieveSingle('id1');
  echo $document->name . '<br />';

  // retrieving multiple documents - listing only the name
  $documents = $cpsSimple->retrieveMultiple(array('id2', 'id3'));
  foreach ($documents as $id => $document) {
    echo $document->name . '<br />';
  }
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>