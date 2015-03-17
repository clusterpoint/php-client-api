<?php
	if (!defined("sock_timeout")) define("sock_timeout",60);

	CLASS SOCK
	{
		var $socket;
		var $error;
		var $errno;
		var $timeout;
	}

	function sock_init($timeout = sock_timeout)
	{
		$sock = new SOCK;
		unset($sock->socket);
		$sock->error = "";
		$sock->errno = 0;
		$sock->timeout = $timeout;
		return $sock;
	}

	function sock_open(&$sock, $host, $port)
	{
		global $TOTAL_SOCKETS;
		$TOTAL_SOCKETS++;
		$sock->error = "";
		$sock->errno = 0;
		
		if (substr($host, 0, 7) == 'unix://') {
			$sock->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
			$host = substr($host, 7);
		} else {
			$sock->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		}
		// 1 second timeout for connection
		socket_set_option($sock->socket, SOL_SOCKET, SO_RCVTIMEO,array('sec' => 1, 'usec' => 0));
		$res = @socket_connect($sock->socket, $host, $port);
		socket_set_option($sock->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $sock->timeout, 'usec' => 0));

		if (!$res) {
			$sock->errno = socket_last_error($sock->socket);
			$sock->error = socket_strerror($sock->errno);
		}
		return $res;
	}

	function sock_read(&$sock, $length = 1024)
	{
		$sock->error = "";
		$sock->errno = 0;
		$data = socket_read($sock->socket, $length);
		if (strlen($data)<$length)
		{
			$left = $length - strlen($data);
			$errs = 0;
			while ($left>0 && $errs<5)
			{
				$oldleft = $left;
				$part = socket_read($sock->socket, $left);
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
			$ret += @socket_write($sock->socket,substr($data,$ret),$length-$ret);
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
/*		else if ($flush)
			sock_flush($sock);*/
	}
	
	function sock_flush(&$sock)
	{
	/*	$sock->error = "";
		$sock->errno = 0;
		fflush($sock->socket);*/
	}

	function sock_close(&$sock)
	{
		$sock->error = "";
		$sock->errno = 0;
		@socket_shutdown($sock->socket, 1);
		socket_close($sock->socket);
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