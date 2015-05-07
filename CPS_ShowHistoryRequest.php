<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_ShowHistoryRequest class is a wrapper for the Response class for the show-history command
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_ShowHistoryRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_ShowHistoryRequest class.
   * @param string $id Document ID
   * @param bool $returnDocs set this to true if you want historical document content returned as well
   */
  public function __construct($id, $returnDocs = false)
  {
    parent::__construct('show-history');
    $this->_documents = array($id => NULL);
    if ($returnDocs)
      $this->setParam('return_doc', 'yes');
  }
}
