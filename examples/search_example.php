<?php
// includes
require_once 'cps_simple.php';

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection("unix:///usr/local/cps/storages/example/storage.sock", "example", "username","password");
  $cpsSimple = new CPS_Simple($cpsConnection);
  
  // Setting parameters
  // search for items with category == 'cars' and car_params/year >= 2010
  $query = CPS_Term('cars', 'category') . CPS_Term('>=2010', 'car_params/year');

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
  $ordering = CPS_NumericOrdering('car_params/year', 'descending');
  $documents = $cpsSimple->search($query, $offset, $docs, $list, $ordering);
  foreach ($documents as $id => $document) {
    echo $document->car_params->make . ' ' . $document->car_params->model . '<br />';
    echo 'first registration in ' . $document->car_params->year . '<br />';
  }
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
