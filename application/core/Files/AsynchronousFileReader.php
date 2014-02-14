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
					$error = self::RESPONSE_ERROR;
				} else if ($socket->streamBuffer == '') {
					$error = self::NO_DATA_ERROR;
				} else {
					$resultArray = explode("\r\n\r\n", $socket->streamBuffer, 2);

					if (count($resultArray) < 2) {
						$error = self::INVALID_RESULT_ERROR;
					} else {
						$result = $resultArray[1];
					}
				}
				call_user_func($socket->function, $result, $error);
			}
		}
	}

	/**
	 * Load a remote file
	 *
	 * @param string $url
	 * @param        $function
	 * @param string $contentType
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
			$query = 'GET ' . $urlData['path'] . $urlQuery . ' HTTP/1.0' . PHP_EOL;
			$query .= 'Host: ' . $urlData['host'] . PHP_EOL;
			$query .= 'Content-Type: ' . $contentType . PHP_EOL;
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