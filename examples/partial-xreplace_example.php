<?php

// includes
require_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));
//  $cpsConnection->setDebug(1);
  //=========== adding a new subdocument
  $subdocument = array(
    'person' => array(
      'name' => 'Josh',
      'surname' => 'Smith',
    )
  );
  $ids = array('id1', 'id2');

  // Perform additions
  $prxRequest = new CPS_PartialXRequest($ids, new CPS_PRX_Operation('/document/persons', 'append_children', $subdocument));
  $response = $cpsConnection->sendRequest($prxRequest);


  //=========== sending multiple changesets at once
  $subdocument = array(
    'person' => array(
      'name' => 'George',
      'surname' => 'Smith',
    )
  );
  $changeset1 = new CPS_PRX_Changeset('id1', new CPS_PRX_Operation('/document/persons', 'append_children', $subdocument));


  $subdocument2 = array(
    'birth_year' => '1960'
  );
  $changeset2 = new CPS_PRX_Changeset('id2', new CPS_PRX_Operation('/document/persons/person[name="Josh"]', 'merge_children', $subdocument2));

  // Perform changes
  $prxRequest = new CPS_PartialXRequest(array($changeset1, $changeset2));
  $response = $cpsConnection->sendRequest($prxRequest);


} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>

