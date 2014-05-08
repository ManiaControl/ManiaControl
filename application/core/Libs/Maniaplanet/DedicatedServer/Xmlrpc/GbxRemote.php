<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer\Xmlrpc;

class GbxRemote
{
	const MAX_REQUEST_SIZE  = 0x200000; // 2MB
	const MAX_RESPONSE_SIZE = 0x400000; // 4MB

	public static $received;
	public static $sent;

	private $socket;
	private $timeouts = array(
		'read' => 5000,
		'write' => 5000
	);
	private $requestHandle;
	private $callbacksBuffer = array();
	private $multicallBuffer = array();
	private $lastNetworkActivity = 0;

	/**
	 * @param string $host
	 * @param int $port
	 * @param int $timeout Timeout when opening connection
	 */
	function __construct($host, $port, $timeout = 5)
	{
		$this->requestHandle = (int) 0x80000000;
		$this->connect($host, $port, $timeout);
	}

	function __destruct()
	{
		$this->terminate();
	}

	/**
	 * Change timeouts
	 * @param int $read read timeout (in ms), 0 to leave unchanged
	 * @param int $write write timeout (in ms), 0 to leave unchanged
	 */
	function setTimeouts($read=0, $write=0)
	{
		if($read)
			$this->timeouts['read'] = $read;
		if($write)
			$this->timeouts['write'] = $write;
	}

	/**
	 * @return int Network idle time in seconds
	 */
	function getIdleTime()
	{
		$this->assertConnected();
		return time() - $this->lastNetworkActivity;
	}

	/**
	 * @param string $host
	 * @param int $port
	 * @param int $timeout
	 * @throws TransportException
	 */
	private function connect($host, $port, $timeout)
	{
		$this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
		if(!$this->socket)
			throw new TransportException('Cannot open socket', TransportException::NOT_INITIALIZED);

		stream_set_write_buffer($this->socket, 0);

		// handshake
		$header = $this->read(15);
		if($header === false)
			throw new TransportException('Connection interrupted during handshake', TransportException::INTERRUPTED);

		extract(unpack('Vsize/a*protocol', $header));
		/** @var $size int */
		/** @var $protocol string */
		if($size != 11 || $protocol != 'GBXRemote 2')
			throw new TransportException('Wrong protocol header', TransportException::WRONG_PROTOCOL);
		$this->lastNetworkActivity = time();
	}

	function terminate()
	{
		if($this->socket)
		{
			fclose($this->socket);
			$this->socket = null;
		}
	}

	/**
	 * @param string $method
	 * @param mixed[] $args
	 * @return mixed
	 * @throws MessageException
	 */
	function query($method, $args=array())
	{
		$this->assertConnected();
		$xml = Request::encode($method, $args);

		if(strlen($xml) > self::MAX_REQUEST_SIZE-8)
		{
			if($method != 'system.multicall' || count($args[0]) < 2)
				throw new MessageException('Request too large', MessageException::REQUEST_TOO_LARGE);

			$mid = count($args[0]) >> 1;
			$res1 = $this->query('system.multicall', array(array_slice($args[0], 0, $mid)));
			$res2 = $this->query('system.multicall', array(array_slice($args[0], $mid)));
			return array_merge($res1, $res2);
		}

		$this->writeMessage($xml);
		return $this->flush(true);
	}

	/**
	 * @param string $method
	 * @param mixed[] $args
	 */
	function addCall($method, $args)
	{
		$this->multicallBuffer[] = array(
			'methodName' => $method,
			'params' => $args
		);
	}

	/**
	 * @return mixed
	 */
	function multiquery()
	{
		switch(count($this->multicallBuffer))
		{
			case 0:
				return;
			case 1:
				$call = array_shift($this->multicallBuffer);
				return $this->query($call['methodName'], $call['params']);
			default:
				$result = $this->query('system.multicall', array($this->multicallBuffer));
				$this->multicallBuffer = array();
				return $result;
		}
	}

	/**
	 * @return mixed[]
	 */
	function getCallbacks()
	{
		$this->assertConnected();
		$this->flush();
		$cb = $this->callbacksBuffer;
		$this->callbacksBuffer = array();
		return $cb;
	}

	/**
	 * @throws TransportException
	 */
	private function assertConnected()
	{
		if(!$this->socket)
			throw new TransportException('Connection not initialized', TransportException::NOT_INITIALIZED);
	}

	/**
	 * @param bool $waitResponse
	 * @return mixed
	 * @throws FaultException
	 */
	private function flush($waitResponse=false)
	{
		$r = array($this->socket);
		$w = null;
		$e = null;
		$n = @stream_select($r, $w, $e, 0);
		while($waitResponse || $n > 0)
		{
			list($handle, $xml) = $this->readMessage();
			list($type, $value) = Request::decode($xml);
			switch($type)
			{
				case 'fault':
					throw FaultException::create($value['faultString'], $value['faultCode']);
				case 'response':
					if($handle == $this->requestHandle)
						return $value;
					break;
				case 'call':
					$this->callbacksBuffer[] = $value;
			}

			if(!$waitResponse)
				$n = @stream_select($r, $w, $e, 0);
		};
	}

	/**
	 * @return mixed[]
	 * @throws TransportException
	 * @throws MessageException
	 */
	private function readMessage()
	{
		$header = $this->read(8);
		if($header === false)
			throw new TransportException('Connection interrupted while reading header', TransportException::INTERRUPTED);

		extract(unpack('Vsize/Vhandle', $header));
		/** @var $size int */
		/** @var $handle int */
		if($size == 0 || $handle == 0)
			throw new TransportException('Incorrect header', TransportException::PROTOCOL_ERROR);

		if($size > self::MAX_RESPONSE_SIZE)
			throw new MessageException('Response too large', MessageException::RESPONSE_TOO_LARGE);

		$data = $this->read($size);
		if($data === false)
			throw new TransportException('Connection interrupted while reading data', TransportException::INTERRUPTED);

		$this->lastNetworkActivity = time();
		return array($handle, $data);
	}

	/**
	 * @param string $xml
	 * @throws TransportException
	 */
	private function writeMessage($xml)
	{
		$data = pack('V2a*', strlen($xml), ++$this->requestHandle, $xml);
		if(!$this->write($data))
			throw new TransportException('Connection interrupted while writing', TransportException::INTERRUPTED);
		$this->lastNetworkActivity = time();
	}

	/**
	 * @param int $size
	 * @return boolean|string
	 */
	private function read($size)
	{
		@stream_set_timeout($this->socket, 0, $this->timeouts['read'] * 1000);

		$data = '';
		while(strlen($data) < $size)
		{
			$buf = fread($this->socket, $size - strlen($data));
			if($buf === '' || $buf === false)
				return false;
			$data .= $buf;
		}

		self::$received += $size;
		return $data;
	}

	/**
	 * @param string $data
	 * @return boolean
	 */
	private function write($data)
	{
		@stream_set_timeout($this->socket, 0, $this->timeouts['write'] * 1000);

		while(strlen($data) > 0)
		{
			$written = fwrite($this->socket, $data);
			if($written === 0 || $written === false)
				return false;

			$data = substr($data, $written);
		}

		self::$sent += strlen($data);
		return true;
	}
}

class TransportException extends Exception
{
	const NOT_INITIALIZED = 1;
	const INTERRUPTED     = 2;
	const WRONG_PROTOCOL  = 3;
	const PROTOCOL_ERROR  = 4;
}

class MessageException extends Exception
{
	const REQUEST_TOO_LARGE  = 1;
	const RESPONSE_TOO_LARGE = 2;
}
