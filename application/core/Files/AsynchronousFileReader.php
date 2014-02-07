<?php
namespace ManiaControl\Files;

use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;

/**
 * Asynchronous File Reader
 *
 * @author kremsy & steeffeen
 */
class AsynchronousFileReader implements TimerListener {
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

	public function appendData() {
		foreach($this->sockets as $key => &$socket) {
			/** @var SocketStructure $socket */
			$socket->streamBuffer .= fread($socket->socket, 1024);
			$info = stream_get_meta_data($socket->socket);

			if (feof($socket->socket) || $info['timed_out']) {
				fclose($socket->socket);
				unset($this->sockets[$key]);

				if ($info['timed_out']) {
					throw new \Exception("Timed out while reading data from " . $socket->url);
				}

				if (substr($socket->streamBuffer, 9, 3) != "200") {
					throw new \Exception("Connection or response error on " . $socket->url);
				}

				if ($socket->streamBuffer == '') {
					throw new \Exception("No data returned from " . $socket->url);
				}

				$result = explode("\r\n\r\n", $socket->streamBuffer, 2);

				if (count($result) < 2) {
					throw new \Exception("Invalid Result");
				}

				call_user_func($socket->function, $result[1]);
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
	public function loadFile($url, $function, $contentType = 'UTF-8') {
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
		stream_set_timeout($socket, 10);

		$query = 'GET ' . $urlData['path'] . $urlQuery . ' HTTP/1.0' . PHP_EOL;
		$query .= 'Host: ' . $urlData['host'] . PHP_EOL;
		$query .= 'Content-Type: ' . $contentType . PHP_EOL;
		$query .= 'User-Agent: ManiaControl v' . ManiaControl::VERSION . PHP_EOL;
		$query .= PHP_EOL;

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