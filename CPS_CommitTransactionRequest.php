<?php
//<namespace
namespace cps;
//namespace>

class CPS_CommitTransactionRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_CommitTransactionRequest class.
   */
  public function __construct()
  {
    parent::__construct("commit-transaction");
  }
}
