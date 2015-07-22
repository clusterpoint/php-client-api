<?php
/**
 * Main file for CPS API
 * @package CPS
 */

/**
 * Including all classes
 */

require_once(dirname(__FILE__) . '/command_classes.php');

require_once(dirname(__FILE__) . '/CPS_PRX_Operation.php');

require_once(dirname(__FILE__) . '/CPS_Request.php');
require_once(dirname(__FILE__) . '/CPS_StatusRequest.php');
require_once(dirname(__FILE__) . '/CPS_ListLastRetrieveFirstRequest.php');
require_once(dirname(__FILE__) . '/CPS_ModifyRequest.php');
require_once(dirname(__FILE__) . '/CPS_InsertRequest.php');
require_once(dirname(__FILE__) . '/CPS_UpdateRequest.php');
require_once(dirname(__FILE__) . '/CPS_ReplaceRequest.php');
require_once(dirname(__FILE__) . '/CPS_PartialReplaceRequest.php');
require_once(dirname(__FILE__) . '/CPS_PRX_Changeset.php');
require_once(dirname(__FILE__) . '/CPS_PartialXRequest.php');
require_once(dirname(__FILE__) . '/CPS_DeleteRequest.php');
require_once(dirname(__FILE__) . '/CPS_SearchRequest.php');
require_once(dirname(__FILE__) . '/CPS_SQLSearchRequest.php');
//require_once(dirname(__FILE__) . '/CPS_SearchDeleteRequest.php'); //v2.3
require_once(dirname(__FILE__) . '/CPS_RetrieveRequest.php');
require_once(dirname(__FILE__) . '/CPS_LookupRequest.php');
require_once(dirname(__FILE__) . '/CPS_ListLastRequest.php');
require_once(dirname(__FILE__) . '/CPS_ListFirstRequest.php');
require_once(dirname(__FILE__) . '/CPS_RetrieveLastRequest.php');
require_once(dirname(__FILE__) . '/CPS_RetrieveFirstRequest.php');
//require_once(dirname(__FILE__) . '/CPS_ListWordsRequest.php'); //v2.3
require_once(dirname(__FILE__) . '/CPS_ListPathsRequest.php');
//require_once(dirname(__FILE__) . '/CPS_ListFacetsRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_AlternativesRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_SimilarDocumentRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_SimilarTextRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_ShowHistoryRequest.php'); //v2.3

//require_once(dirname(__FILE__) . '/CPS_CreateAlertRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_UpdateAlertRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_DeleteAlertRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_ListAlertsRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_RunAlertsRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_ClearAlertsRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_EnableAlertsRequest.php'); //v2.3
//require_once(dirname(__FILE__) . '/CPS_DisableAlertsRequest.php'); //v2.3


require_once(dirname(__FILE__) . '/CPS_BeginTransactionRequest.php');
require_once(dirname(__FILE__) . '/CPS_CommitTransactionRequest.php');
require_once(dirname(__FILE__) . '/CPS_RollbackTransactionRequest.php');

require_once(dirname(__FILE__) . '/CPS_Exception.php');
require_once(dirname(__FILE__) . '/CPS_LoadBalancer.php');

require_once(dirname(__FILE__) . '/CPS_Response.php');
require_once(dirname(__FILE__) . '/CPS_StaticRequest.php');
require_once(dirname(__FILE__) . '/CPS_Connection.php');






?>