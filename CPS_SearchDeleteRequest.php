<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_SearchDeleteRequest class is a wrapper for the Request class for the search-delete command
 * @package CPS
 * @see CPS_SearchDeleteResponse
 */
class CPS_SearchDeleteRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_SearchDeleteRequest class.
   * @param string $query The query string. see {@link CPS_SearchDeleteRequest::setQuery()} for more info.
   */
  public function __construct($query)
  {
    parent::__construct('search-delete');
    $this->setQuery($query);
  }

  /**
   * Sets the search query.
   *
   * Example usage:
   * <code>$r->setQuery('(' . CPS_Term('predefined_term', '/generated_fields/type/') . CPS_QueryTerm($user_supplied_terms, '/searchable_fields/text') . ')');</code>
   * @param string $value The query string.
   * All <, > and & characters that aren't supposed to be XML tags, should be escaped (e.g. with {@link CPS_Term} or {@link CPS_QueryTerm});
   * @see CPS_QueryTerm, CPS_Term
   */
  public function setQuery($value)
  {
    $this->setParam('query', $value);
  }

  /**
   * Sets the stemming language
   * @param string $value 2-letter language ID
   */
  public function setStemLang($value)
  {
    $this->setParam('stem-lang', $value);
  }

  /**
   * Sets the exact match option
   * @param string $value Exact match option : text, binary or all
   */
  public function setExactMatch($value)
  {
    $this->setParam('exact-match', $value);
  }
}