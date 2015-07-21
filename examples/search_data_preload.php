<?php

// includes
require_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  $insertDocs = array();
  $insertDocs['id1'] = array(
    'category' => 'cars',
    'name' => 'Janis',
    'car_params' => array(
      'year' => '2011',
      'make' => 'Audi',
      'model' => 'A6'
    ),
    'persons' => array(
      'person' => array(
        'name' => 'Janis',
        'surname' => 'Karklins',
      )
    )
  );
  $insertDocs['id2'] = array(
    'category' => 'cars',
    'name' => 'Karlis',
    'car_params' => array(
      'year' => '2009',
      'make' => 'Audi',
      'model' => 'A6'
    ),
    'persons' => array(
      'person' => array(
        'name' => 'Janis',
        'surname' => 'Karklins',
      )
    )
  );
  $insertDocs['id3'] = array(
    'category' => 'mopeds',
    'name' => 'Aigars',
    'car_params' => array(
      'year' => '2012',
      'make' => 'Audi',
      'model' => 'A4'
    ),
    'persons' => array(
      'person' => array(
        'name' => 'Janis',
        'surname' => 'Karklins',
      )
    )
  );
  $insertDocs['id4'] = array(
    'category' => 'cars',
    'name' => 'Janis',
    'car_params' => array(
      'year' => '2010',
      'make' => 'Audi',
      'model' => 'A5'
    ),
    'persons' => array(
      'person' => array(
        'name' => 'Janis',
        'surname' => 'Karklins',
      )
    )
  );
  $insertDocs['id5'] = array(
    'category' => 'mopeds',
    'name' => 'Janis',
    'car_params' => array(
      'year' => '2011',
      'make' => 'Audi',
      'model' => 'A6'
    ),
    'persons' => array(
      'person' => array(
        'name' => 'Janis',
        'surname' => 'Karklins',
      )
    )
  );
  $insertDocs['id6'] = array(
    'category' => 'mopeds',
    'name' => 'Janis',
    'persons' => array(
      'year' => '2011',
      'make' => 'Audi',
      'model' => 'A6'
    ),
    'persons' => array(
      'person' => array(
        'name' => 'Janis',
        'surname' => 'Karklins',
      )
    )
  );


  // Insert
  $insertRequest = new CPS_InsertRequest($insertDocs);
  $cpsConnection->sendRequest($insertRequest);

} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>