<?php
//<namespace
namespace cps;


use \Iterator AS Iterator;
use \DOMDocument AS DOMDocument;
//namespace>

/**
 * Connection class for CPS API
 * @package CPS
 */

/**
 * Including internals
 */
require_once dirname(__FILE__) . '/internals/lib_cps2_helper.inc.php';
require_once dirname(__FILE__) . '/internals/lib_http.inc.php';

/**
 * The connection class - represents a connection to Clusterpoint Storage
 * @package CPS
 */
class CPS_Connection
{
  /**
   * Constructs an instance of the Connection class. Note that it doesn't
   * necessarily make a connection to CPS when the constructor is called.
   * @param string|object $connectionSpecification Specifies the connection string, such as tcp://127.0.0.1:5550, or a load balancing object
   * @param string $storageName The name of the storage you want to connect to
   * @param string $username Username for authenticating with the storage
   * @param string $password Password for this user
   * @param string $documentRootXpath Document root tag name. Default is "document"
   * @param string $documentIdXpath Document ID xpath. Default is "document/id"
   * @see CPS_LoadBalancer
   */
  public function __construct($connectionSpecification, $storageName, $username, $password, $documentRootXpath = 'document', $documentIdXpath = '//document/id', $customEnvelopeParams = array())
  {
    $this->_storageName = $storageName;
    $this->_customEnvelopeParams = $customEnvelopeParams;
    $this->_hmacUserKey = false;
    $this->_hmacSignKey = false;
    $this->_username = $username;
    $this->_password = $password;
    $this->_documentRootXpath = trim($documentRootXpath, '/');
    $this->_documentIdXpath = preg_split("#[/]+#", $documentIdXpath, -1, PREG_SPLIT_NO_EMPTY);
    if (is_string($connectionSpecification)) {
      $this->_connectionString = $this->_parseConnectionString($connectionSpecification);
    } else if (is_object($connectionSpecification)) {
      $this->_connectionSwitcher = $connectionSpecification;
    } else {
      throw new CPS_Exception(array(array('long_message' => 'Invalid connection specification', 'code' => ERROR_CODE_INVALID_CONNECTION_STRING, 'level' => 'REJECTED', 'source' => 'CPS_API')));
    }

    $this->_applicationId = 'CPS_PHP_API';
    $this->_connection = NULL;
    $this->_debug = false;
    $this->_resetDocIds = true;
    $this->_noCdata = false;

    $this->_lastRequestDuration = 0;
    $this->_lastRequestSize = 0;
    $this->_lastResponseSize = 0;
    $this->_lastNetworkDuration = 0;
    $this->_lastRequestQuery = '';
    $this->_lastRequestResponse = '';

    $this->_transactionId = NULL;

    $this->_sslCustomCA = false;
    $this->_sslCustomCN = false;
  }

  /**
   * Sets custom CA and Common Name for SSL certificate validation for cases when system validation fails.
   * @param string $sslCustomCA PEM file with server's CA certificate or false if CA should not be verified.
   * @param string $sslCustomCN String specifying server'a Common Name of false if Common Name should not be verified.
   */
  public function setCustomSSLValidation($sslCustomCA = false, $sslCustomCN = false)
  {
    $this->_sslCustomCA = $sslCustomCA;
    $this->_sslCustomCN = $sslCustomCN;
  }

  /**
   * Sends the request to CPS
   * @param CPS_Request &$request An object of the class Request
   */
  public function sendRequest(CPS_Request &$request)
  {
    $firstSend = true;
    $previousRenderedStorage = '';
    if (isset($this->_connectionSwitcher)) {
      $this->_connectionSwitcher->newRequest($request);
    }

    do {
      $e = NULL;

      if (isset($this->_connectionSwitcher)) {
        $this->_connectionString = $this->_parseConnectionString($this->_connectionSwitcher->getConnectionString($this->_storageName));
      }
      try {
        if (!is_null($this->_transactionId)) {
          $request->setParam('transaction_id', $this->_transactionId);
        }
        if ($firstSend || ($previousRenderedStorage != $this->_storageName)) {
          $requestXml = $this->_renderRequest($request);
          $previousRenderedStorage = $this->_storageName;

          $this->_lastRequestQuery = $requestXml;
          if ($this->_debug) {
            echo $this->getLastRequestQuery();
          }
          $this->_lastRequestSize = strlen($requestXml);
        }
        $firstSend = false;

        $this->_lastNetworkDuration = 0;

        $time_start = microtime(true);
        if ($this->_connectionString['type'] == 'socket') {
          $ssl_options = false;
          if (isset($this->_connectionString['ssl']) && $this->_connectionString['ssl'] == true) {
            $ssl_options["enabled"] = true;
            $ssl_options["ca"] = $this->_sslCustomCA;
            $ssl_options["cn"] = $this->_sslCustomCN;
          }
          $rawResponse = cps2_exchange($this->_connectionString['host'], $this->_connectionString['port'], $requestXml, $this->_storageName, $this->_lastNetworkDuration, $this->_hmacUserKey, $this->_hmacSignKey, $ssl_options);
        } else {
          // TODO: use curl?
          $rawResponse = cps_http_data(cps_http_post($this->_connectionString['url'], $requestXml, false, 'Recipient: ' . str_replace(array("\r", "\n"), '', $this->_storageName) . "\r\n", $this->_lastNetworkDuration, $this->_hmacUserKey, $this->_hmacSignKey));
        }
        $time_end = microtime(true);
        $this->_lastRequestDuration = $time_end - $time_start;
        $this->_lastResponseSize = strlen($rawResponse);

        $this->_lastRequestResponse = $rawResponse;
        if ($this->_debug) {
          echo $this->getLastRequestResponse();
        }
      } catch (CPS_Exception $exception) {
        $e = $exception;
      }
      if (isset($this->_connectionSwitcher)) {
        $quit = !$this->_connectionSwitcher->shouldRetry($rawResponse, $e);
      } else {
        $quit = true;
      }
      if ($quit && !is_null($e))
        throw $e;
    } while (!$quit);
    switch ($request->getCommand()) {
      case 'search':
        $ret = new CPS_SearchResponse($this, $request, $rawResponse, $this->_noCdata);
        break;
      case 'update':
      case 'delete':
      case 'replace':
      case 'partial-replace':
      case 'partial-xreplace':
      case 'insert':
      case 'create-alert':
      case 'update-alerts':
      case 'delete-alerts':
        $ret = new CPS_ModifyResponse($this, $request, $rawResponse, $this->_noCdata);
        break;
      case 'alternatives':
        $ret = new CPS_AlternativesResponse($this, $request, $rawResponse, $this->_noCdata);
        break;
      case 'list-words':
        $ret = new CPS_ListWordsResponse($this, $request, $rawResponse, $this->_noCdata);
        break;
      case 'status':
        $ret = new CPS_StatusResponse($this, $request, $rawResponse, $this->_noCdata);
        break;
      case 'retrieve':
      case 'list-last':
      case 'list-first':
      case 'retrieve-last':
      case 'retrieve-first':
      case 'lookup':
      case 'similar':
      case 'show-history':
        $ret = new CPS_LookupResponse($this, $request, $rawResponse, $this->_noCdata);
        break;
      case 'search-delete':
        $ret = new CPS_SearchDeleteResponse($this, $request, $rawResponse, $this->_noCdata);
        break;
      case 'list-paths':
        $ret = new CPS_ListPathsResponse($this, $request, $rawResponse, $this->_noCdata);
        break;
      case 'list-facets':
        $ret = new CPS_ListFacetsResponse($this, $request, $rawResponse, $this->_noCdata);
        break;
      case 'list-alerts':
        $ret = new CPS_ListAlertsResponse($this, $request, $rawResponse, $this->_noCdata);
        break;
      default:
        $ret = new CPS_Response($this, $request, $rawResponse, $this->_noCdata);
    }
    if (isset($this->_connectionSwitcher) && $this->_transactionId) {
      $this->_connectionSwitcher->logSuccess();
    }
    return $ret;
  }

  /**
   * Sets the application ID for the request
   *
   * This method can be used to set a custom application ID for your requests.
   * This can be useful to identify requests sent by a particular application
   * in a log file
   *
   * @param string &$applicationId
   */
  public function setApplicationId(&$applicationId)
  {
    $this->_applicationId = $applicationId;
  }

  /**
   * Toggles the CDATA integration flag
   *
   * Set to true, if you want CDATA values to be converted to text
   *
   * @param bool $v
   */
  public function setNoCdata($v)
  {
    $this->_noCdata = $v;
  }

  /**
   * Sets the debugging mode
   * @param int $debugMode
   */
  public function setDebug($debugMode)
  {
    $this->_debug = $debugMode;
    if (isset($this->_connectionSwitcher)) {
      $this->_connectionSwitcher->setDebug($debugMode);
    }
  }

  /**
   * Enables or disables resetting document IDs when modifying. On by default.
   * You should change this setting if You plan to insert documents with auto-incremented IDs
   * or with IDs already integrated into the document
   * @param bool $resetIds
   */
  public function setDocIdResetting($resetIds)
  {
    $this->_resetDocIds = $resetIds;
  }

  /**
   * Sets HMAC keys used for this connection, this overrides username/password passed in constructor.
   * @param string $userKey User's key (or sometimes called public key), used for identifying user. This is transmitted over network.
   * @param string $signKey Signature key (or sometimes called private key), used for signing requests. This is NOT transmitted over network.
   */

  public function setHMACKeys($userKey, $signKey)
  {
    $this->_hmacUserKey = $userKey;
    $this->_hmacSignKey = $signKey;
    $this->_username = "";
    $this->_password = "";
  }

  /**#@+
   * @access private
   */

  /**
   * Renders the request as XML
   *
   * @param Request &$request
   * @return string The full XML request
   */

  private function _renderRequest(CPS_Request &$request)
  {
    $envelopeFields = array(
      'storage' => $this->_storageName,
      'user' => $this->_username,
      'password' => $this->_password,
      'command' => $request->getCommand(),
    );
    if (count($this->_customEnvelopeParams) > 0) {
      foreach ($this->_customEnvelopeParams as $key => $val) {
        $envelopeFields[$key] = $val;
      }
    }
    if (strlen($request->getRequestId()) > 0)
      $envelopeFields['requestid'] = $request->getRequestId();
    if (strlen($this->_applicationId) > 0)
      $envelopeFields['application'] = $this->_applicationId;
    if (!is_null($reqType = $request->getRequestType()))
      $envelopeFields['type'] = $reqType;
    if (strlen($reqLabel = $request->getClusterLabel()))
      $envelopeFields['label'] = $reqLabel;
    return $request->getRequestXml($this->_documentRootXpath, $this->_documentIdXpath, $envelopeFields, $this->_resetDocIds);
  }

  /**
   * returns the document root xpath
   */

  function getDocumentRootXpath()
  {
    return $this->_documentRootXpath;
  }

  /**
   * returns the document ID xpath
   * @return array
   */
  function getDocumentIdXpath()
  {
    return $this->_documentIdXpath;
  }

  /**
   * returns the duration (in seconds) of the last request, as measured on the client side
   * @return float
   */
  function getLastRequestDuration()
  {
    return $this->_lastRequestDuration;
  }

  /**
   * returns the duration (in seconds) of the last request from the moment the first packet is sent to the network until the moment the last packet is received back
   * @return float
   */
  function getLastNetworkDuration()
  {
    return $this->_lastNetworkDuration;
  }

  /**
   * returns the length of last sent request's XML in bytes
   * @return int
   */
  function getLastRequestSize()
  {
    return $this->_lastRequestSize;
  }

  /**
   * returns the length of last response's XML in bytes
   * @return int
   */
  function getLastResponseSize()
  {
    return $this->_lastResponseSize;
  }

  /**
   * returns the info on where the last connection was made
   * @return array
   */
  function getLastConnectionInfo()
  {
    return $this->_connectionString;
  }

  /**
   * get transaction context from connection object
   */
  function getTransactionId()
  {
    return $this->_transactionId;
  }

  /**
   * set transaction context to connection object
   */
  function setTransactionId($taransID)
  {
    $this->_transactionId = $taransID;
  }

  /**
   * clears transaction context from connection object
   */
  function clearTransactionId()
  {
    $this->_transactionId = NULL;
  }

  /**
   * returns raw query XML from the last request
   * @return string
   */
  function getLastRequestQuery($stripEnvelope = false)
  {
    $html = $this->_lastRequestQuery;

    if (php_sapi_name() == 'cli') {
      // remove user/password
      if (!$this->_debug) {
        $html = preg_replace(array("<\<cps:user\>(.*)\<\/cps:user\>>", "<\<cps:password\>(.*)\<\/cps:password\>>"),
          array('<cps:user>CPS_USER</cps:user>', '<cps:password>CPS_PASSWORD</cps:password>'), $this->_lastRequestQuery);
      }

      // remove everything except cps:content
      if ($stripEnvelope) {
        preg_match("<\<cps:content\>([\s\S.]*)\<\/cps:content\>>",
          $html, $matches);
        $html = @$matches[1];
      }

      return "Sent:\n" . $html . '\n';
    } else {
      // try to pretty format
      try {
        $doc = new DOMDocument();
        $doc->strictErrorChecking = false;
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($this->_lastRequestQuery);
        $html = $doc->saveXML();
      } catch (Exception $e) {
        // do nothing
      }

      // remove user/password
      if (!$this->_debug) {
        $html = preg_replace(array("<\<cps:user\>(.*)\<\/cps:user\>>", "<\<cps:password\>(.*)\<\/cps:password\>>"),
          array('<cps:user>CPS_USER</cps:user>', '<cps:password>CPS_PASSWORD</cps:password>'), $html);
      }

      // remove everything except cps:content
      if ($stripEnvelope) {
        preg_match("<\<cps:content\>([\s\S.]*)\<\/cps:content\>>",
          $html, $matches);
        $html = @$matches[1];
      }

      return 'Sent: <br /><pre>' . htmlspecialchars($html) . '</pre>';
    }
  }

  /**
   * returns raw response XML from the last request
   * @return string
   */
  function getLastRequestResponse()
  {
    if (php_sapi_name() == 'cli') {
      return "Received:\n" . $this->_lastRequestResponse . '\n';
    } else {
      // It's already pretty formatted.
      return 'Received: <br /><pre>' . htmlspecialchars($this->_lastRequestResponse) . '</pre>';
    }
  }

  /**
   * Sets extra envelope params (these params are namespaced with cps:)
   * @param string $key valid xml key
   * @param string $value valid xml value. If passed value is empty (returns true to empty() call) then exisiting value will be removed.
   */
  function setExtraEnvelopeParam($key, $value)
  {
    if (empty($value)) unset($this->_customEnvelopeParams[$key]);
    else $this->_customEnvelopeParams[$key] = $value;
  }

  /**
   * returns an array with parsed connection string data
   */
  private function _parseConnectionString($string)
  {
    $res = array();
    if (($string == '') || ($string == 'unix://')) {
      // default connection
      $res['type'] = 'socket';
      $res['host'] = 'unix:///usr/local/cps2/storages/' . str_replace('/', '_', $this->_storageName) . '/storage.sock';
      $res['port'] = 0;
    } else if (strncmp($string, 'http://', 7) == 0) {
      // HTTP connection
      $res['type'] = 'http';
      $res['url'] = $string;
    } else if (strncmp($string, 'unix://', 7) == 0) {
      // Unix socket
      $res['type'] = 'socket';
      $res['host'] = $string;
      $res['port'] = 0;
    } else if (strncmp($string, 'tcp://', 6) == 0 || strncmp($string, 'tcps://', 7) == 0) {
      // TCP socket
      $res['type'] = 'socket';
      $uc = parse_url($string);
      if (!isset($uc['host'])) {
        throw new CPS_Exception(array(array('long_message' => 'Invalid connection string', 'code' => ERROR_CODE_INVALID_CONNECTION_STRING, 'level' => 'REJECTED', 'source' => 'CPS_API')));
      }
      $res['host'] = $uc['host'];
      if (isset($uc['port'])) {
        $res['port'] = $uc['port'];
      } else {
        $res['port'] = 5550;
      }
      if (strncmp($string, 'tcps://', 7) == 0)
        $res['ssl'] = true;
    } else {
      throw new CPS_Exception(array(array('long_message' => 'Invalid connection string', 'code' => ERROR_CODE_INVALID_CONNECTION_STRING, 'level' => 'REJECTED', 'source' => 'CPS_API')));
    }
    return $res;
  }

  private $_connectionString;
  private $_connectionSwitcher;
  private $_storageName;
  private $_username;
  private $_password;
  private $_documentRootXpath;
  private $_documentIdXpath;
  private $_customEnvelopeParams;
  private $_applicationId;
  private $_connection;
  private $_debug;
  private $_resetDocIds;
  private $_lastRequestDuration;
  private $_lastRequestSize;
  private $_lastResponseSize;
  private $_lastNetworkDuration;
  private $_lastRequestQuery;
  private $_lastRequestResponse;
  private $_noCdata;
  private $_hmacUserKey;
  private $_hmacSignKey;
  private $_transactionId;

  private $_sslCustomCA;
  private $_sslCustomCN;

  /**#@-*/
}

//-------------
//
// Response classes
//
//------------


/**
 * The CPS_SearchResponse class is a wrapper for the Response class
 * @package CPS
 * @see CPS_SearchRequest
 */
class CPS_SearchResponse extends CPS_Response
{
  /**
   * Returns the documents from the response as an associative array, where keys are document IDs and values area document contents
   * @param int $type defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
   * @return array
   */
  public function getDocuments($type = DOC_TYPE_SIMPLEXML)
  {
    if (isset($this->_simpleXml->cursor_id)) return $this->getCursor($type);
    return parent::getRawDocuments($type);
  }

  /**
   * Returns the facets from the response in a form of a multi-dimensional associative array, e.g. array('category' => array('Sports' => 15, 'News' => 20));
   * @return array
   */
  public function getFacets()
  {
    return parent::getRawFacets();
  }

  /**
   * Returns aggregated data from the response as an associative array with queries as keys and results as values
   * @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_ARRAY, other possible values are DOC_TYPE_SIMPLEXML and DOC_TYPE_STDCLASS
   * @return array
   */
  public function getAggregate($returnType = DOC_TYPE_SIMPLEXML)
  {
    return parent::getRawAggregate($returnType);
  }

  /**
   * Returns the number of documents returned
   * @return int
   */
  public function getFound()
  {
    return $this->getParam('found');
  }

  /**
   * Returns the total number of hits - i.e. the number of documents in a storage that match the request
   * @return int
   */
  public function getHits()
  {
    return $this->getParam('hits');
  }

  /**
   * Returns the position of the first document that was returned
   * @return int
   * @see CPS_SearchRequest::setOffset(), CPS_SearchRequest::setDocs()
   */
  public function getFrom()
  {
    return $this->getParam('from');
  }

  /**
   * Returns the position of the last document that was returned
   * @see CPS_SearchRequest::setOffset(), CPS_SearchRequest::setDocs()
   * @return int
   */
  public function getTo()
  {
    return $this->getParam('to');
  }

  public function getCursor($type = DOC_TYPE_SIMPLEXML)
  {
    return new CPS_Cursor($this->_connection, $this->getParam('cursor_id'), $this->getParam('cursor_data'), $this->_documents, $type);
  }
}

class CPS_Cursor implements Iterator
{
  public function __construct($connection, $cursor_id, $cursor_data, $data, $type = DOC_TYPE_SIMPLEXML, $batch_size = 10)
  {
    $this->_connection = $connection;
    $this->_cursor_id = $cursor_id;
    $this->_cursor_data = $cursor_data;
    $this->_data = $data;
    $this->_type = $type;
    $this->_batch_size = $batch_size;
  }

  public function current()
  {
    if ($this->type == DOC_TYPE_ARRAY) {
      return CPS_Response::simpleXmlToArray(current($this->_data));
    } else if ($this->type == DOC_TYPE_STDCLASS) {
      return CPS_Response::simpleXmlToStdClass(current($this->_data));
    } else {
      return current($this->_data);
    }
  }

  public function key()
  {
    return key($this->_data);
  }

  public function next()
  {
    if (next($this->_data) === FALSE) {
      // Try to request new data
      $req = new CPS_Request('cursor-next-batch');
      $req->setParam('cursor_id', $this->_cursor_id);
      $req->setParam('cursor_data', $this->_cursor_data);
      $req->setParam('docs', $this->_batch_size);
      $resp = $this->_connection->sendRequest($req);

      $this->_cursor_id = $resp->getParam('cursor_id');
      $this->_cursor_data = $resp->getParam('cursor_data');
      $this->_data = $resp->getRawDocuments(DOC_TYPE_SIMPLEXML);
    }
  }

  public function rewind()
  {
    //TODO: Rewind iterator completely??
    reset($this->_data);
  }

  public function valid()
  {
    return current($this->_data) !== FALSE;
  }

  private $_connection;
  private $_cursor_id;
  private $_cursor_data;
  private $_data;
}

/**#@+
 * @access private
 */

/**
 * The CPS_ModifyResponse class is a wrapper for the Response class for insert, update, delete, replace and partial-replace commands
 * @package CPS
 * @see CPS_InsertRequest, CPS_UpdateRequest, CPS_DeleteRequest, CPS_ReplaceRequest, CPS_PartialReplaceRequest
 */
class CPS_ModifyResponse extends CPS_Response
{
  /**
   * Returns an array of IDs of documents that have been successfully modified
   * @return array
   */
  public function getModifiedIds()
  {
    return array_keys(parent::getRawDocuments(NULL));
  }
}

/**
 * The CPS_AlternativesResponse class is a wrapper for the Response class for the alternatives command
 * @package CPS
 * @see CPS_AlternativesRequest
 */
class CPS_AlternativesResponse extends CPS_Response
{
  /**
   * Gets the spelling alternatives to the specified query terms
   *
   * Returns an associative array, where keys are query terms and values are associative arrays
   * with alternative spellings as keys and arrays of the metrics of these spellings as values
   * @return array
   */
  public function getWords()
  {
    return parent::getRawWords();
  }

  /**
   * Gets the occurence count of a particular word from the original query
   *
   * @param string $word
   * @return int
   */
  public function getWordCount($word)
  {
    if (is_null($this->_localWords)) {
      $this->_localWords = $this->getRawWordCounts();
    }
    return isset($this->_localWords[$word]) ? $this->_localWords[$word] : 0;
  }

  private $_localWords;
}

/**
 * The CPS_ListWordsResponse class is a wrapper for the Response class for the list-words command
 * @package CPS
 * @see CPS_ListWordsRequest
 */
class CPS_ListWordsResponse extends CPS_Response
{
  /**
   * Returns words matching the given wildcard
   *
   * Returns an associative array, where keys are given wildcards and values are associative arrays with matching words as keys and
   * their counts as values
   * @return array
   */
  public function getWords()
  {
    return parent::getRawWords();
  }
}

/**
 * The CPS_StatusResponse class is a wrapper for the Response class for the status command
 * @package CPS
 * @see CPS_StatusRequest
 */
class CPS_StatusResponse extends CPS_Response
{
  /**
   * Returns an associative array, which contains the status information
   * @param int $type defines which datatype the returned documents will be in. Default is DOC_TYPE_ARRAY, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
   * @return array
   */
  public function getStatus($type = DOC_TYPE_ARRAY)
  {
    return parent::getContentArray($type);
  }
}

/**
 * The CPS_LookupResponse class is a wrapper for the Response class for replies to retrieve, list-last, list-first, retrieve-last, and retrieve-first commands
 * @package CPS
 * @see CPS_RetrieveRequest, CPS_ListLastRequest, CPS_ListFirstRequest, CPS_RetrieveLastRequest, CPS_RetrieveFirstRequest
 */
class CPS_LookupResponse extends CPS_Response
{
  /**
   * Returns the documents from the response as an associative array, where keys are document IDs and values area document contents
   * @param int $type defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
   * @return array
   */
  public function getDocuments($type = DOC_TYPE_SIMPLEXML)
  {
    return parent::getRawDocuments($type);
  }

  /**
   * Returns the number of documents returned
   * @return int
   */
  public function getFound()
  {
    return $this->getParam('found');
  }

  /**
   * Returns the position of the first document that was returned
   * @return int
   * @see CPS_SearchRequest::setOffset(), CPS_SearchRequest::setDocs()
   */
  public function getFrom()
  {
    return $this->getParam('from');
  }

  /**
   * Returns the position of the last document that was returned
   * @see CPS_SearchRequest::setOffset(), CPS_SearchRequest::setDocs()
   * @return int
   */
  public function getTo()
  {
    return $this->getParam('to');
  }
}

/**
 * The CPS_SearchDeleteResponse class is a wrapper for the Response class
 * @package CPS
 * @see CPS_SearchDeleteRequest
 */
class CPS_SearchDeleteResponse extends CPS_Response
{
  /**
   * Returns the total number of hits - i.e. the number of documents erased
   * @return int
   */
  public function getHits()
  {
    return $this->getParam('hits');
  }
}

/**
 * The CPS_ListPathsResponse class is a wrapper for the Response class for the list-paths command
 * @package CPS
 * @see CPS_ListPathsRequest
 */
class CPS_ListPathsResponse extends CPS_Response
{
  /**
   * Returns an array of paths
   * @return array
   */
  public function getPaths()
  {
    $content = parent::getContentArray();
    if (isset($content['paths']['path'])) {
      return $content['paths']['path'];
    } else {
      return array();
    }
  }
}

/**
 * The CPS_ListFacetsResponse class is a wrapper for the Response class for the list-facets command
 * @package CPS
 * @see CPS_ListFacetsRequest
 */
class CPS_ListFacetsResponse extends CPS_Response
{

  /**
   * Returns the facets from the response in a form of a multi-dimensional associative array, e.g. array('category' => array('Sports' => '', 'News' => ''));
   * @return array
   */
  public function getFacets()
  {
    return parent::getRawFacets();
  }
}

/**
 * The CPS_ListAlertsResponse class is a wrapper for the Response class for the list-alerts command
 * @package CPS
 * @see CPS_ListAlertsRequest
 */
class CPS_ListAlertsResponse extends CPS_Response
{
  /**
   * Returns the alerts from the response in a form of a multi-dimensional associative array, in format:
   * array('alert-id' => array('id' => ..,
   *              'query' => ..,
   *              'action' = array('type' => .., ..)));
   * @return array
   */
  public function getAlerts()
  {
    if (count($this->_alerts) > 0) return $this->_alerts;
    if (!isset($this->_contentArray['alerts']) || !isset($this->_contentArray['alerts']['alert'])) return array();
    if (isset($this->_contentArray['alerts']['alert']['id'])) {
      $this->_alerts[$this->_contentArray['alerts']['alert']['id']] = $this->_contentArray['alerts']['alert'];
    } else {
      foreach ($this->_contentArray['alerts']['alert'] as $alert) {
        $this->_alerts[$alert['id']] = $alert;
      }
    }
    return $this->_alerts;
  }

  private $_alerts = array();
}

/**
 * CPS_EmptyResponse is used in cases where no reply from the server is expected
 * @package CPS
 */
class CPS_EmptyResponse extends CPS_Response
{
  /**
   * Empty response - used in cases where no reply from the server is expected
   * @param CPS_Connection &$connection CPS connection object
   * @param CPS_Request &$request The original request that the response is to
   */
  public function __construct(CPS_Connection &$connection, CPS_Request &$request)
  {
    $this->_errors = array();
    $errors = array();
    $this->_documents = array();
    $this->_facets = array();
    $this->_textParams = array();
    $this->_words = array();
    $this->_contentArray = array();
    $this->_paths = array();
    $this->_textParams = array();
  }
}