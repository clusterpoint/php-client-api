<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_InsertRequest class is a wrapper for the Response class for the insert command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_InsertRequest extends CPS_ModifyRequest
{
  /**
   * Constructs an instance of the CPS_InsertRequest class.
   * Possible parameter sets:<br />
   * 2 parameters - ($id, $document), where $id is the document ID and $document are its contents<br />
   * 1 parameter - ($array), where $array is an associative array with document IDs as keys and document contents as values<br />
   * @param string|array $arg1 Either the document id or the associative array of ids => docs
   * @param mixed|null $arg2 Either the document contents or NULL (can also be omitted)
   */
  public function __construct($arg1, $arg2 = NULL)
  {
    parent::__construct('insert', $arg1, $arg2);
  }
}
