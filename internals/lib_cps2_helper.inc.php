<?php

if (defined('USE_OLD_LIB_SOCK'))
	require_once("lib_sock_old.inc.php");
else
	require_once("lib_sock.inc.php");
require_once("lib_protobuf.inc.php");

function cps2_header($length)
{
	$result = "        ";
	$result[0] = chr(0x09);
	$result[1] = chr(0x09);
	$result[2] = chr(0x00);
	$result[3] = chr(0x00);
	$result[4] = chr(($length & 0x000000FF));
	$result[5] = chr(($length & 0x0000FF00) >> 8);
	$result[6] = chr(($length & 0x00FF0000) >> 16);
	$result[7] = chr(($length & 0xFF000000) >> 24);
	return $result;
}

function cps2_length($header)
{
	if (strlen($header) >= 8) {
		$result = ord($header[4]) | (ord($header[5]) << 8) | (ord($header[6]) << 16) | (ord($header[7]) << 24);
		return $result;
	} else {
		return 0;
	}
}

function cps2_check_header($header)
{
	if (strlen($header) >= 8)
		if ($header[0] == chr(0x09) && $header[1] == chr(0x09) && $header[2] == chr(0x00) && $header[3] == chr(0x00))
			return true;
	return false;
}

function cps2_random_string($length)
{
	$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$charactersLength = strlen($characters);
	$ret = "";
	for ($i = 0; $i < $length; $i++)
		$ret .= $characters[rand(0, $charactersLength - 1)];
	return $ret;
}

function cps2_exchange($host, $port, $message, $recipient, &$networkTime, $hmacUserKey = false, $hmacSignKey = false, $ssl_options = false)
{
	$result = "";
	$errno = 0; $error = "";
	$sock = sock_init();
	if (is_array($ssl_options) && isset($ssl_options["enabled"]) && function_exists("sock_enable_ssl"))
		sock_enable_ssl($sock, $ssl_options["enabled"], $ssl_options["ca"], $ssl_options["cn"]);
	$time_start = microtime(true);
	if (sock_open($sock, $host, $port))
	{
		$networkTime = microtime(true) - $time_start;
		$pbsend = new SimpleProtoBuf();
		$pbsend->NewFieldString(1, $message);
		if ($recipient === false)
			$pbsend->NewFieldString(2, "special:detect-storage");
		else
			$pbsend->NewFieldString(2, $recipient);
		$pbsendbytes = $pbsend->ToBytes();
		if ($hmacUserKey !== false && $hmacSignKey !== false)
		{
			$pbsend->NewFieldString(14, $token = cps2_random_string(16));
			$pbsend->NewFieldFixed64_is_32bit(15, $validity = date("U") + 10);
			$pbsend->NewFieldString(16, $hmacUserKey);
			$pbsend->NewFieldString(17, sha1(sha1($message) . $token . $validity . $hmacSignKey));
			$pbsend->NewFieldString(18, hash_hmac('sha1', $message . $token . $validity, $hmacSignKey));
			$pbsendbytes = $pbsend->ToBytes();
		}
		$length = strlen($pbsendbytes);
		$header = cps2_header($length);

		$time_start = microtime(true);
		sock_write($sock, $header . $pbsendbytes, 8 + $length);
		$header = sock_read($sock, 8);
		if (!cps2_check_header($header))
			throw new CPS_Exception(array(array('long_message' => "Wrong header received", 'code' => ERROR_CODE_TIMEOUT, 'level' => 'FAILED', 'source' => 'CPS_API')));
		$length = cps2_length($header);
		if ($length <= 2) {
			throw new CPS_Exception(array(array('long_message' => "Response timeout", 'code' => ERROR_CODE_TIMEOUT, 'level' => 'FAILED', 'source' => 'CPS_API')));
		}
		$read = 0;
		$pbrecvbytes = "";
		while ($read < $length)
		{
			if (($part = sock_read($sock, $length - $read)) === FALSE) {
				throw new CPS_Exception(array(array('long_message' => "Socket error when receiving response", 'code' => ERROR_CODE_TIMEOUT, 'level' => 'FAILED', 'source' => 'CPS_API')));
			}
			$read += strlen($part);
			$pbrecvbytes .= $part;
		}
		$networkTime += microtime(true) - $time_start;
		$result = "";
		$pbrecv = new SimpleProtoBuf();
		$pbrecv->FromBytes($pbrecvbytes);
		$wasok = true;
		$reterr = $pbrecv->GetFieldString(5);
		if ($reterr !== false && strlen($reterr) >= 8)
		{
			$err_code = ord($reterr[3]) * 256 * 256 * 256 + ord($reterr[2]) * 256 * 256 + ord($reterr[1]) * 256 + ord($reterr[0]);
			$err_line = ord($reterr[7]) * 256 * 256 * 256 + ord($reterr[6]) * 256 * 256 + ord($reterr[5]) * 256 + ord($reterr[4]);
			$err_file = substr($reterr, 8);
			if ($err_code != 0)
			{
				$wasok = false;
				$result = "[cps2_protobuf] Error $err_code @ $err_file:$err_line";
				throw new CPS_Exception(array(array('long_message' => "[cps2_protobuf] Error $err_code @ $err_file:$err_line", 'code' => ERROR_CODE_PROTOBUF_ERROR, 'level' => 'FAILED', 'source' => 'CPS_API')));
			}
		}
		if ($wasok)
			$result = $pbrecv->GetFieldString(1);
		sock_close($sock);
	}
	else
	{
		sock_error($sock,$error,$errno);
		$result = "[cps2_exchange] Error $errno: $error";
		$code = ERROR_CODE_SOCKET_ERROR;
		if ($error == "SSL handshake error")
			$code = ERROR_CODE_SSL_HANDSHAKE;
		throw new CPS_Exception(array(array('long_message' => "[cps2_exchange] Error $errno: $error", 'code' => $code, 'level' => 'FAILED', 'source' => 'CPS_API')));
	}
	sock_free($sock);
	return $result;
}
?>