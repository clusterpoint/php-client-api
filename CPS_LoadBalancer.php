<?php
//<namespace
namespace cps;

use \SimpleXMLElement AS SimpleXMLElement;
//namespace>

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
    $this->_statusFilePrefix = '/tmp/cps-api-node-status-';
    $this->_sendWhenAllFailed = true;
    $this->_debug = false;
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
    if ($request->getCommand() == 'search') {
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
    $this->_lastSuccess = true;
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
  public function shouldRetry(&$responseString, $exception = NULL)
  {
    if (!$this->_retryRequests)
      return false;
    if ($this->_numUsed == count($this->_connectionStrings))
      return false;
    if (!is_null($exception)) {
      $this->logFailure();
      return true;
    }
    $sp = strpos($responseString, '<cps:error>');
    if ($sp === FALSE) {
      $this->logSuccess();
      return false;
    }
    $ep = strrpos($responseString, '</cps:error>');
    if ($ep === FALSE) {// shouldn't really ever happen, unless our XML response was cut off?
      $this->logFailure();
      return true;
    }
    try {
      $errorXml = new SimpleXMLElement('<root xmlns:cps="www.clusterpoint.com">' . substr($responseString, $sp, $ep - $sp + strlen('</cps:error>')) . '</root>');
    } catch (Exception $e) {
      // Unable to parse errors - shouldn't really ever happen, unless there's something wrong with this function
      return true;
    }
    foreach ($errorXml->children('www.clusterpoint.com')->error as $e) {
      $code = (int)$e->children('')->code;
      $level = (string)$e->children('')->level;

      if (($level == 'FAILED') || ($level == 'ERROR') || ($level == 'FATAL') || ($code == 2344)) {
        // request returned an error, should retry
        $this->logFailure();
        return true;
      }
    }
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