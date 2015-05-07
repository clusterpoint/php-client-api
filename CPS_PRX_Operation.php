<?php
//<namespace
namespace cps;

use \DOMDocument AS DOMDocument;
//namespace>

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