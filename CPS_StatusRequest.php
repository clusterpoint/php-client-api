<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_StatusRequest class is a wrapper for the Response class for the status command
 * @package CPS
 * @see CPS_StatusResponse
 */
class CPS_StatusRequest extends CPS_Request
{
  /**
   * Constructs an instance of CPS_StatusRequest
   */
  public function __construct()
  {
    parent::__construct('status');
  }
}