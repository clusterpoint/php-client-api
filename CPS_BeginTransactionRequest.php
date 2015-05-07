<?php
//<namespace
namespace cps;
//namespace>

class CPS_BeginTransactionRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_BeginTransactionRequest class.
   */
  public function __construct()
  {
    parent::__construct("begin-transaction");
  }
}
