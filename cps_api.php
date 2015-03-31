<?php
/**
* Main file for CPS API
* @package CPS
*/

/**
* Including all classes
*/


require_once(dirname(__FILE__) . '/CPS_Exception.php');
require_once(dirname(__FILE__) . '/CPS_LoadBalancer.php');
require_once(dirname(__FILE__) . '/CPS_Request.php');
require_once(dirname(__FILE__) . '/CPS_Response.php');
require_once(dirname(__FILE__) . '/CPS_StaticRequest.php');
require_once(dirname(__FILE__) . '/CPS_Connection.php');

?>