<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_RetrieveRequest class is a wrapper for the Request class
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_RetrieveRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_RetrieveRequest class.
   * @param string|array $id Either the document id as string or an array of document IDs to be retrieved
   */
  public function __construct($id)
  {
    parent::__construct('retrieve');
    if (!is_array($id)) {
      $this->_documents = array((string)$id => NULL);
    } else {
      $this->_documents = array();
      foreach ($id as $value) {
        $this->_documents[$value] = NULL;
      }
    }
  }
}