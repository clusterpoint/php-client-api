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
  $query = CPS_Term('555', 'id');
  // return documents starting with the first one - offset 0
  $offset = 0;
  // return not more than 5 documents
  $docs = 5;
  // return these fields from the documents
  $list = array();
  // Searching for documents
  // note that only the query parameter is mandatory - the rest are optional
  $searchRequest = new CPS_SearchRequest($query, $offset, $docs, $list);
  $searchResponse = $cpsConnection->sendRequest($searchRequest);
  if ($searchResponse->getHits() > 0) {
    // getHits returns the total number of documents in the storage that match the query
    echo 'Found ' . $searchResponse->getHits() . " documents\n";
var_dump($searchResponse->getDocuments()[555]->asXML());

  } else {
    echo 'Nothing found.';
  }
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>