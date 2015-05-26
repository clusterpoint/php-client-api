<?php
/**
* Exception class for CPS API
* @package CPS
*/

/**
* Defines
*/
define('ERROR_CODE_INVALID_RESPONSE', 9001);
define('ERROR_CODE_INVALID_PARAMETER', 9002);
define('ERROR_CODE_INVALID_XPATHS', 9003);
define('ERROR_CODE_INVALID_CONNECTION_STRING', 9004);
define('ERROR_CODE_PROTOBUF_ERROR', 9005);
define('ERROR_CODE_SOCKET_ERROR', 9006);
define('ERROR_CODE_TIMEOUT', 9007);
define('ERROR_CODE_NO_WORKING_SERVERS', 9008);
define('ERROR_CODE_SSL_HANDSHAKE', 9009);
define('ERROR_CODE_INVALID_UTF8', 9010);

/**
* Exception class for CPS API
* @package CPS
*/
class CPS_Exception extends Exception {
	/**
	* Constructor
	*/
	public function __construct($errors, Exception $previous = null) {
		$this->_errors = $errors;
		parent::__construct($errors[0]['long_message'], $errors[0]['code'], $previous);
	}

	/**
	* toString
	*/
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
	
	/**
	* code
	*/
	public function code() {
		return $this->code;
	}

	/**
	* errors
	*/
	public function errors() {
		return $this->_errors;
	}

	private $_errors;
}
