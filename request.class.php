<?php
/**
 * General request class for CPS API
 * @package CPS
 */


/**
 * array - associative or not?
 * @param array $array array to check
 */

function is_assoc($array) {
    $assoc = true;
    end($array);
    if (key($array) === count($array) - 1) {
        $assoc = false;
    }
    return $assoc;
}

/**
 * The Request class contains a single request to CPS storage
 * @package CPS
 */
class CPS_Request {
    /**
     * Constructs an instance of the Request class.
     * @param string $command Specifies the command field for the request
     * @param string $requestId The request ID. Can be useful for identifying a particular request in a log file when debugging
     */
    public function __construct($command, $requestId = '') {
        $this->_command = $command;
        $this->_requestId = $requestId;
        $this->_label = '';
        $this->_requestType = NULL;
        $this->_textParams = array();
        $this->_rawParams = array();
        $this->_documents = array();
        $this->_extraXmlParam = NULL;
        $this->_changesets = NULL;


        $this->_textParamNames = array(
            'added_external_id',
            'added_id',
            'aggregate',
            'alert_id',
            'case_sensitive',
            'count',
            'cr',
            'create_cursor',
            'cursor_id',
            'cursor_data',
            'deleted_external_id',
            'deleted_id',
            'description',
            'docs',
            'exact-match',
            'facet',
            'facet_size',
            'fail_if_exists',
            'file',
            'finalize',
            'for',
            'force',
            'force_precise_results',
            'force_segment',
            'from',
            'full',
            'group',
            'group_size',
            'h',
            'id',
            'idif',
            'iterator_id',
            'len',
            'message',
            'offset',
            'optimize_to',
            'path',
            'persistent',
            'position',
            'quota',
            'rate2_ordering',
            'rate_from',
            'rate_to',
            'relevance',
            'return_doc',
            'return_internal',
            'sequence_check',
            'sql',
            'stem-lang',
            'step_size',
            'text',
            'transaction_id',
            'type'
        );
        $this->_rawParamNames = array(
            'alert',
            'query',
            'list',
            'ordering'
        );
    }

	public function getTextParamNames(){
		return array_merge($this->_rawParamNames, $this->_textParamNames);
	}

    /**
     * Returns the contents of the request as an XML string
     * @ignore
     * @param string &$docRootXpath document root xpath
     * @param string &$docIdXpath document ID xpath
     * @param array &$envelopeParams an associative array of CPS envelope parameters
     * @return string
     */
    public function getRequestXml(&$docRootXpath, &$docIdXpath, &$envelopeParams, $resetDocIds) {
        unset($this->_requestDom);
        $this->_requestDom = new DomDocument('1.0', 'utf-8');
        $root = $this->_requestDom->createElementNS('www.clusterpoint.com', 'cps:request');
        $this->_requestDom->appendChild($root);

        // envelope parameters first
        foreach ($envelopeParams as $name => $value) {
					if($value) {
            $root->appendChild($this->_requestDom->createElement('cps:' . $name , $this->getValidXmlValue($value)));
        	}
				}
        $contentTag = $root->appendChild($this->_requestDom->createElement('cps:content'));

        // content tag text parameters
        foreach ($this->_textParams as $name => $values) {
            if (!is_array($values)) {
                $values[0] = $values;
            }
            foreach ($values as $value) {
                $contentTag->appendChild($this->_requestDom->createElement($name , $this->getValidXmlValue($value)));
            }
        }

        // special fields: query, list, ordering
        foreach ($this->_rawParams as $name => $values) {
            if (!is_array($values)) {
                $values[0] = $values;
            }
            foreach ($values as $value) {
                $fragment = $this->_requestDom->createDocumentFragment();
                $fragment->appendXML('<' . $name . ' xmlns:cps="www.clusterpoint.com">' . $value . '</' . $name . '>');
                $contentTag->appendChild($fragment);
            }
        }

        if (!is_null($this->_changesets)) {
            $xmlContent = '';
            if (count($this->_changesets) > 1) {
                foreach ($this->_changesets as $cs) {
                    $xmlContent .= '<cps:changeset>';
                    $xmlContent .= $cs->renderXML($docRootXpath, $docIdXpath, $this);
                    $xmlContent .= '</cps:changeset>';
                }
            } else if (count($this->_changesets) == 1) {
                $xmlContent .= $this->_changesets[0]->renderXML($docRootXpath, $docIdXpath, $this);
            }
            if (strlen($xmlContent) > 0) {
                $tmpDoc = new DOMDocument();
                $tmpDoc->loadXML('<fakedoc xmlns:cps="www.clusterpoint.com">' . $xmlContent . '</fakedoc>');
                foreach ($tmpDoc->documentElement->childNodes as $child) {
                    $domNode2 = $this->_requestDom->importNode($child, true);
                    $contentTag->appendChild($domNode2);
                }
            }
        }

        // extra XML content
        if (!is_null($this->_extraXmlParam)) {
            $tmpDoc = new DOMDocument();
            $tmpDoc->loadXML('<fakedoc xmlns:cps="www.clusterpoint.com">' . $this->_extraXmlParam . '</fakedoc>');
            foreach ($tmpDoc->documentElement->childNodes as $child) {
                $domNode2 = $this->_requestDom->importNode($child, true);
                $contentTag->appendChild($domNode2);
            }
        }

        // documents, document IDs
        foreach ($this->_documents as $id => $doc) {
            $subdoc = new DOMDocument();
            $subroot = NULL;
            $loadForEach = false;
            if (is_string($doc)) {
                $res = @$subdoc->loadXML($doc);
                if ($res === FALSE) {
                    throw new CPS_Exception(array(array('long_message' => 'Invalid request parameter - unable to parse XML', 'code' => ERROR_CODE_INVALID_PARAMETER, 'level' => 'REJECTED', 'source' => 'CPS_API')));
                }
            } else if (is_array($doc)) {
                // associative array
                $loadForEach = true;
            } else if (is_object($doc)) {
                if ($doc instanceof SimpleXMLElement) {
                    // SimpleXML
                    $loadForEach = true;
                } else if ($doc instanceof DOMNode) {
                    // DOM
                    $subdoc = $doc;
                } else if ($doc instanceof stdClass) {
                    // stdClass
                    $loadForEach = true;
                } else {
                    throw new CPS_Exception(array(array('long_message' => 'Invalid request parameter', 'code' => ERROR_CODE_INVALID_PARAMETER, 'level' => 'REJECTED', 'source' => 'CPS_API')));
                }
            } else if (is_null($doc)) {
                // just document IDs - no doc integration required
            } else {
                throw new CPS_Exception(array(array('long_message' => 'Invalid request parameter', 'code' => ERROR_CODE_INVALID_PARAMETER, 'level' => 'REJECTED', 'source' => 'CPS_API')));
            }

            if (!($subroot = $subdoc->documentElement)) {
                $subroot = $subdoc->createElement($docRootXpath);
                $subdoc->appendChild($subroot);
            }

            if ($loadForEach) {
                CPS_Request::_loadIntoDom($subdoc, $subroot, $doc);
            }

            if ($resetDocIds) {
                // integrating ID into the document
                CPS_Request::_setDocId($subdoc, $subroot, $id, $docIdXpath);
            }

            //importing subdoc
            $reqNode = $this->_requestDom->importNode($subdoc->documentElement, true);
            $contentTag->appendChild($reqNode);
        }
        $xml = $this->_requestDom->saveXML();
        unset($this->_requestDom);
        return $xml;
    }

    /**
     * Returns the string with control characters stripped
     * @param string &$src original string
     * @return string
     */
    public static function getValidXmlValue(&$src) {
        return strtr($src,
            "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0b\x0c\x0e\x0f\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f",
            '                             ');
    }

    /**
     * returns the command name
     * @return string
     */
    function getCommand() {
        return $this->_command;
    }

    /**
     * Sets shape definition for geospatial search
     * @param String|array $value A single shape definition as string or array of string that define multiple shapes
     * @param bool $replace Should previous set values be replaced
     */
    public function setShape($value, $replace = true)
    {
        $name = 'shapes';
        if (!(is_array($value) || is_string($value))) {
            throw new CPS_Exception(array(array('long_message' => 'Invalid request parameter', 'code' => ERROR_CODE_INVALID_PARAMETER, 'level' => 'REJECTED', 'source' => 'CPS_API')));
        }
        if ($replace) {
            $this->_setRawParam($name, $value);
        } else {
            $exParam = $this->_getRawParam($name);
            if (!is_array($exParam)) {
                $exParam = array($exParam);
            }
            if (is_array($value)) {
                foreach($value as $val){
                    $exParam[] = $val;
                }
            } else if (is_string($value)) {
                $exParam[] = $value;
            }
            $this->_setRawParam($name, $exParam);
        }
    }

    /**
     * sets a request parameter
     * @param string $name name of the parameter
     * @param mixed $value value to set. Could be a single string or an array of strings
     */
    public function setParam($name, $value) {
        if (in_array($name, $this->_textParamNames)) {
            $this->_setTextParam($name, $value);
        } else if (in_array($name, $this->_rawParamNames)) {
            $this->_setRawParam($name, $value);
        } else {
            throw new CPS_Exception(array(array('long_message' => 'Invalid request parameter', 'code' => ERROR_CODE_INVALID_PARAMETER, 'level' => 'REJECTED', 'source' => 'CPS_API')));
        }
    }

    /**
     * sets extra XML to send in the content tag of the request. Only use this if you know what you are doing.
     * @param string &$value extra XML as a string
     */
    public function setExtraXmlParam($value) {
        $this->_extraXmlParam = $value;
    }

    /**
     * gets the request id
     * @return string
     */
    public function getRequestId() {
        return $this->_requestId;
    }

    /**
     * sets the request id
     * @param string $requestId
     */
    public function setRequestId($requestId) {
        $this->_requestId = $requestId;
    }

    /**
     * sets the label for cluster nodeset
     * @param string $label
     */
    public function setClusterLabel($label) {
        $this->_label = $label;
    }

    /**
     * gets the label for cluster nodeset if set
     * @return string
     */
    public function getClusterLabel() {
        return $this->_label;
    }

    /**
     * gets the request type
     * @return string
     */
    public function getRequestType() {
        return $this->_requestType;
    }

    /**
     * sets the request type
     * @param string $requestType
     */
    public function setRequestType($requestType) {
        $this->_requestType = $requestType;
    }

    /*
    //**
    * gets a request parameter
    * @param string &$name name of the parameter to get
    //*
    public function getParam(&$name) {
        if (in_array($name, self::$_textParamNames)) {
            return $this->_getTextParam($name);
        } else if (in_array($name, self::$_rawParamNames)) {
            return $this->_getRawParam($name);
        } else {
            // TODO: throw exception
        }
    }*/

    /**#@+
     * @access private
     */

    /**
     * sets a text-only request parameter
     * @param string &$name name of the parameter
     * @param mixed &$value value to set. Could be a single string or an array of strings
     */
    private function _setTextParam(&$name, &$value) {
        if (!is_array($value))
            $value = array($value);
        $this->_textParams[$name] = $value;
    }
    /**
     * sets a raw request parameter
     * @param string &$name name of the parameter
     * @param mixed &$value value to set. Could be a single string or an array of strings
     */
    private function _setRawParam(&$name, &$value) {
        if (!is_array($value))
            $value = array($value);
        $this->_rawParams[$name] = $value;
    }

  /**
   * gets a raw request parameter
   * @param string $name name of the parameter
   */
  private function _getRawParam($name)
  {
    if (!isset($this->_rawParams[$name]))
      return array();
    return $this->_rawParams[$name];
  }

    /**
     * loads the document into DOM recursively from an array or object/SimpleXMLelement. It's supposed to be private, but
     * due to PHP not supporting friend classes it's made public
     * @param DOMDocument &$subdoc into which the nodes are being loaded
     * @param DOMNode &$destNode destination node
     * @param string|array|object|SimpleXMLElement &$srcNode source node
     */
    public static function _loadIntoDom(&$subdoc, &$destNode, &$srcNode) {
        foreach ($srcNode as $key => $value) {
            $newDestNode = $subdoc->createElement($key);
            $destNode->appendChild($newDestNode);
            if (is_array($value)) {
                // array - associative or not?
                if (is_assoc($value)) {
                    self::_loadIntoDom($subdoc, $newDestNode, $value);
                } else {
                    $c = 0;
                    foreach ($value as &$v) {
                        if ($c) {
                            $newDestNode = $subdoc->createElement($key);
                            $destNode->appendChild($newDestNode);
                        }
                        if (is_string($v) || is_float($v) || is_int($v) || (($v instanceof SimpleXMLElement) && (count($v->children()) == 0))) {
                        	if (!CPS_Request::isValidUTF8((string) $v))
                        		throw new CPS_Exception(array(array('long_message' => 'Invalid UTF-8 encoding in key = \''.$key.'\'', 'code' => ERROR_CODE_INVALID_UTF8, 'level' => 'ERROR', 'source' => 'CPS_API')));
                            $textNode = $subdoc->createTextNode((string) self::getValidXmlValue($v));
                            $newDestNode->appendChild($textNode);
                        } else {
                            self::_loadIntoDom($subdoc, $newDestNode, $v);
                        }
                        /*						$textNode = $subdoc->createTextNode($v);
                                                $newDestNode->appendChild($textNode);*/
                        ++$c;
                    }
                }
            } else if (is_object($value) && ($value instanceof SimpleXMLElement)) {
                $domNode = dom_import_simplexml($value);
                $domNode2 = $subdoc->importNode($domNode, true);
                $destNode->replaceChild($domNode2, $newDestNode);
            } else if (is_object($value)) {
                self::_loadIntoDom($subdoc, $newDestNode, $value);
            } else if (is_string($value) || is_float($value) || is_int($value)) {
            	if (!CPS_Request::isValidUTF8((string) $value))
            		throw new CPS_Exception(array(array('long_message' => 'Invalid UTF-8 encoding in key = \''.$key.'\'', 'code' => ERROR_CODE_INVALID_UTF8, 'level' => 'ERROR', 'source' => 'CPS_API')));
                $textNode = $subdoc->createTextNode((string) self::getValidXmlValue($value));
                $newDestNode->appendChild($textNode);
            }
        }
    }

    /**
     * sets the docid inside the document
     * @param DOMDocument &$subdoc document where the id should be loaded into
     */
    private static function _setDocId(&$subdoc, &$parentNode, $id, &$docIdXpath, $curLevel = 0) {
        if ($parentNode->nodeName == $docIdXpath[$curLevel]) {
            if ($curLevel == count($docIdXpath) - 1) {
                // remove all sub-nodes
                $curChild = $parentNode->firstChild;
                while ($curChild) {
                    $nextChild = $curChild->nextSibling;
                    $parentNode->removeChild($curChild);
                    $curChild = $nextChild;
                }
                // set the ID
                if (!CPS_Request::isValidUTF8((string) $id))
                	throw new CPS_Exception(array(array('long_message' => 'Invalid UTF-8 encoding in document ID', 'code' => ERROR_CODE_INVALID_UTF8, 'level' => 'ERROR', 'source' => 'CPS_API')));
                $textNode = $subdoc->createTextNode($id);
                $parentNode->appendChild($textNode);
            } else {
                // traverse children
                $found = false;
                $curChild = $parentNode->firstChild;
                while ($curChild) {
                    if ($curChild->nodeName == $docIdXpath[$curLevel + 1]) {
                        CPS_Request::_setDocId($subdoc, $curChild, $id, $docIdXpath, $curLevel + 1);
                        $found = true;
                        break;
                    }
                    $curChild = $curChild->nextSibling;
                }
                if (!$found) {
                    $newNode = $subdoc->createElement($docIdXpath[$curLevel + 1]);
                    $parentNode->appendChild($newNode);
                    CPS_Request::_setDocId($subdoc, $newNode, $id, $docIdXpath, $curLevel + 1);
                }
            }
        } else {
            throw new CPS_Exception(array(array('long_message' => 'Document root xpath not matching document ID xpath', 'code' => ERROR_CODE_INVALID_XPATHS, 'level' => 'REJECTED', 'source' => 'CPS_API')));
        }
    }

  private static function isValidUTF8($string)
  {
    $ret = preg_match('%^[\x{09}\x{0A}\x{0D}\x{20}-\x{7E}\x{C280}-\x{C2BF}\x{C380}-\x{C3BF}\x{C480}-\x{C4BF}\x{C580}-\x{C5BF}\x{C680}-\x{C6BF}\x{C780}-\x{C7BF}\x{C880}-\x{C8BF}\x{C980}-\x{C9BF}\x{CA80}-\x{CABF}\x{CB80}-\x{CBBF}\x{CC80}-\x{CCBF}\x{CD80}-\x{CDBF}\x{CE80}-\x{CEBF}\x{CF80}-\x{CFBF}\x{D080}-\x{D0BF}\x{D180}-\x{D1BF}\x{D280}-\x{D2BF}\x{D380}-\x{D3BF}\x{D480}-\x{D4BF}\x{D580}-\x{D5BF}\x{D680}-\x{D6BF}\x{D780}-\x{D7BF}]*$%uxs', $string);
    if (!$ret) {
      if (function_exists("mb_strlen") && function_exists("mb_substr")) {
        $str_len = mb_strlen($string, 'UTF-8');
        $start = 0;
        while ($start < $str_len) {
          if (!preg_match('%^(?:
          [\x09\x0A\x0D\x20-\x7E]              # ASCII
          | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
          | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
          | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
          | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
          | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
          | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
          | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
          )*$%xs', mb_substr($string, $start, 1000, 'UTF-8'))
          )
            return 0;
          $start = $start + 1000;
        }
      }
    }
    return 1;
  }

    private $_textParamNames;
    private $_rawParamNames;
    private $_requestId;
    private $_requestDom;
    private $_label;
    private $_command;
    private $_textParams;
    private $_rawParams;
    private $_extraXmlParam;
    protected $_documents;
    protected $_changesets;
    /**#@-*/
}

/**
 * This class, which is derived from the CPS_Request class, can be used to send pre-formed XML requests to the storage.
 * If you already have the XML request, you can send it to the API by using this class. Note that parameters from the CPS_Connection
 * object such as storage name, username and password will not be used to form the request
 * @package CPS
 */
class CPS_StaticRequest extends CPS_Request {
    /**
     * Constructs an instance of the CPS_StaticRequest class.
     * @param string $xml the full XML string to send
     * @param string $command specifies which command is being sent. This is not mandatory for sending
     * the request per se, but how the response will be parsed will depend on this setting, so if you
     * want full response parsing from the API, you should specify the command here. Note that setting this
     * parameter will not change the XML being sent to the database
     */
    public function __construct($xml, $command='') {
        parent::__construct($command);
        $this->_renderedXml = $xml;
    }
    /**
     * Returns the contents of the request as an XML string
     * @ignore
     */
    public function getRequestXml(&$docRootXpath, &$docIdXpath, &$envelopeParams, $resetDocIds) {
        return $this->_renderedXml;
    }
    private $_renderedXml;
};