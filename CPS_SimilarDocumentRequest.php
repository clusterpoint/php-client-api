<?php
//<namespace
namespace cps;
//namespace>

/**
 * The CPS_SimilarDocumentRequest class is a wrapper for the Response class for the similar command for documents
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_SimilarDocumentRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_SimilarDocumentRequest class.
   * @param string $docid ID of the source document - the one that You want to search similar documents to
   * @param int $len number of keywords to extract from the source
   * @param int $quota minimum number of keywords matching in the destination
   * @param int $offset number of results to skip before returning the following ones
   * @param int $docs number of documents to retrieve
   * @param string $query an optional query that all found documents have to match against
   */
  public function __construct($docid, $len, $quota, $offset = NULL, $docs = NULL, $query = NULL)
  {
    parent::__construct('similar');
    $this->setParam('id', $docid);
    $this->setParam('len', $len);
    $this->setParam('quota', $quota);
    if (!is_null($docs))
      $this->setParam('docs', $docs);
    if (!is_null($offset))
      $this->setParam('offset', $offset);
    if (!is_null($query))
      $this->setParam('query', $query);
  }
}
