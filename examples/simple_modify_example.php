<?php

// includes
require_once('config.php');
require_once('../cps_simple.php');

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));

  // creating a CPS_Simple instance
  $cpsSimple = new CPS_Simple($cpsConnection);

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
  $cpsSimple->insertSingle('id1', $document);
  $cpsSimple->insertMultiple(array('id2' => $document2, 'id3' => $document3));

  // Update
  $document['title'] = 'changed title';
  $cpsSimple->updateSingle('id1', $document);
  $cpsSimple->updateMultiple(array('id1' => $document, 'id3' => $document3));

  // Replace
  $document['title'] = 'changed title for replace';
  $cpsSimple->replaceSingle('id1', $document);
  $cpsSimple->replaceMultiple(array('id1' => $document, 'id3' => $document3));

  // Partial Replace
  $document = array('title' => 'original title');
  $cpsSimple->partialReplaceSingle('id1', $document);
  $cpsSimple->partialreplaceMultiple(array('id1' => $document, 'id3' => $document3));

} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}

?>