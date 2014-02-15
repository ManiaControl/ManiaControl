<?php
namespace ManiaControl\Files;

use ManiaControl\ManiaControl;

/**
 * Asynchronous File Reader
 *
 * @author kremsy & steeffeen
 */
class AsynchronousFileReader {
	/**
	 * Constants
	 */
	const TIMEOUT_ERROR        = 'Timed out while reading data';
	const RESPONSE_ERROR       = 'Connection or response error';
	const NO_DATA_ERROR        = 'No data returned';
	const INVALID_RESULT_ERROR = 'Invalid Result';
	const SOCKET_TIMEOUT       = 10;


	/**
	 * Private Properties
	 */
	private $sockets = array();
	private $maniaControl = null;

	/**
	 * Construct
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Appends the Data
	 */
	public function appendData() {
		foreach($this->sockets as $key => &$socket) {
			/** @var SocketStructure $socket */
			$socket->streamBuffer .= fread($socket->socket, 4096);
			if (feof($socket->socket) || time() > ($socket->creationTime + self::SOCKET_TIMEOUT)) {
				fclose($socket->socket);
				unset($this->sockets[$key]);

				$result = "";
				$error  = 0;
				if (time() > ($socket->creationTime + self::SOCKET_TIMEOUT)) {
					$error = self::TIMEOUT_ERROR;
				} else if (substr($socket->streamBuffer, 9, 3) != "200") {
					$error  = self::RESPONSE_ERROR;
					$result = $this->parseResult($socket->streamBuffer);

				} else if ($socket->streamBuffer == '') {
					$error = self::NO_DATA_ERROR;
				} else {
					$result = $this->parseResult($socket->streamBuffer);
					if ($result == self::INVALID_RESULT_ERROR) {
						$error = self::INVALID_RESULT_ERROR;
					}
				}

				call_user_func($socket->function, $result, $error);
			}
		}
	}

	/**
	 * Parse the Stream Result
	 *
	 * @param $streamBuffer
	 * @return string
	 */
	private function parseResult($streamBuffer) {
		$resultArray = explode(PHP_EOL . PHP_EOL, $streamBuffer, 2);
		if (count($resultArray) < 2) {
			$result = self::INVALID_RESULT_ERROR;
		} else {
			$header = $this->parse_header($resultArray[0]);
			if (isset($header["Transfer-Encoding"])) {
				$result = $this->decode_chunked($resultArray[1]);
			} else {
				$result = $resultArray[1];
			}
		}
		return $result;
	}


	/**
	 * Decode Chunks
	 *
	 * @param $str
	 * @return string
	 */
	private function decode_chunked($str) {
		for($res = ''; !empty($str); $str = trim($str)) {
			$pos = strpos($str, PHP_EOL);
			$len = hexdec(substr($str, 0, $pos));
			$res .= substr($str, $pos + 2, $len);
			$str = substr($str, $pos + 2 + $len);
		}
		return $res;
	}


	/**
	 * Parse The Header
	 *
	 * @param $raw_headers
	 * @return array
	 */
	private function parse_header($raw_headers) {
		$headers = array();
		$key     = '';

		foreach(explode("\n", $raw_headers) as $i => $h) {
			$h = explode(':', $h, 2);

			if (isset($h[1])) {
				if (!isset($headers[$h[0]])) {
					$headers[$h[0]] = trim($h[1]);
				} elseif (is_array($headers[$h[0]])) {
					$headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1]))); // [+]
				} else {
					$headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [+]
				}

				$key = $h[0];
			} else {
				if (substr($h[0], 0, 1) == "\t") // [+]
				{
					$headers[$key] .= "\r\n\t" . trim($h[0]);
				} elseif (!$key) {
					$headers[0] = trim($h[0]);
				}
				trim($h[0]);
			}
		}
		return $headers;
	}

	/**
	 * Load a remote file
	 *
	 * @param string $url
	 * @param        $function
	 * @param string $contentType
	 * @param string $customHeader
	 * @return bool
	 */
	public function loadFile($url, $function, $contentType = 'UTF-8', $customHeader = '') {
		if (!is_callable($function)) {
			$this->maniaControl->log("Function is not callable");
			return false;
		}

		if (!$url) {
			return null;
		}
		$urlData  = parse_url($url);
		$port     = (isset($urlData['port']) ? $urlData['port'] : 80);
		$urlQuery = isset($urlData['query']) ? "?" . $urlData['query'] : "";

		$socket = @fsockopen($urlData['host'], $port, $errno, $errstr, 4);
		if (!$socket) {
			return false;
		}

		if ($customHeader == '') {
			$query = 'GET ' . $urlData['path'] . $urlQuery . ' HTTP/1.1' . PHP_EOL;
			$query .= 'Host: ' . $urlData['host'] . PHP_EOL;
			$query .= 'Content-Type: ' . $contentType . PHP_EOL;
			$query .= 'Connection: close' . PHP_EOL;
			$query .= 'User-Agent: ManiaControl v' . ManiaControl::VERSION . PHP_EOL;
			$query .= PHP_EOL;
		} else {
			$query = $customHeader;
		}

		fwrite($socket, $query);

		$success = stream_set_blocking($socket, 0);
		if (!$success) {
			return false;
		}

		$socketStructure = new SocketStructure($url, $socket, $function);
		array_push($this->sockets, $socketStructure);

		return true;
	}
}