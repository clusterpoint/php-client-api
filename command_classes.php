<?php

/**
 * Request and response classes for all commands in the CPS API
 * @package CPS
 */

/**
 * includes
 */

require_once(dirname(__FILE__) . '/CPS.php');

/**
 * Escapes <, > and & characters in the given term for inclusion into XML (like the search query). Also wraps the term in XML tags if xpath is specified.
 * Note that this function doesn't escape the @, $, " and other symbols that are meaningful in a search query. If You want to escape input that comes directly
 * from the user and that isn't supposed to contain any search operators at all, it's probably better to use {@link CPS_QueryTerm}
 * @param string $term the term to be escaped (e.g. a search query term)
 * @param string $xpath an optional xpath, to be specified if the search term is to be searched under a specific xpath
 * @param bool $escape an optional parameter - whether to escape the term's XML
 * @see CPS_QueryTerm
 */
function CPS_Term($term, $xpath = '', $escape = TRUE)
{
  return CPS::Term($term, $xpath, $escape);
}

/**
 * Escapes <, > and & characters, as well as @"{}()=$~+ (search query operators) in the given term for inclusion into the search query.
 * Also wraps the term in XML tags if xpath is specified.
 * @param string $term the term to be escaped (e.g. a search query term)
 * @param string $xpath an optional xpath, to be specified if the search term is to be searched under a specific xpath
 * @param string $allowed_symbols a string containing operator symbols that the user is allowed to use (e.g. ")
 * @see CPS_Term
 */
function CPS_QueryTerm($term, $xpath = '', $allowed_symbols = '')
{
  return CPS::QueryTerm($term, $xpath, $allowed_symbols);
}

/**
 * Converts a given query array to a query string
 * @param array $array the query array
 * @return string
 */
function CPS_QueryArray($array)
{
  return CPS::QueryArray($array);
}

/**
 * Returns an circle definition string with provided center and radius
 * @function
 * @param String $name name of a shape, should be a valid xml name
 * @param array $center array with two elements identifying center of circle
 * @param double|String $radius radius of circle with optional distance type (km/mi), default is km
 * @param String $tagName1 tag name of first coordinate (e.g. latitude), if not passed, then default configuration values will be used
 * @param String $tagName2 tag name of second coordinate (e.g. longitude), if not passed, then default configuration values will be used
 * @param String $coord_type coordinate type, either latlong or plane
 */
function CPS_CircleDefinition($name, $center, $radius, $tagName1 = null, $tagName2 = null, $coord_type = null)
{
  return CPS::CircleDefinition($name, $center, $radius, $tagName1, $tagName2, $coord_type);
}

/**
 * Returns an polygon definition string from provided vertice points
 * @function
 * @param String $name name of a shape, should be a valid xml name
 * @param array $vertices array of vertice coordinates identifying polygon each element should contain array of two elements which correspond to vertice coordinates
 * @param String $tagName1 tag name of first coordinate (e.g. latitude), if not passed, then default configuration values will be used
 * @param String $tagName2 tag name of second coordinate (e.g. longitude), if not passed, then default configuration values will be used
 * @param String $coord_type coordinate type, either latlong or plane
 */
function CPS_PolygonDefinition($name, $vertices, $tagName1 = null, $tagName2 = null, $coord_type = null)
{
  return CPS::PolygonDefinition($name, $vertices, $tagName1, $tagName2, $coord_type);
}


/**
 * Returns an ordering string for sorting by relevance
 * @see CPS_SearchRequest::setOrdering()
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default most relevant documents are returned first
 */

function CPS_RelevanceOrdering($ascdesc = '')
{
  return CPS::RelevanceOrdering($ascdesc);
}

/**
 * Returns an ordering string for sorting by a numeric field
 * @see CPS_SearchRequest::setOrdering()
 * @param string $tag the xpath of the tag by which You wish to perform sorting
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default ascending order is used.
 */

function CPS_NumericOrdering($tag, $ascdesc = 'ascending')
{
  return CPS::NumericOrdering($tag, $ascdesc);
}

/**
 * Returns an ordering string for sorting by a date field
 * @see CPS_SearchRequest::setOrdering()
 * @param string $tag the xpath of the tag by which You wish to perform sorting
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default ascending order is used.
 */

function CPS_DateOrdering($tag, $ascdesc = 'ascending')
{
  return CPS::DateOrdering($tag, $ascdesc);
}

/**
 * Returns an ordering string for sorting by a string field
 * @see CPS_SearchRequest::setOrdering()
 * @param string $tag the xpath of the tag by which You wish to perform sorting
 * @param string $lang specifies the language (collation) to be used for ordering. E.g. "en"
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default ascending order is used.
 */

function CPS_StringOrdering($tag, $lang, $ascdesc = 'ascending')
{
  return CPS::StringOrdering($tag, $lang, $ascdesc);
}

/**#@+
 * @access private
 */
function CPS_GenericDistanceOrdering($type, $array, $ascdesc)
{
  return CPS::GenericDistanceOrdering($type, $array, $ascdesc);
}

/**#@-*/

/**
 * Returns an ordering string for sorting by distance from a latitude/longitude coordinate pair
 * @see CPS_SearchRequest::setOrdering()
 * @param array $array an associative array with tag xpaths as keys and centerpoint coordinates as values. Should contain exactly two elements - latitude first and longitude second.
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default ascending order is used.
 */

function CPS_LatLonDistanceOrdering($array, $ascdesc = 'ascending')
{
  return CPS::LatLonDistanceOrdering($array, $ascdesc);
}

/**
 * Returns an ordering string for sorting by distance from specified coordinates on a geometric plane
 * @see CPS_SearchRequest::setOrdering()
 * @param array $array an associative array with tag xpaths as keys and centerpoint coordinates as values.
 * @param string $ascdesc optional parameter to specify ascending/descending order. By default ascending order is used.
 */
function CPS_PlaneDistanceOrdering($array, $ascdesc = 'ascending')
{
  return CPS::PlaneDistanceOrdering($array, $ascdesc);
}
