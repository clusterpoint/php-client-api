<?php
/**
 * Request and response classes for all commands in the CPS API
 * @package CPS
 */

/**
 * request/response includes
 */

require_once dirname(__FILE__) . '/request.class.php';
require_once dirname(__FILE__) . '/response.class.php';

/**
 * Escapes <, > and & characters in the given term for inclusion into XML (like the search query). Also wraps the term in XML tags if xpath is specified.
 * Note that this function doesn't escape the @, $, " and other symbols that are meaningful in a search query. If You want to escape input that comes directly
 * from the user and that isn't supposed to contain any search operators at all, it's probably better to use {@link CPS_QueryTerm}
 * @param string $term the term to be escaped (e.g. a search query term)
 * @param string $xpath an optional xpath, to be specified if the search term is to be searched under a specific xpath
 * @param bool $escape an optional parameter - whether to escape the term's XML
 * @see CPS_QueryTerm
 */
function CPS_Term($term, $xpath = '', $escape = TRUE)
{
    $prefix = ' ';
    $postfix = ' ';
    if (strlen($xpath) > 0) {
        $tags = explode('/', $xpath);
        foreach ($tags as $tag) {
            if (strlen($tag) > 0) {
                $prefix .= '<' . $tag . '>';
                $postfix = '</' . $tag . '>' . $postfix;
            }
        }
    }

	// ENT_NOQUOTES	Will leave both double and single quotes unconverted.
    if (defined('ENT_SUBSTITUTE')) { // php >5.4
        // ENT_SUBSTITUTE	Replace invalid code unit sequences with a Unicode Replacement Character U+FFFD (UTF-8) or &#FFFD; (otherwise) instead of returning an empty string.
        return $prefix . ($escape ? htmlspecialchars($term, ENT_NOQUOTES | ENT_SUBSTITUTE) : $term) . $postfix;
    }
    return $prefix . ($escape ? htmlspecialchars($term, ENT_NOQUOTES) : $term) . $postfix;
}

/**
 * Escapes <, > and & characters, as well as @"{}()=$~+ (search query operators) in the given term for inclusion into the search query.
 * Also wraps the term in XML tags if xpath is specified.
 * @param string $term the term to be escaped (e.g. a search query term)
 * @param string $xpath an optional xpath, to be specified if the search term is to be searched under a specific xpath
 * @param string $allowed_symbols a string containing operator symbols that the user is allowed to use (e.g. ")
 * @see CPS_Term
 */
function CPS_QueryTerm($term, $xpath = '', $allowed_symbols = '')
{
    $newTerm = '';
    $len = strlen($term);
    for ($x = 0; $x < $len; ++$x) {
        switch ($term[$x]) {
            case '@':
            case '$':
            case '"':
            case '=':
            case '>':
            case '<':
            case ')':
            case '(':
            case '{':
            case '}':
            case '~':
            case '+':
                if (strstr($allowed_symbols, $term[$x]) === FALSE)
                    $newTerm .= '\\';
            default:
                $newTerm .= $term[$x];
        }
    }
    return CPS_Term($newTerm, $xpath);
}

/**
 * Converts a given query array to a query string
 * @param array $array the query array
 * @return string
 */
function CPS_QueryArray($array)
{
    $r = '';
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $r .= CPS_Term(CPS_QueryArray($value), $key, false);
        } else {
            $r .= CPS_Term($value, $key);
        }
    }
    return $r;
}

/**
 * Returns an circle definition string with provided center and radius
 * @function
 * @param String $name name of a shape, should be a valid xml name
 * @param array $center array with two elements identifying center of circle
 * @param double|String $radius radius of circle with optional distance type (km/mi), default is km
 * @param String $tagName1 tag name of first coordinate (e.g. latitude), if not passed, then default configuration values will be used
 * @param String $tagName2 tag name of second coordinate (e.g. longitude), if not passed, then default configuration values will be used
 * @param String $coord_type coordinate type, either latlong or plane
 */
function CPS_CircleDefinition($name, $center, $radius, $tagName1 = null, $tagName2 = null, $coord_type = null)
{
    $res = '<' . $name . '>';
    $res .= '<center>' . $center[0] . ' ' . $center[1] . '</center>';
    $res .= '<radius>' . $radius . '</radius>';
    if ($tagName1)
        $res .= '<coord1_tag_name>' . $tagName1 . '</coord1_tag_name>';
    if ($tagName2)
        $res .= '<coord2_tag_name>' . $tagName2 . '</coord2_tag_name>';
    if ($coord_type)
        $res .= '<coord_type>' . $coord_type . '</coord_type>';
    $res .= '</' . $name . '>';
    return $res;
}

/**
 * Returns an polygon definition string from provided vertice points
 * @function
 * @param String $name name of a shape, should be a valid xml name
 * @param array $vertices array of vertice coordinates identifying polygon each element should contain array of two elements which correspond to vertice coordinates
 * @param String $tagName1 tag name of first coordinate (e.g. latitude), if not passed, then default configuration values will be used
 * @param String $tagName2 tag name of second coordinate (e.g. longitude), if not passed, then default configuration values will be used
 * @param String $coord_type coordinate type, either latlong or plane
 */
function CPS_PolygonDefinition($name, $vertices, $tagName1 = null, $tagName2 = null, $coord_type = null)
{
    $res = '<' . $name . '>';
    foreach ($vertices as $vertice) {
        $res .= $vertice[0] . ' ' . $vertice[1] . '; ';
    }
    if ($tagName1)
        $res .= '<coord1_tag_name>' . $tagName1 . '</coord1_tag_name>';
    if ($tagName2)
        $res .= '<coord2_tag_name>' . $tagName2 . '</coord2_tag_name>';
    if ($coord_type)
        $res .= '<coord_type>' . $coord_type . '</coord_type>';
    $res .= '</' . $name . '>';
    return $res;
}

/**
 * Returns an ordering string for sorting by relevance
 * @see CPS_SearchRequest::setOrdering()
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default most relevant documents are returned first
 */

function CPS_RelevanceOrdering($ascdesc = '')
{
    return '<relevance>' . htmlspecialchars($ascdesc, ENT_NOQUOTES) . '</relevance>';
}

/**
 * Returns an ordering string for sorting by a numeric field
 * @see CPS_SearchRequest::setOrdering()
 * @param string $tag the xpath of the tag by which You wish to perform sorting
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default ascending order is used.
 */

function CPS_NumericOrdering($tag, $ascdesc = 'ascending')
{
    return '<numeric>' . CPS_Term($ascdesc, $tag) . '</numeric>';
}

/**
 * Returns an ordering string for sorting by a date field
 * @see CPS_SearchRequest::setOrdering()
 * @param string $tag the xpath of the tag by which You wish to perform sorting
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default ascending order is used.
 */

function CPS_DateOrdering($tag, $ascdesc = 'ascending')
{
    return '<date>' . CPS_Term($ascdesc, $tag) . '</date>';
}

/**
 * Returns an ordering string for sorting by a string field
 * @see CPS_SearchRequest::setOrdering()
 * @param string $tag the xpath of the tag by which You wish to perform sorting
 * @param string $lang specifies the language (collation) to be used for ordering. E.g. "en"
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default ascending order is used.
 */

function CPS_StringOrdering($tag, $lang, $ascdesc = 'ascending')
{
    return '<string>' . CPS_Term($ascdesc . ',' . $lang, $tag) . '</string>';
}

/**#@+
 * @access private
 */
function CPS_GenericDistanceOrdering($type, $array, $ascdesc)
{
    $res = '<distance type="' . htmlspecialchars($type) . '" order="' . htmlspecialchars($ascdesc) . '">';
    foreach ($array as $path => $value) {
        $res .= CPS_Term($value, $path);
    }
    $res .= '</distance>';
    return $res;
}

/**#@-*/

/**
 * Returns an ordering string for sorting by distance from a latitude/longitude coordinate pair
 * @see CPS_SearchRequest::setOrdering()
 * @param array $array an associative array with tag xpaths as keys and centerpoint coordinates as values. Should contain exactly two elements - latitude first and longitude second.
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default ascending order is used.
 */

function CPS_LatLonDistanceOrdering($array, $ascdesc = 'ascending')
{
    return CPS_GenericDistanceOrdering('latlong', $array, $ascdesc);
}

/**
 * Returns an ordering string for sorting by distance from specified coordinates on a geometric plane
 * @see CPS_SearchRequest::setOrdering()
 * @param array $array an associative array with tag xpaths as keys and centerpoint coordinates as values.
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default ascending order is used.
 */
function CPS_PlaneDistanceOrdering($array, $ascdesc = 'ascending')
{
    return CPS_GenericDistanceOrdering('plane', $array, $ascdesc);
}

// Search

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
     * <code>$r->setQuery('(' . CPS_Term('predefined_term', '/generated_fields/type/') . CPS_QueryTerm($user_supplied_terms, '/searchable_fields/text') . ')');</code>
     * or
     * <code>$r->setQuery(array('tags' => array('title' => 'Title', 'text' => 'Text')));</code>
     * or
     * <code>$r->setQuery(array('tags/title' => 'Title', 'tags/text' => 'Text'));</code>
     * @param array|string $value The query array/string.
     * If the string form is used, all <, > and & characters that aren't supposed to be XML tags, should be escaped (e.g. with {@link CPS_Term} or {@link CPS_QueryTerm});
     * @see CPS_QueryTerm, CPS_Term
     */
    public function setQuery($value)
    {
        if (is_array($value)) {
            $this->setParam('query', CPS_QueryArray($value));
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
            $listString .= CPS_Term($value, $key);
        }
        $this->setParam('list', $listString);
    }

    /**
     * Defines the order in which results should be returned.
     * @param string|array $order either a single sorting string or an array of those. Could be conveniently generated with ordering macros,
     * e.g. $q->setOrdering(array(CPS_NumericOrdering('user_count', 'desc'), CPS_RelevanceOrdering())) will sort the documents in descending order
     * according to the user_count, and if user_count is equal will sort them by relevance.
     * @see CPS_RelevanceOrdering, CPS_NumericOrdering, CPS_LatLonDistanceOrdering, CPS_PlainDistanceOrdering
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

/**
 * The CPS_SearchResponse class is a wrapper for the Response class
 * @package CPS
 * @see CPS_SearchRequest
 */
class CPS_SearchResponse extends CPS_Response
{
    /**
     * Returns the documents from the response as an associative array, where keys are document IDs and values area document contents
     * @param int $type defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
     * @return array
     */
    public function getDocuments($type = DOC_TYPE_SIMPLEXML)
    {
        if (isset($this->_simpleXml->cursor_id)) return $this->getCursor($type);
        return parent::getRawDocuments($type);
    }

    /**
     * Returns the facets from the response in a form of a multi-dimensional associative array, e.g. array('category' => array('Sports' => 15, 'News' => 20));
     * @return array
     */
    public function getFacets()
    {
        return parent::getRawFacets();
    }

    /**
     * Returns aggregated data from the response as an associative array with queries as keys and results as values
     * @param int $returnType defines which datatype the returned documents will be in. Default is DOC_TYPE_ARRAY, other possible values are DOC_TYPE_SIMPLEXML and DOC_TYPE_STDCLASS
     * @return array
     */
    public function getAggregate($returnType = DOC_TYPE_SIMPLEXML)
    {
        return parent::getRawAggregate($returnType);
    }

    /**
     * Returns the number of documents returned
     * @return int
     */
    public function getFound()
    {
        return $this->getParam('found');
    }

    /**
     * Returns the total number of hits - i.e. the number of documents in a storage that match the request
     * @return int
     */
    public function getHits()
    {
        return $this->getParam('hits');
    }

    /**
     * Returns the position of the first document that was returned
     * @return int
     * @see CPS_SearchRequest::setOffset(), CPS_SearchRequest::setDocs()
     */
    public function getFrom()
    {
        return $this->getParam('from');
    }

    /**
     * Returns the position of the last document that was returned
     * @see CPS_SearchRequest::setOffset(), CPS_SearchRequest::setDocs()
     * @return int
     */
    public function getTo()
    {
        return $this->getParam('to');
    }

    public function getCursor($type = DOC_TYPE_SIMPLEXML)
    {
        return new CPS_Cursor($this->_connection, $this->getParam('cursor_id'), $this->getParam('cursor_data'), $this->_documents, $type);
    }
}

class CPS_Cursor implements Iterator
{
    public function __construct($connection, $cursor_id, $cursor_data, $data, $type = DOC_TYPE_SIMPLEXML, $batch_size = 10)
    {
        $this->_connection = $connection;
        $this->_cursor_id = $cursor_id;
        $this->_cursor_data = $cursor_data;
        $this->_data = $data;
        $this->_type = $type;
        $this->_batch_size = $batch_size;
    }

    public function current()
    {
        if ($this->type == DOC_TYPE_ARRAY) {
            return CPS_Response::simpleXmlToArray(current($this->_data));
        } else if ($this->type == DOC_TYPE_STDCLASS) {
            return CPS_Response::simpleXmlToStdClass(current($this->_data));
        } else {
            return current($this->_data);
        }
    }

    public function key()
    {
        return key($this->_data);
    }

    public function next()
    {
        if (next($this->_data) === FALSE) {
            // Try to request new data
            $req = new CPS_Request('cursor-next-batch');
            $req->setParam('cursor_id', $this->_cursor_id);
            $req->setParam('cursor_data', $this->_cursor_data);
            $req->setParam('docs', $this->_batch_size);
            $resp = $this->_connection->sendRequest($req);

            $this->_cursor_id = $resp->getParam('cursor_id');
            $this->_cursor_data = $resp->getParam('cursor_data');
            $this->_data = $resp->getRawDocuments(DOC_TYPE_SIMPLEXML);
        }
    }

    public function rewind()
    {
        //TODO: Rewind iterator completely??
        reset($this->_data);
    }

    public function valid()
    {
        return current($this->_data) !== FALSE;
    }

    private $_connection;
    private $_cursor_id;
    private $_cursor_data;
    private $_data;
}

/**#@+
 * @access private
 */

/**
 * The CPS_ModifyRequest class is a wrapper for the Request class
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_ModifyRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_ModifyRequest class.
     * Possible parameter sets:<br />
     * 3 parameters - ($command, $id, $document), where $id is the document ID and $document is its contents<br />
     * 2 parameters - ($command, $array), where $array is an associative array with document IDs as keys and document contents as values<br />
     * @param string $command name of the command
     * @param string|array $arg1 Either the document id or the associative array of ids => docs
     * @param mixed|null $arg2 Either the document contents or NULL
     */
    public function __construct($command, $arg1, $arg2)
    {
        parent::__construct($command);
        if (is_array($arg1)) {
            if (!is_null($arg2)) {
                throw new CPS_Exception(array(array('long_message' => 'Invalid request parameter', 'code' => ERROR_CODE_INVALID_PARAMETER, 'level' => 'REJECTED', 'source' => 'CPS_API')));
            }
            // single argument - an associative array
            $this->_documents = $arg1;
        } elseif (!is_null($arg2)) {
            // two arguments - first is the id, second is the document content
            $this->_documents = array($arg1 => $arg2);
        } else {
            throw new CPS_Exception(array(array('long_message' => 'Invalid request parameter', 'code' => ERROR_CODE_INVALID_PARAMETER, 'level' => 'REJECTED', 'source' => 'CPS_API')));
        }
    }
}

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
            $listString .= CPS_Term($value, $key);
        }
        $this->setParam('list', $listString);
    }
}

/**#@-*/

/**
 * The CPS_InsertRequest class is a wrapper for the Response class for the insert command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_InsertRequest extends CPS_ModifyRequest
{
    /**
     * Constructs an instance of the CPS_InsertRequest class.
     * Possible parameter sets:<br />
     * 2 parameters - ($id, $document), where $id is the document ID and $document are its contents<br />
     * 1 parameter - ($array), where $array is an associative array with document IDs as keys and document contents as values<br />
     * @param string|array $arg1 Either the document id or the associative array of ids => docs
     * @param mixed|null $arg2 Either the document contents or NULL (can also be omitted)
     */
    public function __construct($arg1, $arg2 = NULL)
    {
        parent::__construct('insert', $arg1, $arg2);
    }
}

/**
 * The CPS_UpdateRequest class is a wrapper for the Response class for the update command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_UpdateRequest extends CPS_ModifyRequest
{
    /**
     * Constructs an instance of the CPS_UpdateRequest class.
     * Possible parameter sets:<br />
     * 2 parameters - ($id, $document), where $id is the document ID and $document are its contents<br />
     * 1 parameter - ($array), where $array is an associative array with document IDs as keys and document contents as values<br />
     * @param string|array $arg1 Either the document id or the associative array of ids => docs
     * @param mixed|null $arg2 Either the document contents or NULL (can also be omitted)
     */
    public function __construct($arg1, $arg2 = NULL)
    {
        parent::__construct('update', $arg1, $arg2);
    }
}

/**
 * The CPS_ReplaceRequest class is a wrapper for the Response class for the replace command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_ReplaceRequest extends CPS_ModifyRequest
{
    /**
     * Constructs an instance of the CPS_ReplaceRequest class.
     * Possible parameter sets:<br />
     * 2 parameters - ($id, $document), where $id is the document ID and $document are its contents<br />
     * 1 parameter - ($array), where $array is an associative array with document IDs as keys and document contents as values<br />
     * @param string|array $arg1 Either the document id or the associative array of ids => docs
     * @param mixed|null $arg2 Either the document contents or NULL (can also be omitted)
     */
    public function __construct($arg1, $arg2 = NULL)
    {
        parent::__construct('replace', $arg1, $arg2);
    }
}

/**
 * The CPS_PartialReplaceRequest class is a wrapper for the Response class for the partial-replace command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_PartialReplaceRequest extends CPS_ModifyRequest
{
    /**
     * Constructs an instance of the CPS_PartialReplaceRequest class.
     * Possible parameter sets:<br />
     * 2 parameters - ($id, $document), where $id is the document ID and $document are its partial contents with only those fields set that You want changed.<br />
     * 1 parameter - ($array), where $array is an associative array with document IDs as keys and replaceable document contents as values<br />
     * @param string|array $arg1 Either the document id or the associative array of ids => docs
     * @param mixed|null $arg2 Either the replaceable document contents or NULL (can also be omitted)
     */
    public function __construct($arg1, $arg2 = NULL)
    {
        parent::__construct('partial-replace', $arg1, $arg2);
    }
}

/**
 * The CPS_PRX_Operation class represents a particular operation for the partial-xreplace command.
 * An operation consists of the xpath that identifies which nodes the operation should be performed on,
 * the type of operation and new content that should be used in the operation.
 * @package CPS
 * @see CPS_PartialXRequest
 */
class CPS_PRX_Operation
{
    /**
     * Constructs an instance of the CPS_PRX_Operation class.
     * @param string $xpath an XPath 1.0 expression denoting which nodes the operation should be performed on
     * @param string $operation the type of the operation. E.g. "append_children" or "remove"
     * @param mixed $document content of the operation - required for anything except the remove operation. Can be specified in any format that general document content can be specified in (array, SimpleXML, XML string, etc.)
     */
    public function __construct($xpath, $operation, $document = NULL)
    {
        $this->_xpath = $xpath;
        $this->_operation = $operation;
        $this->_document = $document;
    }

    /**
     * Renders the resulting XML
     * @ignore
     */
    public function renderXML(&$req)
    {
        $res = '<cps:xpath_match>';
        $res .= htmlspecialchars($this->_xpath, ENT_NOQUOTES);
        $res .= '</cps:xpath_match>';
        $res .= '<cps:' . htmlspecialchars($this->_operation) . '>';
        if (is_string($this->_document)) {
            $res .= $this->_document;
        } else if (!is_null($this->_document)) {
            $doc = new DOMDocument('1.0', 'utf-8');
            $root = $doc->createElement('root');
            $doc->appendChild($root);
            $req->_loadIntoDom($doc, $root, $this->_document);
            foreach ($root->childNodes as $node) {
                $res .= $doc->saveXML($node);
            }
        }
        $res .= '</cps:' . htmlspecialchars($this->_operation) . '>';
        return $res;
    }

    private $_xpath;
    private $_operation;
    private $_document;
}

/**
 * The CPS_PRX_Changeset class represents a changeset for the partial-xreplace command.
 * A changeset is one or more document IDs and one or more operations to be performed on documents with these IDs
 * @package CPS
 * @see CPS_PartialXRequest
 */
class CPS_PRX_Changeset
{
    /**
     * Constructs an instance of the CPS_PRX_Changeset class.
     * @param string|array $ids Either a document id or an array of ids
     * @param CPS_PRX_Operation|array $operations Either a CPS_PRX_Operation instance or an array of them
     * @see CPS_PRX_Operation
     */
    public function __construct($ids, $operations)
    {
        if (!is_array($ids)) {
            $this->_ids = array($ids);
        } else {
            $this->_ids = $ids;
        }
        if (!is_array($operations)) {
            $this->_operations = array($operations);
        } else {
            $this->_operations = $operations;
        }
    }

    /**
     * Renders the resulting XML
     * @ignore
     */
    public function renderXML($docRootXpath, $docIdXpath, &$req)
    {
        $res = '';
        $prefix = '';
        $postfix = '';
        foreach ($docIdXpath as $part) {
            $prefix .= '<' . htmlspecialchars($part) . '>';
            $postfix = '</' . htmlspecialchars($part) . '>' . $postfix;
        }
        foreach ($this->_ids as $id) {
            $res .= $prefix;
            $res .= htmlspecialchars($id);
            $res .= $postfix;
        }
        $isolate_changes = (count($this->_operations) > 1);
        foreach ($this->_operations as $op) {
            if ($isolate_changes) {
                $res .= '<cps:change>';
            }
            $res .= $op->renderXML($req);
            if ($isolate_changes) {
                $res .= '</cps:change>';
            }
        }
        return $res;
    }

    private $_ids;
    private $_operations;
}

/**
 * The CPS_PartialXRequest class is a wrapper for the Response class for the partial-xreplace command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_PartialXRequest extends CPS_ModifyRequest
{
    /**
     * Constructs an instance of the CPS_PartialXRequest class.
     * Possible parameter sets:<br />
     * 2 parameters - ($id, $operations), where $id is the document ID (or an array of ids) and $operations is an instance of the CPS_PRX_Operation class or an array of such instances.<br />
     * 1 parameter - ($array), where $array is an array of CPS_PRX_Changeset objects<br />
     * @param mixed|array $arg1 Either doc ID(s) or an array of changesets
     * @param mixed|null $arg2 a CPS_PRX_Operation instance or an array of them for the 2-parameter syntax
     * @see CPS_PRX_Operation
     * @see CPS_PRX_Changeset
     */
    public function __construct($arg1, $arg2 = NULL)
    {
        parent::__construct('partial-xreplace', array(), NULL);
        if (is_null($arg2)) {
            // 1 argument syntax - changesets
            $valid_arg1 = false;
            if ($arg1 instanceof CPS_PRX_Changeset) {
                $valid_arg1 = true;
                $arg1 = array($arg1);
            } else if (is_array($arg1)) {
                $valid_arg1 = true;
                foreach ($arg1 as $val) {
                    if (!($val instanceof CPS_PRX_Changeset)) {
                        $valid_arg1 = false;
                        break;
                    }
                }
            }
            if (!$valid_arg1) {
                throw new CPS_Exception(array(array('long_message' => 'Invalid request parameter - expecting CPS_PRX_Changeset or an array of them', 'code' => ERROR_CODE_INVALID_PARAMETER, 'level' => 'REJECTED', 'source' => 'CPS_API')));
            }
            $this->_changesets = $arg1;
        } else {
            $this->_changesets = array(new CPS_PRX_Changeset($arg1, $arg2));
        }
    }
}

/**
 * The CPS_DeleteRequest class is a wrapper for the Response class for the delete command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_DeleteRequest extends CPS_ModifyRequest
{
    /**
     * Constructs an instance of the CPS_DeleteRequest class.
     * @param string|array $arg1 Either the document id as string or an array of document IDs to be deleted
     */
    public function __construct($arg1)
    {
        $nextArg = array();
        if (!is_array($arg1)) {
            $nextArg = array((string)$arg1 => NULL);
        } else {
            foreach ($arg1 as $value) {
                $nextArg[$value] = NULL;
            }
        }
        parent::__construct('delete', $nextArg, NULL);
    }
}

// Modify response
/**
 * The CPS_ModifyResponse class is a wrapper for the Response class for insert, update, delete, replace and partial-replace commands
 * @package CPS
 * @see CPS_InsertRequest, CPS_UpdateRequest, CPS_DeleteRequest, CPS_ReplaceRequest, CPS_PartialReplaceRequest
 */
class CPS_ModifyResponse extends CPS_Response
{
    /**
     * Returns an array of IDs of documents that have been successfully modified
     * @return array
     */
    public function getModifiedIds()
    {
        return array_keys(parent::getRawDocuments(NULL));
    }
}

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

/**
 * The CPS_AlternativesResponse class is a wrapper for the Response class for the alternatives command
 * @package CPS
 * @see CPS_AlternativesRequest
 */
class CPS_AlternativesResponse extends CPS_Response
{
    /**
     * Gets the spelling alternatives to the specified query terms
     *
     * Returns an associative array, where keys are query terms and values are associative arrays
     * with alternative spellings as keys and arrays of the metrics of these spellings as values
     * @return array
     */
    public function getWords()
    {
        return parent::getRawWords();
    }

    /**
     * Gets the occurence count of a particular word from the original query
     *
     * @param string $word
     * @return int
     */
    public function getWordCount($word)
    {
        if (is_null($this->_localWords)) {
            $this->_localWords = $this->getRawWordCounts();
        }
        return isset($this->_localWords[$word]) ? $this->_localWords[$word] : 0;
    }

    private $_localWords;
}

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

/**
 * The CPS_ListWordsResponse class is a wrapper for the Response class for the list-words command
 * @package CPS
 * @see CPS_ListWordsRequest
 */
class CPS_ListWordsResponse extends CPS_Response
{
    /**
     * Returns words matching the given wildcard
     *
     * Returns an associative array, where keys are given wildcards and values are associative arrays with matching words as keys and
     * their counts as values
     * @return array
     */
    public function getWords()
    {
        return parent::getRawWords();
    }
}

/**
 * The CPS_StatusRequest class is a wrapper for the Response class for the status command
 * @package CPS
 * @see CPS_StatusResponse
 */
class CPS_StatusRequest extends CPS_Request
{
    /**
     * Constructs an instance of CPS_StatusRequest
     */
    public function __construct()
    {
        parent::__construct('status');
    }
}

/**
 * The CPS_StatusResponse class is a wrapper for the Response class for the status command
 * @package CPS
 * @see CPS_StatusRequest
 */
class CPS_StatusResponse extends CPS_Response
{
    /**
     * Returns an associative array, which contains the status information
     * @param int $type defines which datatype the returned documents will be in. Default is DOC_TYPE_ARRAY, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
     * @return array
     */
    public function getStatus($type = DOC_TYPE_ARRAY)
    {
        return parent::getContentArray($type);
    }
}

/**
 * The CPS_RetrieveRequest class is a wrapper for the Request class
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_RetrieveRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_RetrieveRequest class.
     * @param string|array $id Either the document id as string or an array of document IDs to be retrieved
     */
    public function __construct($id)
    {
        parent::__construct('retrieve');
        if (!is_array($id)) {
            $this->_documents = array((string)$id => NULL);
        } else {
            $this->_documents = array();
            foreach ($id as $value) {
                $this->_documents[$value] = NULL;
            }
        }
    }
}

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
            $listString .= CPS_Term($value, $key);
        }
        $this->setParam('list', $listString);
    }
}

/**
 * The CPS_ListLastRequest class is a wrapper for the Request class for list-last requests
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_ListLastRequest extends CPS_ListLastRetrieveFirstRequest
{
    /**
     * Constructs an instance of the CPS_ListLast class.
     * @param array $list an associative array with tag xpaths as keys and listing options (yes, no, snippet or highlight) as values
     * @param int $offset offset
     * @param int $docs max number of docs to return
     */
    public function __construct($list, $offset = '', $docs = '')
    {
        parent::__construct('list-last', $offset, $docs, $list);
    }
}

/**
 * The CPS_ListFirstRequest class is a wrapper for the Request class for list-first requests
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_ListFirstRequest extends CPS_ListLastRetrieveFirstRequest
{
    /**
     * Constructs an instance of the CPS_ListFirst class.
     * @param array $list an associative array with tag xpaths as keys and listing options (yes, no, snippet or highlight) as values
     * @param int $offset offset
     * @param int $docs max number of docs to return
     */
    public function __construct($list, $offset = '', $docs = '')
    {
        parent::__construct('list-first', $offset, $docs, $list);
    }
}

/**
 * The CPS_RetrieveLastRequest class is a wrapper for the Request class for retrieve-last requests
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_RetrieveLastRequest extends CPS_ListLastRetrieveFirstRequest
{
    /**
     * Constructs an instance of the CPS_RetrieveLast class.
     * @param int $offset offset
     * @param int $docs max number of docs to return
     */
    public function __construct($offset = '', $docs = '')
    {
        parent::__construct('retrieve-last', $offset, $docs);
    }
}

/**
 * The CPS_RetrieveFirstRequest class is a wrapper for the Request class for retrieve-first requests
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_RetrieveFirstRequest extends CPS_ListLastRetrieveFirstRequest
{
    /**
     * Constructs an instance of the CPS_RetrieveFirst class.
     * @param int $offset offset
     * @param int $docs max number of docs to return
     */
    public function __construct($offset = '', $docs = '')
    {
        parent::__construct('retrieve-first', $offset, $docs);
    }
}

/**
 * The CPS_LookupResponse class is a wrapper for the Response class for replies to retrieve, list-last, list-first, retrieve-last, and retrieve-first commands
 * @package CPS
 * @see CPS_RetrieveRequest, CPS_ListLastRequest, CPS_ListFirstRequest, CPS_RetrieveLastRequest, CPS_RetrieveFirstRequest
 */
class CPS_LookupResponse extends CPS_Response
{
    /**
     * Returns the documents from the response as an associative array, where keys are document IDs and values area document contents
     * @param int $type defines which datatype the returned documents will be in. Default is DOC_TYPE_SIMPLEXML, other possible values are DOC_TYPE_ARRAY and DOC_TYPE_STDCLASS
     * @return array
     */
    public function getDocuments($type = DOC_TYPE_SIMPLEXML)
    {
        return parent::getRawDocuments($type);
    }

    /**
     * Returns the number of documents returned
     * @return int
     */
    public function getFound()
    {
        return $this->getParam('found');
    }

    /**
     * Returns the position of the first document that was returned
     * @return int
     * @see CPS_SearchRequest::setOffset(), CPS_SearchRequest::setDocs()
     */
    public function getFrom()
    {
        return $this->getParam('from');
    }

    /**
     * Returns the position of the last document that was returned
     * @see CPS_SearchRequest::setOffset(), CPS_SearchRequest::setDocs()
     * @return int
     */
    public function getTo()
    {
        return $this->getParam('to');
    }
}

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

/**
 * The CPS_SearchDeleteResponse class is a wrapper for the Response class
 * @package CPS
 * @see CPS_SearchDeleteRequest
 */
class CPS_SearchDeleteResponse extends CPS_Response
{
    /**
     * Returns the total number of hits - i.e. the number of documents erased
     * @return int
     */
    public function getHits()
    {
        return $this->getParam('hits');
    }
}

/**
 * The CPS_ListPathsRequest class is a wrapper for the Response class for the list-paths command
 * @package CPS
 * @see CPS_ListPathsResponse
 */
class CPS_ListPathsRequest extends CPS_Request
{
    /**
     * Constructs an instance of CPS_ListPathsRequest
     */
    public function __construct()
    {
        parent::__construct('list-paths');
    }
}

/**
 * The CPS_ListPathsResponse class is a wrapper for the Response class for the list-paths command
 * @package CPS
 * @see CPS_ListPathsRequest
 */
class CPS_ListPathsResponse extends CPS_Response
{
    /**
     * Returns an array of paths
     * @return array
     */
    public function getPaths()
    {
        $content = parent::getContentArray();
        if (isset($content['paths']['path'])) {
            return $content['paths']['path'];
        } else {
            return array();
        }
    }
}

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

/**
 * The CPS_ListFacetsResponse class is a wrapper for the Response class for the list-facets command
 * @package CPS
 * @see CPS_ListFacetsRequest
 */
class CPS_ListFacetsResponse extends CPS_Response
{

    /**
     * Returns the facets from the response in a form of a multi-dimensional associative array, e.g. array('category' => array('Sports' => '', 'News' => ''));
     * @return array
     */
    public function getFacets()
    {
        return parent::getRawFacets();
    }
}

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

/**
 * The CPS_SimilarTextRequest class is a wrapper for the Response class for the similar command for text
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_SimilarTextRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_SimilarTextRequest class.
     * @param string $text A chunk of text that the found documents have to be similar to
     * @param int $len number of keywords to extract from the source
     * @param int $quota minimum number of keywords matching in the destination
     * @param int $offset number of results to skip before returning the following ones
     * @param int $docs number of documents to retrieve
     * @param string $query an optional query that all found documents have to match against
     */
    public function __construct($text, $len, $quota, $offset = NULL, $docs = NULL, $query = NULL)
    {
        parent::__construct('similar');
        $this->setParam('text', $text);
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

/**
 * The CPS_ShowHistoryRequest class is a wrapper for the Response class for the show-history command
 * @package CPS
 * @see CPS_LookupResponse
 */
class CPS_ShowHistoryRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_ShowHistoryRequest class.
     * @param string $id Document ID
     * @param bool $returnDocs set this to true if you want historical document content returned as well
     */
    public function __construct($id, $returnDocs = false)
    {
        parent::__construct('show-history');
        $this->_documents = array($id => NULL);
        if ($returnDocs)
            $this->setParam('return_doc', 'yes');
    }
}

class CPS_AlertAction
{
    public function __construct($type, $args = NULL)
    {
        $this->_type = $type;
        $this->_args = $args;
    }

    public function toXML()
    {
        $xml = '<action>';
        $xml .= '<type>' . htmlspecialchars($this->_type) . '</type>';
        if (is_array($this->_args)) {
            foreach ($this->_args as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $v) $xml .= '<' . htmlspecialchars($key) . '>' . htmlspecialchars($v) . '</' . htmlspecialchars($key) . '>';
                } else {
                    $xml .= '<' . htmlspecialchars($key) . '>' . htmlspecialchars($val) . '</' . htmlspecialchars($key) . '>';
                }
            }
        }
        $xml .= '</action>';
        return $xml;
    }

    private $_type;
    private $_args;
}

class CPS_Alert
{
    public function __construct($id, $query, $actions)
    {
        $this->_id = $id;
        $this->_query = $query;
        $this->_actions = (is_array($actions)) ? $actions : array($actions);
    }

    public function toXML()
    {
        $xml = '';
        $xml .= '<id>' . htmlspecialchars($this->_id) . '</id>';
        $xml .= '<query>' . $this->_query . '</query>';
        foreach ($this->_actions as $action) $xml .= $action->toXML();
        return $xml;
    }

    private $_id;
    private $_query;
    private $_actions;
}

/**
 * The CPS_AddAlertsRequest class is a wrapper for the Response class for the create-alert command
 * @package CPS
 */
class CPS_CreateAlertRequest extends CPS_Request
{
    public function __construct($alerts)
    {
        parent::__construct('create-alert');
        if (!is_array($alerts)) $alerts = array($alerts);
        $alertsXML = array();
        foreach ($alerts as $alert) {
            $alertsXML[] = $alert->toXML();
        }
        $this->setParam('alert', $alertsXML);
    }
}

/**
 * The CPS_AddAlertsRequest class is a wrapper for the Response class for the update-alert command
 * @package CPS
 */
class CPS_UpdateAlertRequest extends CPS_Request
{
    public function __construct($alerts)
    {
        parent::__construct('update-alert');
        if (!is_array($alerts)) $alerts = array($alerts);
        $alertsXML = array();
        foreach ($alerts as $alert) {
            $alertsXML[] = $alert->toXML();
        }
        $this->setParam('alert', $alertsXML);
    }
}

/**
 * The CPS_RemoveAlertsRequest class is a wrapper for the Request class for the delete-alert command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_DeleteAlertRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_RemoveAlertsRequest class.
     * @param string|array $ids Either the alert id as string or an array of alert IDs to be deleted
     */
    public function __construct($ids)
    {
        parent::__construct('delete-alert');
        $this->setParam('id', $ids);
    }
}

/**
 * The CPS_ListAlertsRequest class is a wrapper for the Request class for the list-alerts command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_ListAlertsRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_ListAlertsRequest class.
     */
    public function __construct($offset = 0, $count = 10)
    {
        parent::__construct('list-alerts');
        $this->setParam('offset', $offset);
        $this->setParam('count', $count);
    }
}

/**
 * The CPS_RunAlertsRequest class is a wrapper for the Request class for the run-alerts command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_RunAlertsRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_RunAlertsRequest class.
     * @param string|array $ids Either the document id as string or an array of document IDs to be examined again
     */
    public function __construct($ids, $alert_ids = array())
    {
        parent::__construct('run-alerts');
        $this->setParam('id', $ids);
        if (count($alert_ids)) $this->setParam('alert_id', $alert_ids);
    }
}

/**
 * The CPS_ClearAlertsRequest class is a wrapper for the Request class for the clear-alerts command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_ClearAlertsRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_ClearAlertsRequest class.
     */
    public function __construct()
    {
        parent::__construct('clear-alerts');
    }
}

/**
 * The CPS_EnableAlertsRequest class is a wrapper for the Request class for the enable-alerts command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_EnableAlertsRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_EnableAlertsRequest class.
     */
    public function __construct()
    {
        parent::__construct('enable-alerts');
    }
}

/**
 * The CPS_DisableAlertsRequest class is a wrapper for the Request class for the disable-alerts command
 * @package CPS
 * @see CPS_ModifyResponse
 */
class CPS_DisableAlertsRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_DisableAlertsRequest class.
     */
    public function __construct()
    {
        parent::__construct('disable-alerts');
    }
}

class CPS_ListAlertsResponse extends CPS_Response
{
    /**
     * Returns the alerts from the response in a form of a multi-dimensional associative array, in format:
     * array('alert-id' => array('id' => ..,
     *              'query' => ..,
     *              'action' = array('type' => .., ..)));
     * @return array
     */
    public function getAlerts()
    {
        if (count($this->_alerts) > 0) return $this->_alerts;
        if (!isset($this->_contentArray['alerts']) || !isset($this->_contentArray['alerts']['alert'])) return array();
        if (isset($this->_contentArray['alerts']['alert']['id'])) {
            $this->_alerts[$this->_contentArray['alerts']['alert']['id']] = $this->_contentArray['alerts']['alert'];
        } else {
            foreach ($this->_contentArray['alerts']['alert'] as $alert) {
                $this->_alerts[$alert['id']] = $alert;
            }
        }
        return $this->_alerts;
    }

    private $_alerts = array();
}

class CPS_BeginTransactionRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_BeginTransactionRequest class.
     */
    public function __construct()
    {
        parent::__construct("begin-transaction");
    }
}

class CPS_CommitTransactionRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_CommitTransactionRequest class.
     */
    public function __construct()
    {
        parent::__construct("commit-transaction");
    }
}

class CPS_RollbackTransactionRequest extends CPS_Request
{
    /**
     * Constructs an instance of the CPS_RollbackTransactionRequest class.
     */
    public function __construct()
    {
        parent::__construct("rollback-transaction");
    }
}

/**
 * CPS_EmptyResponse is used in cases where no reply from the server is expected
 * @package CPS
 */
class CPS_EmptyResponse extends CPS_Response
{
    /**
     * Empty response - used in cases where no reply from the server is expected
     * @param CPS_Connection &$connection CPS connection object
     * @param CPS_Request &$request The original request that the response is to
     */
    public function __construct(CPS_Connection &$connection, CPS_Request &$request)
    {
        $this->_errors = array();
        $errors = array();
        $this->_documents = array();
        $this->_facets = array();
        $this->_textParams = array();
        $this->_words = array();
        $this->_contentArray = array();
        $this->_paths = array();
        $this->_textParams = array();
    }
}