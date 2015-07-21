<?php

// includes
require_once('config.php');
require_once('../cps_simple.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // setting HMAC keys, comment out is and standard username/password will be used
  $cpsConnection->setHMACKeys("USERKEY", "SIGNKEY");

  // uncomment this, if you would like to see request/response and verify, that username and password is not set
  //$cpsConnection->setDebug(true);

  // perform status command
  $cpsSimple = new CPS_Simple($cpsConnection);
  echo "Status response:\n";
  var_dump($cpsSimple->status());

} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>