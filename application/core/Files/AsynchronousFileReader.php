<?php

namespace ManiaControl\Files;

use cURL\Event;
use cURL\Exception;
use cURL\Request;
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
	 * Constants
	 */
	const CONTENT_TYPE_JSON = 'application/json';

	/*
	 * Private Properties
	 */
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

	/**
	 * Append available Data of active Requests
	 */
	public function appendData() {
		foreach ($this->requests as $key => $request) {
			try {
				if ($request->socketPerform()) {
					$request->socketSelect();
				}
			} catch (Exception $e) {
				if ($e->getMessage() === 'Cannot perform if there are no requests in queue.') {
					unset($this->requests[$key]);
				} else {
					throw $e;
				}
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
	 * @return bool
	 */
	public function loadFile($url, callable $function, $contentType = 'UTF-8', $keepAlive = 0) {
		if (!$url) {
			$this->maniaControl->log('Missing URL!');
			return false;
		}

		$headers = array();
		array_push($headers, 'Content-Type: ' . $contentType);
		if ($keepAlive) {
			array_push($headers, 'Keep-Alive: ' . $keepAlive);
			array_push($headers, 'Connection: Keep-Alive');
		}

		$request = new Request($url);
		$this->prepareOptions($request->getOptions())
		     ->set(CURLOPT_AUTOREFERER, true) // accept link reference
		     ->set(CURLOPT_HTTPHEADER, $headers); // headers

		$request->addListener('complete', function (Event $event) use (&$function) {
			$error   = null;
			$content = null;
			if ($event->response->hasError()) {
				$error = $event->response->getError()
				                         ->getMessage();
			} else {
				$content = $event->response->getContent();
			}
			call_user_func($function, $content, $error);
		});

		$this->addRequest($request);
		return true;
	}

	/**
	 * Prepare the cURL Options
	 *
	 * @param Options $options
	 * @return Options
	 */
	private function prepareOptions(Options $options) {
		$options->set(CURLOPT_TIMEOUT, 10)
		        ->set(CURLOPT_HEADER, false) // don't display response header
		        ->set(CURLOPT_CRLF, true) // linux line feed
		        ->set(CURLOPT_ENCODING, '') // accept encoding
		        ->set(CURLOPT_USERAGENT, 'ManiaControl v' . ManiaControl::VERSION)
		        ->set(CURLOPT_RETURNTRANSFER, true);
		return $options;
	}

	/**
	 * Add a Request to the queue
	 *
	 * @param Request $request
	 */
	public function addRequest(Request $request) {
		array_push($this->requests, $request);
	}

	/**
	 * Send Data via POST Method
	 *
	 * @param string   $url
	 * @param callable $function
	 * @param string   $content
	 * @param bool     $compression
	 * @param string   $contentType
	 * @return bool
	 */
	public function postData($url, callable $function, $content, $compression = false,
	                         $contentType = 'text/xml; charset=UTF-8;') {
		if (!$url) {
			$this->maniaControl->log("Url is empty");
			return false;
		}

		$content = str_replace(array("\r", "\n"), '', $content);

		$headers = array();
		array_push($headers, 'Content-Type: ' . $contentType);
		array_push($headers, 'Keep-Alive: 300');
		array_push($headers, 'Connection: Keep-Alive');

		if ($compression) {
			$content = zlib_encode($content, 31);
			array_push($headers, 'Content-Encoding: gzip');
		}

		$request = new Request($url);
		$this->prepareOptions($request->getOptions())
		     ->set(CURLOPT_POST, true) // post method
		     ->set(CURLOPT_POSTFIELDS, $content) // post content field
		     ->set(CURLOPT_HTTPHEADER, $headers); // headers

		$request->addListener('complete', function (Event $event) use (&$function) {
			$error   = null;
			$content = null;
			if ($event->response->hasError()) {
				$error = $event->response->getError()
				                         ->getMessage();
			} else {
				$content = $event->response->getContent();
			}
			call_user_func($function, $content, $error);
		});

		$this->addRequest($request);
		return true;
	}
}
