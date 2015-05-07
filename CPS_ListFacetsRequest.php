<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_ListFacetsRequest class is a wrapper for the Response class for the list-facets command
 * @package CPS
 * @see CPS_ListFacetsResponse
 */
class CPS_ListFacetsRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_ListFacetsRequest class.
   * @param array|string $paths A single facet path as string or an array of paths to list the facet terms from
   */
  public function __construct($paths)
  {
    parent::__construct('list-facets');
    $this->setParam('path', $paths);
  }
}