<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_ListAlertsRequest class is a wrapper for the Request class for the list-alerts command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_ListAlertsRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_ListAlertsRequest class.
   */
  public function __construct($offset = 0, $count = 10)
  {
    parent::__construct('list-alerts');
    $this->setParam('offset', $offset);
    $this->setParam('count', $count);
  }
}
