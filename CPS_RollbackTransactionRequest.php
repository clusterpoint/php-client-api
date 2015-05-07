<?php
//<namespace
namespace cps;
//namespace>

class CPS_RollbackTransactionRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_RollbackTransactionRequest class.
   */
  public function __construct()
  {
    parent::__construct("rollback-transaction");
  }
}
