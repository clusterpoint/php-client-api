<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_AlternativesRequest class is a wrapper for the Response class for the alternatives command
 * @package CPS
 * @see CPS_AlternativesResponse
 */
class CPS_AlternativesRequest extends CPS_Request
{
  /**
   * Constructs an instance of CPS_AlternativesRequest
   *
   * @param string $query see {@link setQuery}
   * @param float $cr see {@link setCr}
   * @param float $idif see {@link setIdif}
   * @param float $h see {@link setH}
   */
  public function __construct($query, $cr = NULL, $idif = NULL, $h = NULL)
  {
    parent::__construct('alternatives');
    $this->setQuery($query);
    if (!is_null($cr))
      $this->setCr($cr);
    if (!is_null($idif))
      $this->setIdif($idif);
    if (!is_null($h))
      $this->setH($h);
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
   * Minimum ratio between the occurrence of the alternative and the occurrence of the search term.
   * If this parameter is increased, less results are returned while performance is improved.
   * @param float $value
   */
  public function setCr($value)
  {
    $this->setParam('cr', $value);
  }

  /**
   * A number that limits how much the alternative may differ from the search term,
   * the greater the idif value, the greater the allowed difference.
   * If this parameter is increased, more results are returned while performance is decreased.
   * @param float $value
   */
  public function setIdif($value)
  {
    $this->setParam('idif', $value);
  }

  /**
   * A number that limits the overall estimate of the quality of the alternative,
   * the greater the cr value and the smaller the idif value, the greater the h value.
   * If this parameter is increased, less results are returned while performance is improved.
   * @param float $value
   */
  public function setH($value)
  {
    $this->setParam('h', $value);
  }
}
