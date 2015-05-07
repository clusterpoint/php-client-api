<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_DeleteRequest class is a wrapper for the Response class for the delete command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_DeleteRequest extends CPS_ModifyRequest
{
  /**
   * Constructs an instance of the CPS_DeleteRequest class.
   * @param string|array $arg1 Either the document id as string or an array of document IDs to be deleted
   */
  public function __construct($arg1)
  {
    $nextArg = array();
    if (!is_array($arg1)) {
      $nextArg = array((string)$arg1 => NULL);
    } else {
      foreach ($arg1 as $value) {
        $nextArg[$value] = NULL;
      }
    }
    parent::__construct('delete', $nextArg, NULL);
  }
}
