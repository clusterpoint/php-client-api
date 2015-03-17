<?php

// includes
require_once 'cps_simple.php';

try {
  define('DO_CPS', 1);
  define('DO_MYSQL', 0);
  define('DO_MONGO', 0);
  define('MONGO_SAFE', true);

  define('DELAYED_REQUESTS', false);
  define('DELAYED_REQUESTS_FILE', '/tmp/insert_input.data');
  define('TOTAL_DOCS', 100000);
  define('ID_OFFSET', 1);
  define('DOCS_PER_REQUEST', 1000);
  define('RANDOM_SEED', 123456);
  
  // creating a CPS_Connection instance
//    $cpsConnection = new CPS_Connection('http://192.168.0.52/cgi-bin/cps2-andrejs/cps2-cgi', 'leta3m', 'root', 'root123');
//	$cpsConnection = new CPS_Connection('tcp://192.168.0.52:5570', 'leta3m', 'root', 'root123');
  $cpsConnection = new CPS_Connection('tcp://192.168.0.55:5570', 'performance_test3', 'root', 'password');
//	$cpsConnection = new CPS_Connection('unix:///home/andrejs/cps2root/storages/test/storage.sock', 'test2', 'root', 'password');
//  $cpsConnection->setDebug(true);
  $cpsSimple = new CPS_Simple($cpsConnection);
  
/*  $ids = array();
  for ($x = 0; $x < TOTAL_DOCS; ++$x) {
    $ids[] = 'perf_id' . ($x + ID_OFFSET);
  }
  
  $startTime = microtime(true);
  $cpsSimple->retrieveMultiple($ids);
  echo 'Total : ' . sprintf('%01.6f', microtime(true) - $startTime) . ' seconds.<br />';
  exit;*/
  
  if (DO_MYSQL) {
	  $mysqlConnection = mysql_connect('127.0.0.1', 'root', 'password');
	  mysql_select_db('cps2_perf_test');
  }
  
  if (DO_CPS) {  
	$cpsConnection->sendRequest(new CPS_Request('clear'));
	sleep(1);
  }
  
//  $cpsConnection->waitForResponse(false);
  
  if (DO_MONGO) {
    $m = new Mongo();
	$db = $m->test;
	$collection = $db->insert_test;
	MongoCursor::$timeout = 120000; // 2 minutes
  }
  
  if (defined('RANDOM_SEED')) {
	srand(RANDOM_SEED);
	echo 'Seeded with random seed ' . RANDOM_SEED . ', first random value: '  . rand() . '<br />';
  }
  
  $randomWords = array();
  $wordChars = '0123456789abcdefghijklmnopqrstuvwxyz';
  $charCount = strlen($wordChars);
  
  define('WORD_COUNT', 100000);
  
  for ($x = 0; $x < WORD_COUNT; ++$x) {
    $size = rand(3, 9);
	$word = '';
	for ($y = 0; $y < $size; ++$y) {
	  $word .= $wordChars[rand(0, $charCount - 1)];
	}
	$randomWords[] = $word;
  }
  
  function random_text($size) {
    global $randomWords;
    $res = '';
	while ($size > 0) {
	  $word = $randomWords[rand(0, WORD_COUNT - 1)];
	  if (($len = strlen($word)) < $size) {
	    $res .= ($res == '' ? '' : ' ') . $word;
		$size -= $len;
	  } else {
	    break;
	  }
	}
	return $res;
  }
  
  $insertArray = array();
  $cpsTime = 0;
  $requests = 0;
  $totalTime = 0;
  $mysqlTime = 0;
  $mongoTime = 0;
  $networkTime = 0;
  $mongoTokenizeTime = 0;
  
  set_time_limit(1800);
  
  $createdTable = false;
  if (DO_MYSQL) {
	mysql_query('DROP TABLE cps2_perf_test');
  }
  
  if (DO_MONGO) {
    $collection->drop();
	$db->createCollection('insert_test');
	$collection = $db->insert_test;
	
/*	$collection->ensureIndex(array('id' => 1), array('unique' => true, 'safe' => TRUE));
	$collection->ensureIndex(array('text' => 1), array('safe' => TRUE));
	$collection->ensureIndex(array('title' => 1), array('safe' => TRUE));
	$collection->ensureIndex(array('tags' => 1), array('safe' => TRUE));
	$collection->ensureIndex(array('tags' => 1), array('safe' => TRUE));
	$collection->ensureIndex(array('date' => 1), array('safe' => TRUE));*/

	$collection->ensureIndex(array('id' => 1), array('unique' => true, 'safe' => TRUE));
	$collection->ensureIndex(array('text_keywords' => 1), array('safe' => TRUE));
	$collection->ensureIndex(array('title_keywords' => 1), array('safe' => TRUE));
	$collection->ensureIndex(array('tags.tag1_keywords' => 1), array('safe' => TRUE));
	$collection->ensureIndex(array('tags.tag2_keywords' => 1), array('safe' => TRUE));
	$collection->ensureIndex(array('date' => 1), array('safe' => TRUE));
//	var_dump($collection->getIndexInfo());
  }
  
  $req_to_send = array();
  $req_from_file = false; 
  if (DELAYED_REQUESTS && is_file(DELAYED_REQUESTS_FILE))
  {
	$content = file_get_contents(DELAYED_REQUESTS_FILE);
	if (strlen($content))
	{
		$req_to_send = unserialize($content);
		$total_docs = count($req_to_send);
		$req_from_file = true;

	}
	unset($content);
  }
  else
  	$total_docs = TOTAL_DOCS;

  
  if (!$req_from_file || !DELAYED_REQUESTS)
  {
  for ($x = 0; $x < $total_docs ; ++$x) {
    if ($req_from_file)
    {
	$startTime = microtime(true);
        $response = $cpsConnection->sendRequest($req_to_send[$x]);
        $tt = microtime(true) - $startTime;
        $totalTime += $tt;
        $cpsTime += $response->getSeconds();
        $networkTime += $cpsConnection->getLastRequestDuration();
	++$requests;	
	continue;
    }

    $id = 'perf_id' . ($x + ID_OFFSET);
/*    $prefixes = array('pr1', 'ab2', 'dc3');
    $id = $prefixes[rand() % 3] . ($x + ID_OFFSET);
	echo htmlspecialchars('<document><id>' . $id . '</id></document>') . '<br />';*/
	$doc = array(
	  'title' => random_text(100),
	  'text' => random_text(/*200*/5000),
	  'date' => date('Y-m-d H:i:s', rand(0, pow(2, 31))),
	  'tags' => array(
	    'tag1' => random_text(200),
		'tag2' => random_text(500)
	  )
	);
//	var_dump($doc);
	if (!$createdTable && DO_MYSQL) {
		$query = 'CREATE TABLE `cps2_perf_test` (
`id` VARCHAR( 255 ) NOT NULL ,';
        $queries = array();
		foreach ($doc as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $k2 => $v2) {
				  $query .= '`' . $key . '_' . $k2 . '` TEXT NOT NULL,';
//				  $queries[] = 'ALTER TABLE `cps2_perf_test` ADD FULLTEXT (`' . $key . '_' . $k2 . '`)';
				}
			} else {
				$query .= '`' . $key . '` TEXT NOT NULL,';
//				$queries[] = 'ALTER TABLE `cps2_perf_test` ADD FULLTEXT (`' . $key . '`)';
			}
		}
		$query .= '
PRIMARY KEY ( `id` )
) ENGINE = MYISAM';
		mysql_query($query);
		foreach ($queries as $query) {
			mysql_query($query);
		}
		$createdTable = true;
	}
	$insertArray[$id] = $doc;
	
	if (($x % DOCS_PER_REQUEST == DOCS_PER_REQUEST - 1) || ($x == $total_docs - 1)) {
		// send request
		if (DO_CPS) {
			if (DELAYED_REQUESTS) {
				$req_to_send[] = new CPS_InsertRequest($insertArray);
			} else {
				$startTime = microtime(true);
				$request = new CPS_InsertRequest($insertArray);
				$request->setRequestType('single');
				$response = $cpsConnection->sendRequest($request);
				$tt = microtime(true) - $startTime;
				$totalTime += $tt;
				$cpsTime += $response->getSeconds();
				$networkTime += $cpsConnection->getLastRequestDuration();
	//			printf("%01.6f;%01.6f;%01.6f<br />\n", $tt, $response->getSeconds(), $cpsConnection->getLastRequestDuration());
			}
		}
		
		if (DO_MYSQL) {
			$startTime = microtime(true);
			$query = 'INSERT INTO cps2_perf_test VALUES ';
			$comma = false;
			foreach ($insertArray as $id => $doc) {
				$query .= ($comma ? ', ' : '') . '(' . addslashes(str_replace('perf_id', '', $id));
				$comma = true;
				foreach ($doc as $key => $value) {
					if (is_array($value)) {
						foreach ($value as $k2 => $v2) {
							$query .= ', \'' . addslashes($v2) . '\'';
						}
					} else {
						$query .= ', \'' . addslashes($value) . '\'';
					}
				}
				$query .= ')';
			}
			$query[strlen($query)-1] = ')';
	//		echo $query . ';<br />';
			$result = mysql_query($query);
			if (!$result) { die('MySQL error: ' . mysql_error()); }
			$mysqlTime += microtime(true) - $startTime;
		}
		
		if (DO_MONGO) {
			$startTime = microtime(true);
			foreach ($insertArray as $id => $doc) {
				$insertArray[$id]['id'] = $id;
				$startTokenizeTime = microtime(true);
				$insertArray[$id]['text_keywords'] = explode(' ', $insertArray[$id]['text']);
				$insertArray[$id]['title_keywords'] = explode(' ', $insertArray[$id]['title']);
				$insertArray[$id]['tags']['tag1_keywords'] = explode(' ', $insertArray[$id]['tags']['tag1']);
				$insertArray[$id]['tags']['tag2_keywords'] = explode(' ', $insertArray[$id]['tags']['tag2']);
				$mongoTokenizeTime += (microtime(true) - $startTokenizeTime);
			}
			$collection->batchInsert($insertArray, array('safe' => MONGO_SAFE));
			$mongoTime += microtime(true) - $startTime;
		}
		
		unset($insertArray);
		$insertArray = array();
		++$requests;
	}
  }
  }
  
  $realTime = microtime(true);
  $c = 0;
  if ($req_from_file && DELAYED_REQUESTS) {
	foreach($req_to_send as $request) {
		$startTime = microtime(true);
		$response = $cpsConnection->sendRequest($request);
		$tt = microtime(true) - $startTime;
		$totalTime += $tt;
		$cpsTime += $response->getSeconds();
		$networkTime += $cpsConnection->getLastRequestDuration();
//		printf("%01.6f;%d;%01.6f;%01.6f;%01.6f<br />\n", microtime(true), $c, $tt, $response->getSeconds(), $cpsConnection->getLastRequestDuration());
		++$requests;
		++$c;
	}
  }
  $realTime = microtime(true) - $realTime;
  if (!$req_from_file && DELAYED_REQUESTS)
  	file_put_contents(DELAYED_REQUESTS_FILE, serialize($req_to_send));
  
  printf("Total documents inserted: %d<br />", $total_docs);
  if (DO_CPS) {
	printf("CPS:<br />Total time: %01.3f seconds, CPS time: %01.3f seconds, network time: %01.3f seconds, average %01.6f seconds per document, average %01.3f seconds per request<br />",
		$totalTime, $cpsTime, $networkTime, $totalTime / $total_docs, $totalTime / $requests);
	if (DELAYED_REQUESTS) {
		printf("CPS Real time: %01.6f seconds<br />", $realTime);
	}
  }
  if (DO_MYSQL) {
	printf("MySQL:<br />Total time: %01.3f seconds, average %01.6f seconds per document, average %01.3f seconds per request<br />",
		$mysqlTime, $mysqlTime / $total_docs, $mysqlTime / $requests);
  }
  if (DO_MONGO) {
	printf("MongoDB:<br />Total time: %01.3f seconds, tokenization time %01.3f, average %01.6f seconds per document, average %01.3f seconds per request<br />",
		$mongoTime, $mongoTokenizeTime, $mongoTime / $total_docs, $mongoTime / $requests);
	printf("Document count in collection: " . $collection->count() . '<br />');
  }
} catch (Exception $e) {
  if (DO_MONGO) {
	printf("MongoDB:<br />Total time: %01.3f seconds, tokenization time %01.3f<br />", $mongoTime, $mongoTokenizeTime);
	printf("Document count in collection: " . $collection->count() . '<br />');
  }
var_dump($e->errors());

exit;

}