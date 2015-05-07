<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_SearchRequest class is a wrapper for the Request class
 * @package CPS
 * @see CPS_SearchResponse
 */
class CPS_SearchRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_SearchRequest class.
   * @param array|string $query The query array/string. see {@link CPS_SearchRequest::setQuery()} for more info.
   * @param int $offset Defines the number of documents to skip before including them in the results
   * @param int $docs Maximum document count to retrieve
   * @param array $list Listing parameter - an associative array with tag xpaths as keys and listing options (yes | no | snippet | highlight) as values
   */
  public function __construct($query, $offset = NULL, $docs = NULL, $list = NULL)
  {
    parent::__construct('search');
    $this->setQuery($query);
    if (!is_null($offset))
      $this->setOffset($offset);
    if (!is_null($docs))
      $this->setDocs($docs);
    if (!is_null($list))
      $this->setList($list);
  }

  /**
   * Sets the search query.
   *
   * Example usage:
   * <code>$r->setQuery('(' . CPS::Term('predefined_term', '/generated_fields/type/') . CPS_QueryTerm($user_supplied_terms, '/searchable_fields/text') . ')');</code>
   * or
   * <code>$r->setQuery(array('tags' => array('title' => 'Title', 'text' => 'Text')));</code>
   * or
   * <code>$r->setQuery(array('tags/title' => 'Title', 'tags/text' => 'Text'));</code>
   * @param array|string $value The query array/string.
   * If the string form is used, all <, > and & characters that aren't supposed to be XML tags, should be escaped (e.g. with {@link CPS::Term} or {@link CPS_QueryTerm});
   * @see CPS_QueryTerm, CPS::Term
   */
  public function setQuery($value)
  {
    if (is_array($value)) {
      $this->setParam('query', CPS::QueryArray($value));
    } else {
      $this->setParam('query', $value);
    }
  }

  /**
   * Sets the maximum number of documents to be returned
   * @param int $value maximum number of documents
   */
  public function setDocs($value)
  {
    $this->setParam('docs', $value);
  }

  /**
   * Sets the number of documents to skip in the results
   * @param int $value number of results to skip
   */
  public function setOffset($value)
  {
    $this->setParam('offset', $value);
  }

  /**
   * Sets the paths for facets
   * @param string|array $value a single path as a string or an array of paths
   */
  public function setFacet($value)
  {
    $this->setParam('facet', $value);
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

  /**
   * Sets grouping options
   * @param string $tagName name of the grouping tag
   * @param int $count maximum number of documents to return from each group
   */
  public function setGroup($tagName, $count)
  {
    $this->setParam('group', $tagName);
    $this->setParam('group_size', $count);
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

  /**
   * Defines the order in which results should be returned.
   * @param string|array $order either a single sorting string or an array of those. Could be conveniently generated with ordering macros,
   * e.g. $q->setOrdering(array(CPS::NumericOrdering('user_count', 'desc'), CPS::RelevanceOrdering())) will sort the documents in descending order
   * according to the user_count, and if user_count is equal will sort them by relevance.
   * @see CPS::RelevanceOrdering, CPS::NumericOrdering, CPS::LatLonDistanceOrdering, CPS::PlainDistanceOrdering
   */
  public function setOrdering($order)
  {
    if (is_array($order)) {
      $order = implode('', $order);
    }
    $this->setParam('ordering', $order);
  }

  /**
   * Defines aggregation queries for the search request
   * @param string|array $aggregate either a single aggregation query, or an array of queries
   * @see CPS_SearchResponse::getAggregate()
   */
  public function setAggregate($aggregate)
  {
    $this->setParam('aggregate', $aggregate);
  }
}