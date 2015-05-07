<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_EnableAlertsRequest class is a wrapper for the Request class for the enable-alerts command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_EnableAlertsRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_EnableAlertsRequest class.
   */
  public function __construct()
  {
    parent::__construct('enable-alerts');
  }
}
