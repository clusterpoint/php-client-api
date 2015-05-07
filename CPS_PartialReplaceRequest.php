<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_PartialReplaceRequest class is a wrapper for the Response class for the partial-replace command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_PartialReplaceRequest extends CPS_ModifyRequest
{
  /**
   * Constructs an instance of the CPS_PartialReplaceRequest class.
   * Possible parameter sets:<br />
   * 2 parameters - ($id, $document), where $id is the document ID and $document are its partial contents with only those fields set that You want changed.<br />
   * 1 parameter - ($array), where $array is an associative array with document IDs as keys and replaceable document contents as values<br />
   * @param string|array $arg1 Either the document id or the associative array of ids => docs
   * @param mixed|null $arg2 Either the replaceable document contents or NULL (can also be omitted)
   */
  public function __construct($arg1, $arg2 = NULL)
  {
    parent::__construct('partial-replace', $arg1, $arg2);
  }
}