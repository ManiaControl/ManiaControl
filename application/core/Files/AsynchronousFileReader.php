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
	 * @return bool
	 */
	public function loadFile($url, $function, $contentType = 'UTF-8') {
		if (!is_callable($function)) {
			$this->maniaControl->log("Function is not callable");
			return false;
		}

		if (!$url) {
			$this->maniaControl->log("Url is empty");
			return false;
		}

		$request = new Request($url);

		$request->getOptions()->set(CURLOPT_TIMEOUT, 5) //
			->set(CURLOPT_HEADER, false) //don't display response header
			->set(CURLOPT_CRLF, true) //linux linefeed
			->set(CURLOPT_ENCODING, "")//accept encoding
			->set(CURLOPT_AUTOREFERER, true)//accept link reference
			->set(CURLOPT_HTTPHEADER, array("Content-Type: " . $contentType)) //
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
	 * @param        $url
	 * @param        $function
	 * @param        $content
	 * @param string $compression
	 * @param string $contentType
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

		if ($compression) {
			$content = gzencode($content);
		}
		$request = new Request($url);
		$request->getOptions()->set(CURLOPT_HEADER, false) //don't display response header
			->set(CURLOPT_CRLF, true) //linux linefeed
			->set(CURLOPT_ENCODING, "")//accept encoding
			//->set(CURLOPT_AUTOREFERER, true)//accept link reference
			->set(CURLOPT_POST, true) //post field
			->set(CURLOPT_POSTFIELDS, $content) //post content field
			->set(CURLOPT_HTTPHEADER, array("Content-Type: " . $contentType, "Keep-Alive: 300", "Connection: Keep-Alive", "Content-Encoding: gzip")) //
			//->set(CURLOPT_HTTPHEADER, array("Content-Type: " . $contentType, "Keep-Alive")) //
			->set(CURLOPT_USERAGENT, 'User-Agent: ManiaControl v' . ManiaControl::VERSION) //
			->set(CURLOPT_RETURNTRANSFER, true);

		$request->addListener('complete', function (\cURL\Event $event) use (&$function) {
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