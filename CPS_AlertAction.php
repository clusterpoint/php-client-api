<?php
//<namespace
namespace cps;
//namespace>

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
