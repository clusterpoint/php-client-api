<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_RemoveAlertsRequest class is a wrapper for the Request class for the delete-alert command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_DeleteAlertRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_RemoveAlertsRequest class.
   * @param string|array $ids Either the alert id as string or an array of alert IDs to be deleted
   */
  public function __construct($ids)
  {
    parent::__construct('delete-alert');
    $this->setParam('id', $ids);
  }
}
