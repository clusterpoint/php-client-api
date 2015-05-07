<?php
//<namespace
namespace cps;
//namespace>

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