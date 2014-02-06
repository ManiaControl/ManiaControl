<?php
namespace ManiaControl\Files;

use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;

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
		$this->maniaControl->timerManager->registerTimerListening($this, 'appendData', 1);
	}

	public function appendData() {
		foreach($this->sockets as &$socket) {
			/** @var SocketStructure $socket */
			$socket->streamBuffer .= fread($socket->socket, 512);
			$info = stream_get_meta_data($socket->socket);

			if (feof($socket->socket || $info['timed_out'])) { //TODO special error threadment on timeout
				fclose($socket->socket);

				$error = 0; //TODO error constants
				if ($info['timed_out'] || !$socket->streamBuffer) {
					$error = 1;
				} else if (substr($socket->streamBuffer, 9, 3) != "200") {
					$error = 2;
				}

				$result = explode("\r\n\r\n", $socket->streamBuffer, 2);

				if (count($result) < 2) {
					$error = 3;
				}

				//TODO call inner function

			}
		}
	}

	/**
	 * Load a remote file
	 *
	 * @param string $url
	 * @param string $contentType
	 * @return string || null
	 */
	public function loadFile($url, $contentType = 'UTF-8', $function) {
		if (!$url) {
			return null;
		}
		$urlData = parse_url($url);
		$port    = (isset($urlData['port']) ? $urlData['port'] : 80);

		$socket = fsockopen($urlData['host'], $port);
		stream_set_timeout($socket, 5);


		$query = 'GET ' . $urlData['path'] . ' HTTP/1.0' . PHP_EOL;
		$query .= 'Host: ' . $urlData['host'] . PHP_EOL;
		$query .= 'Content-Type: ' . $contentType . PHP_EOL;
		$query .= 'User-Agent: ManiaControl v' . ManiaControl::VERSION . PHP_EOL;
		$query .= PHP_EOL;

		fwrite($socket, $query);

		//TODO check error
		stream_set_blocking($this->sockets, 0);

		$socketStructure = new SocketStructure($socket, $function);
		array_push($this->sockets, $socketStructure);
	}
}