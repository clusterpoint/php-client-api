<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_ListLastRetrieveFirstRequest class is a wrapper for the Request class for list-last, list-first, retrieve-last, and retrieve-first requests
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_ListLastRetrieveFirstRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_ListLastRetrieveFirstRequest class.
   * @param string $command command name
   * @param int $offset offset
   * @param int $docs max number of docs to return
   */
  public function __construct($command, $offset, $docs, $list = NULL)
  {
    parent::__construct($command);
    if (strlen($offset) > 0) {
      $this->setParam('offset', $offset);
    }
    if (strlen($docs) > 0) {
      $this->setParam('docs', $docs);
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