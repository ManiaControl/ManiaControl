<?php

namespace ManiaControl\Files;

use cURL\Event;
use cURL\Exception;
use cURL\Request;
use cURL\Response;
use ManiaControl\ManiaControl;

/**
 * Asynchronous File Reader
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AsynchronousFileReader {
	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $requests = array();

	/**
	 * Construct FileReader
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
		foreach ($this->requests as $key => $request) {
			/** @var Request $request */
			try {
				if ($request->socketPerform()) {
					$request->socketSelect();
				}
			} catch (Exception $e) {
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
	 * @param string      $url
	 * @param    callable $function
	 * @param string      $contentType
	 * @param int         $keepAlive
	 * @return bool
	 */
	public function loadFile($url, $function, $contentType = 'UTF-8', $keepAlive = 0) {
		if (!is_callable($function)) {
			$this->maniaControl->log("Function is not callable");
			return false;
		}

		if (!$url) {
			$this->maniaControl->log("Url is empty");
			return false;
		}

		if ($keepAlive) {
			$header = array("Content-Type: " . $contentType, "Keep-Alive: " . $keepAlive, "Connection: Keep-Alive");
		} else {
			$header = array("Content-Type: " . $contentType);
		}

		$request = new Request($url);

		$request->getOptions()->set(CURLOPT_TIMEOUT, 10) //
			->set(CURLOPT_HEADER, false) //don't display response header
			->set(CURLOPT_CRLF, true) //linux linefeed
			->set(CURLOPT_ENCODING, "")//accept encoding
			->set(CURLOPT_AUTOREFERER, true)//accept link reference
			->set(CURLOPT_HTTPHEADER, $header) //
			->set(CURLOPT_USERAGENT, 'ManiaControl v' . ManiaControl::VERSION) //
			->set(CURLOPT_RETURNTRANSFER, true);

		$request->addListener('complete', function (Event $event) use (&$function) {
			$response = $event->response;

			$error   = null;
			$content = null;
			if ($response->hasError()) {
				$error = $response->getError()->getMessage();
			} else {
				$content = $response->getContent();
			}

			call_user_func($function, $content, $error);
		});

		$this->addRequest($request);
		return true;
	}

	/**
	 * Adds a Request to the queue
	 *
	 * @param Request $request
	 */
	public function addRequest(Request $request) {
		array_push($this->requests, $request);
	}

	/**
	 * Send Data via POST Method
	 *
	 * @param    string     $url
	 * @param      callable $function
	 * @param   string      $content
	 * @param bool          $compression
	 * @param string        $contentType
	 * @return bool
	 */
	public function postData($url, $function, $content, $compression = false, $contentType = 'text/xml; charset=UTF-8') {
		if (!is_callable($function)) {
			$this->maniaControl->log("Function is not callable");
			return false;
		}

		if (!$url) {
			$this->maniaControl->log("Url is empty");
			return false;
		}

		$content = str_replace(array("\r", "\n"), '', $content);
		if ($compression) {
			$content = zlib_encode($content, 31);
			$header  = array("Content-Type: " . $contentType, "Keep-Alive: 300", "Connection: Keep-Alive", "Content-Encoding: gzip");
		} else {
			$header = array("Content-Type: " . $contentType, "Keep-Alive: 300", "Connection: Keep-Alive");
		}


		$request = new Request($url);
		$request->getOptions()->set(CURLOPT_HEADER, false) //don't display response header
			->set(CURLOPT_CRLF, true) //linux linefeed
			->set(CURLOPT_ENCODING, "")//accept encoding
			//->set(CURLOPT_AUTOREFERER, true)//accept link reference
			->set(CURLOPT_POST, true) //post field
			->set(CURLOPT_POSTFIELDS, $content) //post content field
			->set(CURLOPT_HTTPHEADER, $header) //
			->set(CURLOPT_USERAGENT, 'ManiaControl v' . ManiaControl::VERSION) //
			->set(CURLOPT_RETURNTRANSFER, true) //
			->set(CURLOPT_TIMEOUT, 10);

		$request->addListener('complete', function (Event $event) use (&$function) {
			/** @var Response $response */
			$response = $event->response;
			$error    = "";
			$content  = "";
			if ($response->hasError()) {
				$error = $response->getError()->getMessage();
			} else {
				$content = $response->getContent();
			}

			call_user_func($function, $content, $error);
		});

		$this->addRequest($request);

		return true;
	}
}