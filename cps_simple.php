<?php
/**
 * CPS Simple API
 * @package CPS
 */
 
/**
* including CPS API
*/

require_once dirname(__FILE__) . '/cps_api.php';

/**
* The CPS_Simple class contains methods suitable for most requests that don't require advanced parameters to be specified
* @package CPS
*/
class CPS_Simple {
	/**
	* Constructs an instance of the CPS_Simple class.
	* @param CPS_Connection $connection A CPS_Connection object
	*/
	public function __construct(CPS_Connection $connection) {
		$this->_connection = $connection;
		$this->_lastResponse = NULL;
	}
	
	/**
	* Performs a search command. Returns the documents found in an associative array with document IDs as keys and document contents as values
	* @param array|string $query The query array/string. see {@link CPS_SearchRequest::setQuery()} for more info on best practices.
	* @param int $offset Defines the number of documents to skip before including them in the results
	* @param int $docs Maximum document count to retrieve
	* @param array $list Listing parameter - an associative array with tag xpaths as keys and listing options (yes | no | snippet | highlight) as values
	* @param string|array $ordering Defines the order in which results will be returned. Contains either a single sorting string or an array of those. Could be conveniently generated with ordering macros, e.g. $q->setOrdering(array(CPS_NumericOrdering('user_count', 'desc'), CPS_RelevanceOrdering())) will sort the documents in descending order according to the user_count, and if user_count is equal will sort them by relevance.
	* @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
	* @return array
	*/
	public function search($query, $offset = NULL, $docs = NULL, $list = NULL, $ordering = NULL, $returnType = DOC_TYPE_SIMPLEXML, $stemLang = NULL) {
		$request = new CPS_SearchRequest($query, $offset, $docs, $list);
		if (!is_null($ordering))
			$request->setOrdering($ordering);
		if (!is_null($stemLang))
			$request->setStemLang($stemLang);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments($returnType);
	}
	
	/**
	* Performs a search command with an SQL query. Returns the documents found in an associative array with document IDs as keys and document contents as values
	* @param string $sql_query The SQL query string.
	* @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
	* @return array
	*/
	public function sql_search($sql_query, $returnType = DOC_TYPE_SIMPLEXML) {
		$request = new CPS_SQLSearchRequest($sql_query);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments($returnType);
	}
	
	/**
	* Performs an insert command. Returns the number of modified documents
	* @param string $id The ID of the document to insert
	* @param array|SimpleXMLElement|stdClass $document The document contents
	* @return int
	*/
	public function insertSingle($id, $document) {
		$request = new CPS_InsertRequest($id, $document);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return count($this->_lastResponse->getModifiedIds());
	}
	
	/**
	* Performs an insert command for multiple documents. Returns the number of modified documents
	* @param array $docs an associative array with document IDs as keys and document contents as values
	* @return int
	*/
	public function insertMultiple($docs) {
		$request = new CPS_InsertRequest($docs);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return count($this->_lastResponse->getModifiedIds());
	}
	
	/**
	* Performs an update command. Returns the number of modified documents
	* @param string $id The ID of the document to update
	* @param array|SimpleXMLElement|stdClass $document The document contents
	* @return int
	*/
	public function updateSingle($id, $document) {
		$request = new CPS_UpdateRequest($id, $document);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return count($this->_lastResponse->getModifiedIds());
	}
	
	/**
	* Performs an update command for multiple documents. Returns the number of modified documents
	* @param array $docs an associative array with document IDs as keys and document contents as values
	* @return int
	*/
	public function updateMultiple($docs) {
		$request = new CPS_UpdateRequest($docs);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return count($this->_lastResponse->getModifiedIds());
	}
	
	/**
	* Performs a replace command. Returns the number of modified documents
	* @param string $id The ID of the document to replace
	* @param array|SimpleXMLElement|stdClass $document The document contents
	* @return int
	*/
	public function replaceSingle($id, $document) {
		$request = new CPS_ReplaceRequest($id, $document);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return count($this->_lastResponse->getModifiedIds());
	}
	
	/**
	* Performs a replace command for multiple documents. Returns the number of modified documents
	* @param array $docs an associative array with document IDs as keys and document contents as values
	* @return int
	*/
	public function replaceMultiple($docs) {
		$request = new CPS_ReplaceRequest($docs);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return count($this->_lastResponse->getModifiedIds());
	}
	
	/**
	* Performs a partial-replace command. Returns the number of modified documents
	* @param string $id The ID of the document to replace
	* @param array|SimpleXMLElement|stdClass $document The document contents that are to be replaced
	* @return int
	*/
	public function partialReplaceSingle($id, $document) {
		$request = new CPS_PartialReplaceRequest($id, $document);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return count($this->_lastResponse->getModifiedIds());
	}
	
	/**
	* Performs a partial-replace command for multiple documents. Returns the number of modified documents
	* @param array $docs an associative array with document IDs as keys and replaceable document contents as values
	* @return int
	*/
	public function partialReplaceMultiple($docs) {
		$request = new CPS_PartialReplaceRequest($docs);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return count($this->_lastResponse->getModifiedIds());
	}
	
	/**
	* Performs a partial-xreplace command. Returns the number of modified documents
	* @param string|array $ids The ID of the document to replace or an array of such documents
	* @param CPX_PRX_Operation|array $operations The operation to perform or an array of such operations
	* @see CPS_PRX_Operation
	* @return int
	*/
	public function partialXReplace($ids, $operations) {
		$request = new CPS_PartialXRequest($ids, $operations);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return count($this->_lastResponse->getModifiedIds());
	}
	
	/**
	* Performs a delete command. Returns the number of deleted documents
	* @param string|array $id The ID of the document to delete as a string or an array of IDs
	* @return int
	*/

	public function delete($id) {
		$request = new CPS_DeleteRequest($id);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return count($this->_lastResponse->getModifiedIds());
	}
	
	/**
	* Performs a list-last command. Returns an associative array with document IDs as keys and document contents as values
	* @param array $list a listing parameter - an associative array with xpaths as keys and listing options (yes | no | snippet | highlight) as values
	* @param int $offset offset
	* @param int $docs the maximum number of documents to return
	* @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
	* @return array
	*/

	public function listLast($list = NULL, $offset = '', $docs = '', $returnType = DOC_TYPE_SIMPLEXML) {
		$request = new CPS_ListLastRequest($list, $offset, $docs);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments($returnType);
	}
	
	/**
	* Performs a list-first command. Returns an associative array with document IDs as keys and document contents as values
	* @param array $list a listing parameter - an associative array with xpaths as keys and listing options (yes | no | snippet | highlight) as values
	* @param int $offset offset
	* @param int $docs the maximum number of documents to return
	* @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
	* @return array
	*/

	public function listFirst($list = NULL, $offset = '', $docs = '', $returnType = DOC_TYPE_SIMPLEXML) {
		$request = new CPS_ListFirstRequest($list, $offset, $docs);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments($returnType);
	}
	
	/**
	* Performs a retrieve-last command. Returns an associative array with document IDs as keys and document contents as values
	* @param int $offset offset
	* @param int $docs the maximum number of documents to return
	* @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
	* @return array
	*/

	public function retrieveLast($offset = '', $docs = '', $returnType = DOC_TYPE_SIMPLEXML) {
		$request = new CPS_RetrieveLastRequest($offset, $docs);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments($returnType);
	}
	
	/**
	* Performs a retrieve-first command. Returns an associative array with document IDs as keys and document contents as values
	* @param int $offset offset
	* @param int $docs the maximum number of documents to return
	* @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
	* @return array
	*/

	public function retrieveFirst($offset = '', $docs = '', $returnType = DOC_TYPE_SIMPLEXML) {
		$request = new CPS_RetrieveFirstRequest($offset, $docs);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments($returnType);
	}
	
	/**
	* Performs a retrieve command for a single document. Returns the document contents
	* @param string $id document ID
	* @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
	* @return array|SimpleXMLElement|stdClass
	*/

	public function retrieveSingle($id, $returnType = DOC_TYPE_SIMPLEXML) {
		$request = new CPS_RetrieveRequest($id);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		foreach ($this->_lastResponse->getDocuments($returnType) as $document) {
			return $document;
		}
		return NULL;
	}
	
	/**
	* Performs a retrieve command for multiple documents. Returns an associative array with document IDs as keys and document contents as values
	* @param array $ids an array of document IDs
	* @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
	* @return array
	*/

	public function retrieveMultiple($ids, $returnType = DOC_TYPE_SIMPLEXML) {
		$request = new CPS_RetrieveRequest($ids);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments($returnType);
	}
	
	/**
	* Performs a lookup command for a single document. Returns the document contents
	* @param string $id document ID
	* @param array $list listing parameter associative array - xpaths as keys, listing options as values
	* @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
	* @return array|SimpleXMLElement|stdClass
	*/

	public function lookupSingle($id, $list=NULL, $returnType = DOC_TYPE_SIMPLEXML) {
		$request = new CPS_LookupRequest($id, $list);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		foreach ($this->_lastResponse->getDocuments($returnType) as $document) {
			return $document;
		}
		return NULL;
	}
	
	/**
	* Performs a lookup command for multiple documents. Returns an associative array with document IDs as keys and document contents as values
	* @param array $ids an array of document IDs
	* @param array $list listing parameter associative array - xpaths as keys, listing options as values
	* @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
	* @return array
	*/

	public function lookupMultiple($ids, $list = NULL, $returnType = DOC_TYPE_SIMPLEXML) {
		$request = new CPS_LookupRequest($ids, $list);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments($returnType);
	}
	
	/**
	* Performs a list-words command for one or multiple patterns with wildcards
	* @param string|array $wildcard a pattern to match against, or an array of patterns
	* @return array
	*/

	public function listWords($wildcard) {
		$request = new CPS_ListWordsRequest($wildcard);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getWords();
	}
	
	/**
	* Performs an alternatives command for the specified query. Returns a string with the most probable query (could be identical to the given one)
	*
	* @param string $query see {@link CPS_AlternativesRequest::setQuery()}
	* @param float $cr see {@link CPS_AlternativesRequest::setCr()}
	* @param float $idif see {@link CPS_AlternativesRequest::setIdif()}
	* @param float $h see {@link CPS_AlternativesRequest::setH()}
	* @return array
	*/
	public function alternatives($query, $cr = NULL, $idif = NULL, $h = NULL) {
		$request = new CPS_AlternativesRequest($query, $cr, $idif, $h);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		$words = $this->_lastResponse->getWords();
		$res = '';
		$xp_accum = '';
		$prevxpath = NULL;
		foreach ($words as $original => $spellings) {
			$xpath = '';
			if (($pos = strpos($original, '/')) !== FALSE) {
				$xpath = substr($original, 0, $pos);
				$original = substr($original, $pos + 1);
			}
			if (!is_null($prevxpath) && ($xpath != $prevxpath)) {
				$res .= CPS_Term($xp_accum, $prevxpath);
				$xp_accum = '';
			}
			$prevxpath = $xpath;
			if (count($spellings) > 0) {
				foreach ($spellings as $key => $n) {
					$xp_accum .= ($xp_accum == '' ? '' : ' ') . $key;
					break;
				}
			} else {
				$xp_accum .= ($xp_accum == '' ? '' : ' ') . $original;
			}
		}
		if (strlen($xp_accum) > 0) {
			$res .= ($res == '' ? '' : ' ') . CPS_Term($xp_accum, is_null($prevxpath) ? '' : $prevxpath);
		}
		return $res;
	}
	
	/**
	* Performs the status command. Returns an array with status data
	* @return array
	*/
	public function status() {
		$request = new CPS_StatusRequest();
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getStatus();
	}
	
	/**
	* Performs a search-delete command. Returns the number of the documents erased
	* @param string $query The query string. see {@link CPS_SearchRequest::setQuery()} for more info on best practices.
	* @return int
	*/
	public function searchDelete($query) {
		$request = new CPS_SearchDeleteRequest($query);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getHits();
	}
	
	/**
	* Performs a list-paths command. Returns all available paths in an array
	* @return array
	*/
	public function listPaths() {
		$request = new CPS_ListPathsRequest();
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getPaths();
	}
	
	/**
	* Performs a list-facets command. Returns an associative array where keys are facet paths and values are arrays of terms
	* @param $paths a single facet path as string or an array of paths
	* @return array
	*/
	public function listFacets($paths) {
		$request = new CPS_ListFacetsRequest($paths);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getFacets();
	}
	
	/**
	* Finds documents similar to the document with a given ID. Returns an associative array with similar document IDs as keys and document contents as values
	* @param string $docid ID of the source document - the one that You want to search similar documents to
	* @param int $len number of keywords to extract from the source
	* @param int $quota minimum number of keywords matching in the destination
	* @param int $offset number of results to skip before returning the following ones
	* @param int $docs number of documents to retrieve
	* @param string $query an optional query that all found documents have to match against
	* @return array
	*/
	public function similarDocument($docid, $len, $quota, $offset=NULL, $docs=NULL, $query=NULL) {
		$request = new CPS_SimilarDocumentRequest($docid, $len, $quota, $offset, $docs, $query);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments();
	}
	
	/**
	* Finds documents similar to the given text. Returns an associative array with similar document IDs as keys and document contents as values
	* @param string $text A chunk of text that the found documents have to be similar to
	* @param int $len number of keywords to extract from the source
	* @param int $quota minimum number of keywords matching in the destination
	* @param int $offset number of results to skip before returning the following ones
	* @param int $docs number of documents to retrieve
	* @param string $query an optional query that all found documents have to match against
	* @return array
	*/
	public function similarText($text, $len, $quota, $offset=NULL, $docs=NULL, $query=NULL) {
		$request = new CPS_SimilarTextRequest($text, $len, $quota, $offset, $docs, $query);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments();
	}
	
	/**
	* Retrieves modification history of the document. Returns an associative array with revision IDs as keys and document contents/versioning metadata as values
	* @param string $id Document ID
	* @param bool $returnDocs set to true to return document content (subject to license conditions)
	* @return array
	*/
	public function showHistory($id, $returnDocs = false) {
		$request = new CPS_ShowHistoryRequest($id, $returnDocs);
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return $this->_lastResponse->getDocuments();
	}
	
	/**
	* Begins new transaction
	*/
	public function beginTransaction() {
		$request = new CPS_BeginTransactionRequest();
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return true;
	}
	
	/**
	* Commits last transaction
	*/
	public function commitTransaction() {
		$request = new CPS_CommitTransactionRequest();
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return true;
	}
	
	/**
	* Rollbacks last transaction
	*/
	public function rollbackTransaction() {
		$request = new CPS_RollbackTransactionRequest();
		$this->_lastResponse = $this->_connection->sendRequest($request);
		return true;
	}
	
	/**
	* Returns last received response. This could be useful if you need extra information from the response object
	* @return CPS_Response
	*/
	public function getLastResponse() {
		return $this->_lastResponse;
	}
	
	/**#@+
	 * @access private
	 */
	private $_connection;
	private $_lastResponse;
	/**#@-*/
}