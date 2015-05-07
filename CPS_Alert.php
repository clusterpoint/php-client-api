<?php
//<namespace
namespace cps;
//namespace>

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
