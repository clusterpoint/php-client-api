<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_ListPathsRequest class is a wrapper for the Response class for the list-paths command
 * @package CPS
 * @see CPS_ListPathsResponse
 */
class CPS_ListPathsRequest extends CPS_Request
{
  /**
   * Constructs an instance of CPS_ListPathsRequest
   */
  public function __construct()
  {
    parent::__construct('list-paths');
  }
}