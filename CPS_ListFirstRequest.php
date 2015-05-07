<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_ListFirstRequest class is a wrapper for the Request class for list-first requests
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_ListFirstRequest extends CPS_ListLastRetrieveFirstRequest
{
  /**
   * Constructs an instance of the CPS_ListFirst class.
   * @param array $list an associative array with tag xpaths as keys and listing options (yes, no, snippet or highlight) as values
   * @param int $offset offset
   * @param int $docs max number of docs to return
   */
  public function __construct($list, $offset = '', $docs = '')
  {
    parent::__construct('list-first', $offset, $docs, $list);
  }
}