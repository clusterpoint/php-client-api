<?php
//<namespace
namespace cps;
//namespace>

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