<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_AddAlertsRequest class is a wrapper for the Response class for the update-alert command
 * @package CPS
 */
class CPS_UpdateAlertRequest extends CPS_Request
{
  public function __construct($alerts)
  {
    parent::__construct('update-alert');
    if (!is_array($alerts)) $alerts = array($alerts);
    $alertsXML = array();
    foreach ($alerts as $alert) {
      $alertsXML[] = $alert->toXML();
    }
    $this->setParam('alert', $alertsXML);
  }
}
