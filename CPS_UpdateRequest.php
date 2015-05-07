<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_UpdateRequest class is a wrapper for the Response class for the update command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_UpdateRequest extends CPS_ModifyRequest
{
  /**
   * Constructs an instance of the CPS_UpdateRequest class.
   * Possible parameter sets:<br />
   * 2 parameters - ($id, $document), where $id is the document ID and $document are its contents<br />
   * 1 parameter - ($array), where $array is an associative array with document IDs as keys and document contents as values<br />
   * @param string|array $arg1 Either the document id or the associative array of ids => docs
   * @param mixed|null $arg2 Either the document contents or NULL (can also be omitted)
   */
  public function __construct($arg1, $arg2 = NULL)
  {
    parent::__construct('update', $arg1, $arg2);
  }
}
