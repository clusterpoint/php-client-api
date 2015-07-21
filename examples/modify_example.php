<?php

// includes
require_once('config.php');
require_once('../cps_api.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));
//  $cpsConnection->setDebug(1);
  // creating a new document
  $document = array(
    'title' => 'Test document',
    'body' => array(
      'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam a nisl magna.'
    )
  );

  $document2 = array(
    'title' => 'Document 2',
    'body' => array(
      'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam a nisl magna.'
    )
  );
  $document3 = array(
    'title' => 'Document 3',
    'body' => array(
      'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam a nisl magna.'
    )
  );

  // Insert
  $insertRequest = new CPS_InsertRequest('id1', $document);
  $cpsConnection->sendRequest($insertRequest);
  $insertRequest = new CPS_InsertRequest(array('id2' => $document2, 'id3' => $document3));
  $cpsConnection->sendRequest($insertRequest);

  // Update
  $document['title'] = 'changed title';
  $updateRequest = new CPS_UpdateRequest('id1', $document);
  $cpsConnection->sendRequest($updateRequest);
  $updateRequest = new CPS_UpdateRequest(array('id1' => $document, 'id3' => $document3));
  $cpsConnection->sendRequest($updateRequest);

  // Replace
  $document['title'] = 'changed title for replace';
  $replaceRequest = new CPS_ReplaceRequest('id1', $document);
  $cpsConnection->sendRequest($replaceRequest);
  $replaceRequest = new CPS_ReplaceRequest(array('id1' => $document, 'id3' => $document3));
  $cpsConnection->sendRequest($replaceRequest);

  // Partial Replace
  $document = array('title' => 'original name');
  $replaceRequest = new CPS_PartialReplaceRequest('id1', $document);
  $cpsConnection->sendRequest($replaceRequest);
  $replaceRequest = new CPS_PartialReplaceRequest(array('id1' => $document, 'id3' => $document3));
  $cpsConnection->sendRequest($replaceRequest);

} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>