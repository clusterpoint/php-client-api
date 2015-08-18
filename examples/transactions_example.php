<?php
// includes
require_once 'cps_simple.php';

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection(ENDPOINT, STORAGE, USERNAME, PASSWORD, "document", "//document/id", array("account" => ACCOUNTID));
  $cpsSimple = new CPS_Simple($cpsConnection);
  
  // Insert 2 documents with balance
  $cpsSimple->updateMultiple(array(
    "1" => array("balance" => 1000),
    "2" => array("balance" => 1000)
  ));

  // Begin transaction
  $cpsSimple->beginTransaction();

  $docs = $cpsSimple->retrieveMultiple(array("1", "2"), DOC_TYPE_ARRAY);
  echo "Before update:\n"; var_dump($docs);

  // Increase balance and store in database
  $docs["1"]["balance"] -= 5;
  $docs["2"]["balance"] += 5;
  $cpsSimple->updateMultiple($docs);

  $docs = $cpsSimple->retrieveMultiple(array("1", "2"), DOC_TYPE_ARRAY);
  echo "After update:\n"; var_dump($docs);

  // Commit transaction
  $cpsSimple->commitTransaction();
  $docs = $cpsSimple->retrieveMultiple(array("1", "2"), DOC_TYPE_ARRAY);
  echo "After commit:\n"; var_dump($docs);
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
