<?php
/**
* Main file for CPS API
* @package CPS
*/

//define('USE_OLD_LIB_SOCK', true);

/**
* Including all classes
*/

require_once(dirname(__FILE__) . '/exception.class.php');
require_once(dirname(__FILE__) . '/connection.class.php');
require_once(dirname(__FILE__) . '/request.class.php');
require_once(dirname(__FILE__) . '/response.class.php');
require_once(dirname(__FILE__) . '/command_classes.php');

?>