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

  // deleting one document
  $cpsSimple->delete('id1');

  // deleting multiple documents
  $cpsSimple->delete(array('id2', 'id3'));
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>