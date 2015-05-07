<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_RunAlertsRequest class is a wrapper for the Request class for the run-alerts command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_RunAlertsRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_RunAlertsRequest class.
   * @param string|array $ids Either the document id as string or an array of document IDs to be examined again
   */
  public function __construct($ids, $alert_ids = array())
  {
    parent::__construct('run-alerts');
    $this->setParam('id', $ids);
    if (count($alert_ids)) $this->setParam('alert_id', $alert_ids);
  }
}
