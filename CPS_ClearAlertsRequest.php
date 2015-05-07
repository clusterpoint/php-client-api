<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_ClearAlertsRequest class is a wrapper for the Request class for the clear-alerts command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_ClearAlertsRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_ClearAlertsRequest class.
   */
  public function __construct()
  {
    parent::__construct('clear-alerts');
  }
}
