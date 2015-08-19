<?php

// includes
include_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));
$cpsConnection->setDebug(1);
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
  $listXml = '<id>yes</id>  <car_params><make>yes</make></car_params>  <car_params><model listas="modelis">yes</model></car_params>  <car_params><year>yes</year></car_params>';

  // order by year, from largest to smallest
  $ordering = CPS_NumericOrdering('car_params/year', 'descending');

  // Searching for documents
  // note that only the query parameter is mandatory - the rest are optional
  $searchRequest = new CPS_SearchRequest($query, $offset, $docs);
  $searchRequest->setParam('list', $listXml);
  $searchRequest->setOrdering($ordering);
  $searchResponse = $cpsConnection->sendRequest($searchRequest);
  if ($searchResponse->getHits() > 0) {
    // getHits returns the total number of documents in the storage that match the query
    echo 'Found ' . $searchResponse->getHits() . ' documents<br />';
    echo 'showing from ' . $searchResponse->getFrom() . ' to ' . $searchResponse->getTo() . '<br />';
    foreach ($searchResponse->getDocuments() as $id => $document) {
      echo $document->car_params->make . ' ' . $document->car_params->model . '<br />';
      echo 'first registration in ' . $document->car_params->year . '<br />';
    }
  } else {
    echo 'Nothing found.';
  }
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>