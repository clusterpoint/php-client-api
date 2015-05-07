<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_LookupRequest class is a wrapper for the Request class
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_LookupRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_LookupRequest class.
   * @param string|array $id Either the document id as string or an array of document IDs to be retrieved
   * @param array $list listing parameters - an associative array with xpaths as values and snippeting options (yes | no | snippet | highlight) as values
   */
  public function __construct($id, $list = NULL)
  {
    parent::__construct('lookup');
    if (!is_array($id)) {
      $this->_documents = array((string)$id => NULL);
    } else {
      $this->_documents = array();
      foreach ($id as $value) {
        $this->_documents[$value] = NULL;
      }
    }
    if (!is_null($list))
      $this->setList($list);
  }

  /**
   * Defines which tags of the search results should be listed in the response
   * @param array $array an associative array with tag xpaths as keys and listing options (yes, no, snippet or highlight) as values
   */
  public function setList($array)
  {
    $listString = '';
    foreach ($array as $key => $value) {
      $listString .= CPS::Term($value, $key);
    }
    $this->setParam('list', $listString);
  }
}