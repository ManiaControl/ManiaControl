<?php

namespace ManiaControl\Files;

use cURL\Event;
use cURL\Request;
use ManiaControl\ManiaControl;

/**
 * Asynchronous File Reader
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2015 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AsynchronousFileReader {
	/*
	 * Constants
	 */
	const CONTENT_TYPE_JSON = 'application/json';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var Request[] $requests */
	private $requests = array();

	/**
	 * Construct a new Asynchronous File Reader Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	public static function newRequestTest($url) {
		$request = new Request($url);
		$request->getOptions()->set(CURLOPT_TIMEOUT, 60)->set(CURLOPT_HEADER, false)// don't display response header
		        ->set(CURLOPT_CRLF, true)// linux line feed
		        ->set(CURLOPT_ENCODING, '')// accept encoding
		        ->set(CURLOPT_USERAGENT, 'ManiaControl v' . ManiaControl::VERSION)// user-agent
		        ->set(CURLOPT_RETURNTRANSFER, true); // return instead of output content
		return $request;
	}

	/**
	 * Append available Data of active Requests
	 */
	public function appendData() {
		foreach ($this->requests as $key => $request) {
			if ($request->socketPerform()) {
				$request->socketSelect();
			} else {
				unset($this->requests[$key]);
			}
		}
	}

	/**
	 * Load a Remote File
	 *
	 * @param string   $url
	 * @param callable $function
	 * @param string   $contentType
	 * @param int      $keepAlive
	 * @param array    $headers Additional Headers
	 */
	public function loadFile($url, callable $function, $contentType = 'UTF-8', $keepAlive = 0, $headers = array()) {
		array_push($headers, 'Content-Type: ' . $contentType);
		if ($keepAlive) {
			array_push($headers, 'Keep-Alive: ' . $keepAlive);
			array_push($headers, 'Connection: Keep-Alive');
		}

		$request = $this->newRequest($url);
		$request->getOptions()->set(CURLOPT_AUTOREFERER, true)// accept link reference
		        ->set(CURLOPT_HTTPHEADER, $headers); // headers

		$request->addListener('complete', function (Event $event) use (&$function) {
			$error   = null;
			$content = null;
			if ($event->response->hasError()) {
				$error = $event->response->getError()->getMessage();
			} else {
				$content = $event->response->getContent();
			}
			call_user_func($function, $content, $error);
		});

		$this->addRequest($request);
	}

	/**
	 * Create a new cURL Request for the given URL, DO NOT CALL MANUALLY!
	 *
	 * @param string $url
	 * @return Request
	 */
	public function newRequest($url) {
		$request = new Request($url);
		$request->getOptions()->set(CURLOPT_TIMEOUT, 60)->set(CURLOPT_HEADER, false)// don't display response header
		        ->set(CURLOPT_CRLF, true)// linux line feed
		        ->set(CURLOPT_ENCODING, '')// accept encoding
		        ->set(CURLOPT_USERAGENT, 'ManiaControl v' . ManiaControl::VERSION)// user-agent
		        ->set(CURLOPT_RETURNTRANSFER, true); // return instead of output content
		return $request;
	}

	//TODO remove, they are just for testing dedimania

	/**
	 * Add a Request to the queue, DO NOT CALL MANUALLY!
	 *
	 * @param Request $request
	 */
	public function addRequest(Request $request) {
		array_push($this->requests, $request);
	}

	public function postDataTest(Request $request, $url, callable $function, $content, $compression = false, $contentType = 'text/xml; charset=UTF-8;') {

		$headers = array();
		array_push($headers, 'Content-Type: ' . $contentType);
		array_push($headers, 'Keep-Alive: timeout=600, max=2000');
		array_push($headers, 'Connection: Keep-Alive');

		$content = str_replace(array("\r", "\n"), '', $content);
		if ($compression) {
			$content = zlib_encode($content, 31);
			array_push($headers, 'Content-Encoding: gzip');
		}

		$request->getOptions()->set(CURLOPT_POST, true)// post method
		        ->set(CURLOPT_POSTFIELDS, $content)// post content field
		        ->set(CURLOPT_HTTPHEADER, $headers) // headers
		;
		$request->addListener('complete', function (Event $event) use (&$function) {
			$error   = null;
			$content = null;
			if ($event->response->hasError()) {
				$error = $event->response->getError()->getMessage();
			} else {
				$content = $event->response->getContent();
			}

			call_user_func($function, $content, $error);
		});

		$this->addRequest($request);
	}


	/**
	 * Send Data via POST Method
	 *
	 * @param string   $url
	 * @param callable $function
	 * @param string   $content
	 * @param bool     $compression
	 * @param string   $contentType
	 * @param array    $headers Additional Headers
	 */
	public function postData($url, callable $function, $content, $compression = false, $contentType = 'text/xml; charset=UTF-8;', $headers = array()) {
		$httpRequest = new AsyncHttpRequest($this->maniaControl, $url);
		$httpRequest->setCallable($function)->setContent($content)->setCompression($compression)->setContentType($contentType)->setHeaders($headers);
		$httpRequest->postData();
	}
}
