<?php

// includes
require_once('config.php');
require_once('../cps_simple.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new cps\CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // creating a CPS_Simple instance
  $cpsSimple = new cps\CPS_Simple($cpsConnection);

  // search for items with category == 'cars' and car_params/year >= 2010
  $query = cps\CPS::Term('cars', 'category') . cps\CPS::Term('>=2010', 'car_params/year');
  // return documents starting with the first one - offset 0
  $offset = 0;
  // return not more than 5 documents
  $docs = 5;
  // return these fields from the documents
  $list = array(
    'id' => 'yes',
    'car_params/make' => 'yes',
    'car_params/model' => 'yes',
    'car_params/year' => 'yes'
  );
  // order by year, from largest to smallest
  $ordering = cps\CPS::NumericOrdering('car_params/year', 'descending');
  $documents = $cpsSimple->search($query, $offset, $docs, $list, $ordering);
  foreach ($documents as $id => $document) {
    echo $document->car_params->make . ' ' . $document->car_params->model . '<br />';
    echo 'first registration in ' . $document->car_params->year . '<br />';
  }
} catch (cps\CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>