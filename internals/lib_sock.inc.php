<?php
if (!defined("sock_timeout")) define("sock_timeout",60);

CLASS SOCK
{
	var $socket;
	var $error;
	var $errno;
	var $timeout;
	var $ssl_ca; // CA
	var $ssl_cn; // Common Name
}

function sock_init($timeout = sock_timeout)
{
	$sock = new SOCK;
	unset($sock->socket);
	$sock->error = "";
	$sock->errno = 0;
	$sock->timeout = $timeout;
	$sock->ssl_enabled = false;
	$sock->ssl_ca = false;
	$sock->ssl_cn = false;
	return $sock;
}

function sock_enable_ssl(&$sock, $enabled = false, $ca = false, $cn = false)
{
	$sock->ssl_enabled = $enabled;
	$sock->ssl_ca = $ca;
	$sock->ssl_cn = $cn;
}

function sock_open(&$sock, $host, $port)
{
	global $TOTAL_SOCKETS;
	$TOTAL_SOCKETS++;
	$sock->error = "";
	$sock->errno = 0;

	if (substr($host, 0, 7) != 'unix://') {
		$host = "tcp://$host:$port";
	}

	$sock->socket = @stream_socket_client($host, $sock->errno, $sock->error, 1, STREAM_CLIENT_CONNECT);
	if ($sock->socket !== false) // successfull connection
	{
		stream_set_timeout($sock->socket, $sock->timeout);

		if ($sock->ssl_enabled)
		{
			if ($sock->ssl_ca !== false)
			{
				stream_context_set_option($sock->socket, 'ssl', 'verify_peer', true);
				stream_context_set_option($sock->socket, 'ssl', 'cafile', $sock->ssl_ca);
			}
			if ($sock->ssl_cn !== false) // only PHP 5.6 provides common name validation, so lets do this by ourselves
				stream_context_set_option($sock->socket, 'ssl', 'capture_peer_cert', true);

			if (stream_socket_enable_crypto($sock->socket, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT))
			{
				if ($sock->ssl_cn !== false)
				{
					$parameters = stream_context_get_params($sock->socket);
					$certificate = (openssl_x509_parse($parameters["options"]["ssl"]["peer_certificate"]));
					$common_name = $certificate["subject"]["CN"];

					if (fnmatch($common_name, $sock->ssl_cn, FNM_CASEFOLD) || fnmatch($sock->ssl_cn, $common_name, FNM_CASEFOLD))
						return true;
					else
					{
						sock_close($sock);
						$sock->error = "SSL handshake error";
						$sock->errno = 0;
						return false;
					}
				}

				return true;
			}
			else
			{
				sock_close($sock);
				$sock->error = "SSL handshake error";
				$sock->errno = 0;
				return false;
			}
		}
		else
			return true;
	}
	else
		return false;
}

function sock_read(&$sock, $length = 1024)
{
	$sock->error = "";
	$sock->errno = 0;
	$data = @fread($sock->socket, $length);
	if (strlen($data)<$length)
	{
		$left = $length - strlen($data);
		$errs = 0;
		while ($left>0 && $errs<5)
		{
			$oldleft = $left;
			$part = fread($sock->socket, $left);
			if ($part!==false && strlen($part)>0)
			{
				$data .= $part;
				$left = $length - strlen($data);
			}
			elseif ($part === false)
				$left = 0;
			else
				$errs++;
		}
		if ($errs>=5)
		{
			$sock->error = "Socket read error";
			$sock->errno = 0;
		}
	}
	return $data;
}

function sock_write(&$sock, $data, $length = 0, $flush = false)
{
	$sock->error = "";
	$sock->errno = 0;
	if (empty($length)) $length = strlen($data);

	$ret = false;
	$retries = 5;
	while (($ret === false || $ret < $length) && $retries > 0)
	{
		$ret += @fwrite($sock->socket,substr($data,$ret),$length-$ret);
		if ($ret === false || $ret < $length)
			usleep(500);
		$retries--;
	}

	if ($ret === false || $ret < $length)
	{
		$sock->errno = 0;
		if ($ret<$length && ($ret !== false))
			$sock->error = "Socket write timeout";
		else
			$sock->error = "Socket write error";
	}
}

function sock_flush(&$sock)
{
	fflush($sock->socket);
}

function sock_close(&$sock)
{
	$sock->error = "";
	$sock->errno = 0;
	@stream_socket_shutdown($sock->socket, STREAM_SHUT_RDWR);
}

function sock_free(&$sock)
{
	unset($sock);
}

function sock_timeout(&$sock)
{
	if ($sock->error=="Socket timeout" && $sock->errno==0)
		return true;
	else
		return false;
}

function sock_eof(&$sock)
{
	return feof($sock->socket);
}

function sock_error(&$sock, &$error, &$errno)
{
	if (!empty($sock->error) || !empty($sock->errno))
	{
		$error = $sock->error;
		$errno = $sock->errno;
		return true;
	}
	else
		return false;
}
?>