<?php
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
  public function sendRequest(CPS_Request &$request, $json = false)
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
        if (strtolower($request->getCommand()) == 'begin-transaction')
          $this->setTransactionId(NULL);//reset transaction id to allow failover to another hub in case of failure
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
          if ($json) { // TODO: Jauzlabo JSON integracija
            return $rawResponse;
          }
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
        $quit = !$this->_connectionSwitcher->shouldRetry($rawResponse, $e, $this->getTransactionId());
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

/**
 * The load balancer class - connects to different nodes randomly unless one of the nodes is down
 * @package CPS
 */
class CPS_LoadBalancer
{
  /**
   * Constructs an instance of the Load Balancer class.
   * @param array $connectionStrings an array of connection strings
   * @param array $storageNames an array of storage names corresponding to each of the connection stringss
   */
  public function __construct($connectionStrings, $storageNames = array())
  {
    $this->_connectionStrings = $connectionStrings;
    $this->_storageNames = $storageNames;
    $this->_usedConnections = array();
    $this->_numUsed = 0;
    $this->_lastReturnedIndex = -1;
    $this->_lastSuccess = false;
    $this->_exclusionTime = 30;
    $tmp =  sys_get_temp_dir();
    if (substr($tmp, strlen($tmp) - 1) !== DIRECTORY_SEPARATOR) {
      $tmp .= DIRECTORY_SEPARATOR;
    };
    $this->_statusFilePrefix = $tmp . "cps-api-node-status-";
    $this->_sendWhenAllFailed = true;
    $this->_debug = false;
    $this->_non_retry_cmds = array('update', 'delete', 'replace', 'partial-replace', 'partial-xreplace', 'insert', 'create-alert', 'update-alerts', 'delete-alerts',
      'search-delete', 'commit-transaction', 'rollback-transaction');
  }

  /**
   * Sets the debugging mode
   * @param int $debugMode
   */
  public function setDebug($debugMode)
  {
    $this->_debug = $debugMode;
  }

  /**
   * This is called by CPS_Connection whenever there is a new request to be sent. This method should not be used directly.
   * @ignore
   * @param CPS_Request &$request the request before being rendered
   */
  public function newRequest(CPS_Request &$request)
  {
    if (!array_search(strtolower($request->getCommand()), $this->_non_retry_cmds)) {
      $this->_retryRequests = true;
    } else {
      $this->_retryRequests = false;
    }
    if ($this->_numUsed == count($this->_connectionStrings)) {
      // reset state if there's nowhere to send to
      $this->_usedConnections = array();
      $this->_numUsed = 0;
      $this->_lastReturnedIndex = -1;
      $this->_lastSuccess = false;
    }
  }

  /**
   * This function is called by CPS_Connection before sending the request to find out where to send it to. This method should not be used directly.
   * @ignore
   * @param string &$storageName if request on different connections should be sent to different storage names,
   * this parameter should be set before returning
   */
  public function getConnectionString(&$storageName)
  {
    // if all servers have been tried, no other options are left
    if ($this->_numUsed == count($this->_connectionStrings)) {
      return NULL;
    }
    if ($this->_lastSuccess) {
      $this->_lastSuccess = false;
      return $this->_connectionStrings[$this->_lastReturnedIndex];
    }
    if ($this->_numUsed == 0) {
      $noFreeConn = true;
      for ($i = 0; $i < count($this->_connectionStrings); $i++) {
        $hash = md5($this->_connectionStrings[$i] . (isset($this->_storageNames[$i]) ? '|' . $this->_storageNames[$i] : ''));
        if (!file_exists($this->_statusFilePrefix . $hash)) {
          $noFreeConn = false;
          break;
        }
      }
      if ($noFreeConn) {
        for ($i = 0; $i < count($this->_connectionStrings); $i++) {
          $hash = md5($this->_connectionStrings[$i] . (isset($this->_storageNames[$i]) ? '|' . $this->_storageNames[$i] : ''));
          @unlink($this->_statusFilePrefix . $hash);
        }
      }
    }

    // otherwise, select a connection string randomly
    do {
      $i = rand(0, count($this->_connectionStrings) - 1);
      if (isset($this->_usedConnections[$i]))
        continue;
      $hash = md5($this->_connectionStrings[$i] . (isset($this->_storageNames[$i]) ? '|' . $this->_storageNames[$i] : ''));
      if (file_exists($this->_statusFilePrefix . $hash)) {
        // failed record present, check it
        $stat = stat($this->_statusFilePrefix . $hash);
        if (time(NULL) - $stat['mtime'] > $this->_exclusionTime) {
          // if record is too old, it will be deleted
          unlink($this->_statusFilePrefix . $hash);
        } else {
          // if this isn't the last connection on the list, mark it as used
          if (count($this->_connectionStrings) - $this->_numUsed > 1 || (!$this->_sendWhenAllFailed)) {
            if ($this->_debug) {
              printf("[LoadBalancer] Skipping %s because it's marked as failed on %s<br />\n", $this->_connectionStrings[$i] . (isset($this->_storageNames[$i]) ? '|' . $this->_storageNames[$i] : ''), date('d-m-Y H:i:s', $stat['mtime']));
            }

            $this->_usedConnections[$i] = true;
            ++$this->_numUsed;
            if ((count($this->_connectionStrings) == $this->_numUsed) && !$this->_sendWhenAllFailed) {
              if ($this->_debug) {
                printf("[LoadBalancer] All servers are marked as failed<br />\n");
              }
              throw new CPS_Exception(array(array('long_message' => 'No servers to send to', 'code' => ERROR_CODE_NO_WORKING_SERVERS, 'level' => 'REJECTED', 'source' => 'CPS_API')));
            }
          }
        }
      }
    } while (isset($this->_usedConnections[$i]));
    $this->_usedConnections[$i] = true;
    ++$this->_numUsed;
    if (isset($this->_storageNames[$i]))
      $storageName = $this->_storageNames[$i];
    $this->_lastReturnedIndex = $i;
//    $this->_lastSuccess = true;
    if ($this->_debug) {
      printf("[LoadBalancer] Connecting to %s<br />\n", $this->_connectionStrings[$i] . (isset($this->_storageNames[$i]) ? '|' . $this->_storageNames[$i] : ''));
    }
    return $this->_connectionStrings[$i];
  }

  /**
   * This function is called whenever there was a sending failure - an error received or an exception raised
   */

  private function logFailure()
  {
    if ($this->_debug) {
      printf("[LoadBalancer] Failed for %s<br />\n", $this->_connectionStrings[$this->_lastReturnedIndex] . (isset($this->_storageNames[$this->_lastReturnedIndex]) ? '|' . $this->_storageNames[$this->_lastReturnedIndex] : ''));
    }
    $hash = md5($this->_connectionStrings[$this->_lastReturnedIndex] . (isset($this->_storageNames[$this->_lastReturnedIndex]) ? '|' . $this->_storageNames[$this->_lastReturnedIndex] : ''));
    touch($this->_statusFilePrefix . $hash);
  }

  /**
   * This function is called whenever there was a sending success - no error received and no exception raised
   */
  public function logSuccess()
  {
    $this->_lastSuccess = true;
    if ($this->_debug) {
      printf("[LoadBalancer] Success for %s<br />\n", $this->_connectionStrings[$this->_lastReturnedIndex] . (isset($this->_storageNames[$this->_lastReturnedIndex]) ? '|' . $this->_storageNames[$this->_lastReturnedIndex] : ''));
    }
    $hash = md5($this->_connectionStrings[$this->_lastReturnedIndex] . (isset($this->_storageNames[$this->_lastReturnedIndex]) ? '|' . $this->_storageNames[$this->_lastReturnedIndex] : ''));
    if (file_exists($this->_statusFilePrefix . $hash)) {
      unlink($this->_statusFilePrefix . $hash);
    }
  }

  /**
   * This function should return whether a request should be retried depending on response
   * @ignore
   * @param string &$responseString raw response string
   */
  public function shouldRetry(&$responseString, $exception, $transaction_id)
  {
    // Is transaction - no retry
    if ($transaction_id)
      return false;
    if (!$this->_retryRequests)
      return false;
    if (!is_null($exception)) {
      $this->logFailure();
      if ($this->_numUsed == count($this->_connectionStrings)) {
        return false;
      }
	  $errors = $exception->errors();
      if ($exception->code() == 9006 && $errors[0]['source'] == "CPS_API")
        return true;
      return false;
    }
//     $sp = strpos($responseString, '<cps:error>');
//     if ($sp === FALSE) {
//       $this->logSuccess();
//       return false;
//     }
//     $ep = strrpos($responseString, '</cps:error>');
//     if ($ep === FALSE) {// shouldn't really ever happen, unless our XML response was cut off?
//       $this->logFailure();
//       return ($this->_retryRequests && !$transaction_id);
//     }
//     try {
//       $errorXml = new SimpleXMLElement('<root xmlns:cps="www.clusterpoint.com">' . substr($responseString, $sp, $ep - $sp + strlen('</cps:error>')) . '</root>');
//     } catch (Exception $e) {
//       // Unable to parse errors - shouldn't really ever happen, unless there's something wrong with this function
//       return ($this->_retryRequests && !$transaction_id);
//     }
//     foreach ($errorXml->children('www.clusterpoint.com')->error as $e) {
//       $code = (int)$e->children('')->code;
//       $level = (string)$e->children('')->level;

//       if (($level == 'FAILED') || ($level == 'ERROR') || ($level == 'FATAL') || ($code == 2344)) {
//         // request returned an error, should retry
//         $this->logFailure();
//         return ($this->_retryRequests && !$transaction_id);
//       }
//     }
    $this->logSuccess();
    return false;
  }

  /**
   * This function defines how long should a node should be excluded from the list of valid nodes after a failure. Default is 30 seconds
   * @param int $seconds number of seconds that the node will be excluded for
   */
  public function setExclusionTime($seconds)
  {
    $this->_exclusionTime = $seconds;
  }

  /**
   * This function lets you set the prefix for status files. Default prefix is "/tmp/cps-api-node-status-"
   * @param string $prefix new prefix
   */
  public function setStatusFilePrefix($prefix)
  {
    $this->_statusFilePrefix = $prefix;
  }

  /**
   * This function defines whether the request should be sent to one of the nodes anyway, when all the nodes in the list have failed
   * @param bool $value true or false
   */
  public function setSendWhenAllFailed($value)
  {
    $this->_sendWhenAllFailed = $value;
  }

  private $_connectionStrings;
  private $_storageNames;
  private $_debug;
  private $_loadBalance;
  private $_usedConnections;
  private $_numUsed;
  private $_lastReturnedIndex;
  private $_lastSuccess;
  private $_exclusionTime;
  private $_statusFilePrefix;
  private $_sendWhenAllFailed;
}
