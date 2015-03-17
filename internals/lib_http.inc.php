<?php
if (defined('USE_OLD_LIB_SOCK'))
	require_once("lib_sock_old.inc.php");
else
	require_once("lib_sock.inc.php");
require_once(dirname(__FILE__) . '/../exception.class.php');

function cps_http_get($url, $headers = "")
{
	$result = "";
	$errno = 0; $error = "";
	$urlx = parse_url($url);
	if (empty($urlx['port'])) $urlx['port'] = 80;
	$fs = sock_init();
	if(sock_open($fs,$urlx['host'],$urlx['port']))
	{
		sock_write($fs,"GET ".$urlx["path"].(!empty($urlx["query"]) ? "?".$urlx["query"] : "")." HTTP/1.0\r\n");
		sock_write($fs,"Host: ".$urlx["host"]."\r\n");
		if (!empty($headers))
			sock_write($fs,$headers);
		sock_write($fs,"\r\n");
		sock_flush($fs);
		$result = "";
		while (!sock_eof($fs) && !sock_error($fs,$error,$errno))
		{
			$buf = sock_read($fs);
			$result .= $buf;
		}
		sock_close($fs);
	}
	else
	{
		sock_error($fs,$error,$errno);
		$result = "[http_get] Error $errno: $error";
		throw new CPS_Exception(array(array('long_message' => "[http_get] Error $errno: $error", 'code' => ERROR_CODE_SOCKET_ERROR, 'level' => 'ERROR', 'source' => 'CPS_API')));
	}
	sock_free($fs);
	return $result;
}

function cps_http_get_proxy($proxy, $url, $headers = "")
{
	$result = "";
	$errno = 0; $error = "";
	$urlx = parse_url($url);
	$proxyx = parse_url($proxy);
	if (empty($proxyx['port'])) $proxyx['port'] = 8080;
	$fs = sock_init();
	if (sock_open($fs,$proxyx['host'],$proxyx['port']))
	{
		sock_write($fs,"GET ".$url." HTTP/1.0\r\n");
		sock_write($fs,"Host: ".$urlx["host"]."\r\n");
		if (!empty($headers))
			sock_write($fs,$headers);
		sock_write($fs,"\r\n");
		sock_flush($fs);
		$result = "";
		while (!sock_eof($fs) && !sock_error($fs,$error,$errno))
		{
			$buf = sock_read($fs);
			$result .= $buf;
		}
		sock_close($fs);
	}
	else
	{
		sock_error($fs,$error,$errno);
		$result = "[http_get_proxy] Error $errno: $error";
	}
	sock_free($fs);
	return $result;
}

function cps_http_post($url, $data = "", $type = true, $headers = "", &$networkTime, $hmacUserKey = false, $hmacSignKey = false)
{
	// TODO: implement HMAC support when server side supports it
	$result = "";
	$errno = 0; $error = "";
	$urlx = parse_url($url);
	if (empty($urlx['port'])) $urlx['port'] = 80;
	$fs = sock_init();
	$time_start = microtime(true);
	if (sock_open($fs,$urlx['host'],$urlx['port']))
	{
		$networkTime = microtime(true) - $time_start;
		sock_write($fs,"POST ".$urlx["path"]." HTTP/1.0\r\n");
		sock_write($fs,"Host: ".$urlx["host"]."\r\n");
		sock_write($fs,"Content-Length: ".strlen($data)."\r\n");
		if ($type)
			sock_write($fs,"Content-Type: application/x-www-form-urlencoded\r\n");
		if (!empty($headers))
			sock_write($fs,$headers);
		sock_write($fs,"\r\n");
		$time_start = microtime(true);
		sock_write($fs,$data);
		sock_flush($fs);
		$result = "";
		while (!sock_error($fs,$error,$errno))
		{
			$buf = sock_read($fs);
			$result .= $buf;
		}
		sock_close($fs);
		$networkTime += microtime(true) - $time_start;
	}
	else
	{
		sock_error($fs,$error,$errno);
		$result = "[http_post] Error $errno: $error";
		throw new CPS_Exception(array(array('long_message' => "[http_post] Error $errno: $error", 'code' => ERROR_CODE_SOCKET_ERROR, 'level' => 'ERROR', 'source' => 'CPS_API')));
	}
	
	sock_free($fs);
	
	return $result;
}

function cps_http_post_proxy($proxy, $url, $data = "", $type = true, $headers = "")
{
	$result = "";
	$errno = 0; $error = "";
	$urlx = parse_url($url);
	$proxyx = parse_url($proxy);
	if (empty($proxyx['port'])) $proxyx['port'] = 8080;
	$fs = sock_init();
	if (sock_open($fs,$proxyx['host'],$proxyx['port']))
	{
		sock_write($fs,"POST ".$urlx["path"]." HTTP/1.0\r\n");
		sock_write($fs,"Host: ".$urlx["host"]."\r\n");
		sock_write($fs,"Content-Length: ".strlen($data)."\r\n");
		if ($type)
			sock_write($fs,"Content-Type: application/x-www-form-urlencoded\r\n");
		if (!empty($headers))
			sock_write($fs,$headers);
		sock_write($fs,"\r\n");
		sock_write($fs,$data);
		sock_flush($fs);
		$result = "";
		while (!sock_eof($fs) && !sock_error($fs,$error,$errno))
		{
			$buf = sock_read($fs);
			$result .= $buf;
		}
		sock_close($fs);
	}
	else
	{
		sock_error($fs,$error,$errno);
		$result = "[http_post] Error $errno: $error";
		throw new CPS_Exception(array(array('long_message' => "[http_post] Error $errno: $error", 'code' => ERROR_CODE_SOCKET_ERROR, 'level' => 'ERROR', 'source' => 'CPS_API')));
	}
	sock_free($fs);
	return $result;
}

function cps_http_headers($data)
{
	if (($pos = strpos($data,"\r\n\r\n"))!==false)
		return substr($data,0,(-1)*$pos);
	else if (($pos = strpos($data,"\n\n"))!==false)
		return substr($data,0,(-1)*$pos);
	else if (($pos = strpos($data,"\r\r"))!==false)
		return substr($data,0,(-1)*$pos);
	else
		return $data;
}

function cps_http_data($data)
{
	if (($pos = strpos($data,"\r\n\r\n"))!==false)
		return substr($data,$pos+4);
	else if (($pos = strpos($data,"\n\n"))!==false)
		return substr($data,$pos+2);
	else if (($pos = strpos($data,"\r\r"))!==false)
		return substr($data,$pos+2);
	else
		return $data;
}
?>