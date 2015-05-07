<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_RetrieveLastRequest class is a wrapper for the Request class for retrieve-last requests
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_RetrieveLastRequest extends CPS_ListLastRetrieveFirstRequest
{
  /**
   * Constructs an instance of the CPS_RetrieveLast class.
   * @param int $offset offset
   * @param int $docs max number of docs to return
   */
  public function __construct($offset = '', $docs = '')
  {
    parent::__construct('retrieve-last', $offset, $docs);
  }
}