<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_ModifyRequest class is a wrapper for the Request class
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_ModifyRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_ModifyRequest class.
   * Possible parameter sets:<br />
   * 3 parameters - ($command, $id, $document), where $id is the document ID and $document is its contents<br />
   * 2 parameters - ($command, $array), where $array is an associative array with document IDs as keys and document contents as values<br />
   * @param string $command name of the command
   * @param string|array $arg1 Either the document id or the associative array of ids => docs
   * @param mixed|null $arg2 Either the document contents or NULL
   */
  public function __construct($command, $arg1, $arg2)
  {
    parent::__construct($command);
    if (is_array($arg1)) {
      if (!is_null($arg2)) {
        throw new CPS_Exception(array(array('long_message' => 'Invalid request parameter', 'code' => ERROR_CODE_INVALID_PARAMETER, 'level' => 'REJECTED', 'source' => 'CPS_API')));
      }
      // single argument - an associative array
      $this->_documents = $arg1;
    } elseif (!is_null($arg2)) {
      // two arguments - first is the id, second is the document content
      $this->_documents = array($arg1 => $arg2);
    } else {
      throw new CPS_Exception(array(array('long_message' => 'Invalid request parameter', 'code' => ERROR_CODE_INVALID_PARAMETER, 'level' => 'REJECTED', 'source' => 'CPS_API')));
    }
  }
}