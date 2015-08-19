<?php

// includes
include_once('config.php');
require_once('../cps_api.php');

$config['database'] = 'public::osm_poi';

try {
  // creating a CPS_Connection instance
  $cpsConnection = new CPS_Connection($config['connection'], $config['database'], $config['username'], $config['password'],
    'document', '//document/id', array('account' => $config['account']));
  $cpsConnection->setDebug(1);

  $offset = 0;
  $docs = 30;
  $listXml = '<id>yes</id>  <lat>yes</lat>  <lon>yes</lon>';

  $query = '{' . CPS_Term(" ><exampleshape1") . CPS_Term(" ><exampleshape2") . '}';
//  $query = CPS_Term(" ><exampleshape2");


//  $query = CPS_Term('cars', 'category') . CPS_Term('>=2010', 'car_params/year');

  $searchRequest = new CPS_SearchRequest($query, $offset, $docs);
  $searchRequest->setParam('list', $listXml);


  $shape1 = CPS_CircleDefinition("exampleshape1", array(48.060736, -2.6902734), '1 km', 'lat', 'lon');


  $shape2 = CPS_PolygonDefinition("exampleshape2", array(array(50.5735874, 9.9972383), array(50.574689, 10.006046), array(50.564493, 10.008982)), 'lat', 'lon');


  $shapes = '<shapes>
             <exampleshape1>
                <center>48.060736 -2.6902734</center>
                <radius>1 km</radius>
                <coord1_tag_name>lat</coord1_tag_name>
                <coord2_tag_name>lon</coord2_tag_name>
            </exampleshape1>
            <exampleshape2>50.5735874 9.9972383; 50.574689 10.006046; 50.564493 10.008982;
                <coord1_tag_name>lat</coord1_tag_name>
                <coord2_tag_name>lon</coord2_tag_name>
            </exampleshape2>
            </shapes>';


////<coord1_tag_name>lat</coord1_tag_name>
////<coord2_tag_name>lon</coord2_tag_name>
//
//  search_req.setShape(cps.CircleDefinition("exampleshape1", [48.060736, -2.6902734], '1 km', 'lat', 'lon'));
////search_req.setShape([cps.CircleDefinition("exampleshape1", [48.060736, -2.6902734], '1 km', 'lat', 'lon'), cps.CircleDefinition("exampleshape2", [50.5735874, 9.9972383], '1 km', 'lat', 'lon')]);
////search_req.setShape(cps.CircleDefinition("exampleshape2", [50.5735874, 9.9972383], '1 km', 'lat', 'lon'));
//  search_req.setShape(cps.PolygonDefinition("exampleshape2", [[50.5735874, 9.9972383], [50.574689, 10.006046],[50.564493, 10.008982]], 'lat', 'lon'));

//  $searchRequest->setExtraXmlParam($shapes);


  $searchRequest->setShape($shape1, false);
  $searchRequest->setShape($shape2, false);
//  $searchRequest->setShape(array($shape1, $shape2));

  $searchResponse = $cpsConnection->sendRequest($searchRequest);
  if ($searchResponse->getHits() > 0) {
    // getHits returns the total number of documents in the storage that match the query
    echo 'Found ' . $searchResponse->getHits() . ' documents<br />';
    echo 'showing from ' . $searchResponse->getFrom() . ' to ' . $searchResponse->getTo() . '<br />';
    foreach ($searchResponse->getDocuments() as $id => $document) {
      echo $document->lat . ' ' . $document->lon . "<br />\n
      ";
    }
  } else {
    echo 'Nothing found.';
  }
} catch (CPS_Exception $e) {
  var_dump($e->errors());
  exit;
}
?>