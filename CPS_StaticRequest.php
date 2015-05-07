<?php
//<namespace
namespace cps;
//namespace>

/**
 * This class, which is derived from the CPS_Request class, can be used to send pre-formed XML requests to the storage.
 * If you already have the XML request, you can send it to the API by using this class. Note that parameters from the CPS_Connection
 * object such as storage name, username and password will not be used to form the request
 * @package CPS
 */
class CPS_StaticRequest extends CPS_Request
{
  /**
   * Constructs an instance of the CPS_StaticRequest class.
   * @param string $xml the full XML string to send
   * @param string $command specifies which command is being sent. This is not mandatory for sending
   * the request per se, but how the response will be parsed will depend on this setting, so if you
   * want full response parsing from the API, you should specify the command here. Note that setting this
   * parameter will not change the XML being sent to the database
   */
  public function __construct($xml, $command = '')
  {
    parent::__construct($command);
    $this->_renderedXml = $xml;
  }

  /**
   * Returns the contents of the request as an XML string
   * @ignore
   */
  public function getRequestXml(&$docRootXpath, &$docIdXpath, &$envelopeParams, $resetDocIds)
  {
    return $this->_renderedXml;
  }

  private $_renderedXml;
}

;