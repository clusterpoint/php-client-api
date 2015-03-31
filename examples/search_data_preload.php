<?php

// includes
require_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new cps\CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  $insertDocs = array();
  $insertDocs['id1'] = array(
    'category' => 'cars',
    'car_params' => array(
      'year' => '2011',
      'make' => 'Audi',
      'model' => 'A6'
    )
  );
  $insertDocs['id2'] = array(
    'category' => 'cars',
    'car_params' => array(
      'year' => '2009',
      'make' => 'Audi',
      'model' => 'A6'
    )
  );
  $insertDocs['id3'] = array(
    'category' => 'cars',
    'car_params' => array(
      'year' => '2012',
      'make' => 'Audi',
      'model' => 'A4'
    )
  );
  $insertDocs['id4'] = array(
    'category' => 'cars',
    'car_params' => array(
      'year' => '2010',
      'make' => 'Audi',
      'model' => 'A5'
    )
  );
  $insertDocs['id5'] = array(
    'category' => 'mopeds',
    'car_params' => array(
      'year' => '2011',
      'make' => 'Audi',
      'model' => 'A6'
    )
  );


  // Insert
  $insertRequest = new cps\CPS_InsertRequest($insertDocs);
  $cpsConnection->sendRequest($insertRequest);

} catch (cps\CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>