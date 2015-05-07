<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_SQLSearchRequest class is virtually identical to the {@link CPS_SearchRequest} class, but
 * is used for querying the database through the Basic SQL interface
 * @package CPS
 * @see CPS_SearchResponse
 */
class CPS_SQLSearchRequest extends CPS_SearchRequest
{
  /**
   * Constructs an instance of the CPS_SQLSearchRequest class.
   * @param string $sql The SQL query
   */
  public function __construct($sql_query)
  {
    CPS_Request::__construct('search');
    $this->setParam('sql', $sql_query);
  }
}