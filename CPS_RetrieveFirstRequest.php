<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_RetrieveFirstRequest class is a wrapper for the Request class for retrieve-first requests
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_RetrieveFirstRequest extends CPS_ListLastRetrieveFirstRequest
{
  /**
   * Constructs an instance of the CPS_RetrieveFirst class.
   * @param int $offset offset
   * @param int $docs max number of docs to return
   */
  public function __construct($offset = '', $docs = '')
  {
    parent::__construct('retrieve-first', $offset, $docs);
  }
}