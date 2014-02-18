<?php
namespace ManiaControl\Files;

use cURL\Exception;
use cURL\Request;
use cURL\Response;
use ManiaControl\ManiaControl;

/**
 * Asynchronous File Reader
 *
 * @author kremsy & steeffeen
 */
class AsynchronousFileReader {
	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $requests = array();

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
		foreach($this->requests as $key => $request) {
			/** @var Request $request */
			try {
				if ($request->socketPerform()) {
					$request->socketSelect();
				}
			} catch(Exception $e) {
				if ($e->getMessage() == "Cannot perform if there are no requests in queue.") {
					unset($this->requests[$key]);
				} else {
					throw $e;
				}
			}
		}
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

		$request = new \cURL\Request($url);

		$request->getOptions()->set(CURLOPT_TIMEOUT, 5) //
			->set(CURLOPT_HEADER, false) //
			->set(CURLOPT_CRLF, true) //
			//->set(CURLOPT_HTTPHEADER, array("Content-Type: " . $contentType))
			->set(CURLOPT_USERAGENT, 'User-Agent: ManiaControl v' . ManiaControl::VERSION) //
			->set(CURLOPT_RETURNTRANSFER, true);


		$request->addListener('complete', function (\cURL\Event $event) use (&$function) {
			/** @var Response $response */
			$response = $event->response;

			$error   = "";
			$content = "";
			if ($response->hasError()) {
				$error = $response->getError()->getMessage();
			} else {
				$content = $response->getContent();
			}

			call_user_func($function, $content, $error);
		});

		array_push($this->requests, $request);

		return true;
	}


	/**
	 * Send Data via POST Method
	 *
	 * @param        $url
	 * @param        $function
	 * @param        $content
	 * @param string $contentType
	 * @return bool|null
	 */
	public function postData($url, $function, $content, $compressed = false, $contentType = 'UTF-8') {
		/*if (!is_callable($function)) {
			$this->maniaControl->log("Function is not callable");
			return false;
		}

		if (!$url) {
			return null;
		}
		$urlData = parse_url($url);
		$port    = (isset($urlData['port']) ? $urlData['port'] : 80);

		$socket = @fsockopen($urlData['host'], $port, $errno, $errstr, 4);
		if (!$socket) {
			return false;
		}

		$query = 'POST ' . $urlData['path'] . ' HTTP/1.1' . PHP_EOL;
		$query .= 'Host: ' . $urlData['host'] . PHP_EOL;
		$query .= 'Accept-Charset: utf-8' . PHP_EOL;
		$query .= 'Accept-Encoding: gzip, deflate' . PHP_EOL;
		//$query .= 'Content-Encoding: gzip' . PHP_EOL;
		$query .= 'Content-Type: text/xml; charset=utf-8;' . PHP_EOL;
		$query .= 'Keep-Alive: 300' . PHP_EOL;
		$query .= 'Connection: Keep-Alive' . PHP_EOL;
		$query .= 'User-Agent: ManiaControl v' . ManiaControl::VERSION . PHP_EOL;
		$query .= 'Content-Length: ' . strlen($content) . PHP_EOL . PHP_EOL;

		$query .= $content . PHP_EOL;

		fwrite($socket, $query);

		$success = stream_set_blocking($socket, 0);
		if (!$success) {
			return false;
		}

		$socketStructure = new SocketStructure($url, $socket, $function);
		array_push($this->sockets, $socketStructure);

		return true;*/
	}
}