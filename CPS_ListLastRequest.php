<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_ListLastRequest class is a wrapper for the Request class for list-last requests
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_ListLastRequest extends CPS_ListLastRetrieveFirstRequest
{
  /**
   * Constructs an instance of the CPS_ListLast class.
   * @param array $list an associative array with tag xpaths as keys and listing options (yes, no, snippet or highlight) as values
   * @param int $offset offset
   * @param int $docs max number of docs to return
   */
  public function __construct($list, $offset = '', $docs = '')
  {
    parent::__construct('list-last', $offset, $docs, $list);
  }
}