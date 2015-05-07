<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_DisableAlertsRequest class is a wrapper for the Request class for the disable-alerts command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_DisableAlertsRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_DisableAlertsRequest class.
   */
  public function __construct()
  {
    parent::__construct('disable-alerts');
  }
}
