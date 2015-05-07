<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_ListWordsRequest class is a wrapper for the Response class for the list-words command
 * @package CPS
 * @see CPS_ListWordsResponse
 */
class CPS_ListWordsRequest extends CPS_Request
{
  /**
   * Constructs an instance of CPS_ListWordsRequest
   *
   * @param string $query see {@link setQuery}
   */
  public function __construct($query)
  {
    parent::__construct('list-words');
    if (is_array($query))
      $query = implode(' ', $query);
    $this->setQuery($query);
  }

  /**
   * Sets the query
   *
   * @param string|array $value a single term with a wildcard as a string or an array of terms
   */
  public function setQuery($value)
  {
    $this->setParam('query', $value);
  }
}