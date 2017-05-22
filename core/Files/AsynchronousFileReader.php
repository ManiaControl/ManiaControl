<?php

namespace ManiaControl\Files;

use cURL\Request;
use cURL\RequestsQueue;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

/**
 * Asynchronous File Reader
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AsynchronousFileReader implements UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const CONTENT_TYPE_JSON = 'application/json';

	const QUEUE_NONSERIALIZING = 0;
	const QUEUE_SERIALIZING = 1;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/** @var \cURL\RequestsQueue|null $requestQueue */
	private $requestQueue = array(null, null);

	/**
	 * Construct a new Asynchronous File Reader Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Queue for non-serializing requests (parallel is preferred)
		$this->requestQueue[self::QUEUE_NONSERIALIZING] = new RequestsQueue();

		// Queue for per host serialized requests
		$this->requestQueue[self::QUEUE_SERIALIZING] = new RequestsQueue();
		$this->requestQueue[self::QUEUE_SERIALIZING]->setOption(CURLMOPT_MAX_HOST_CONNECTIONS, 1);
	}

	/**
	 * Append available Data of active Requests
	 */
	public function appendData() {
		foreach ($this->requestQueue as &$queue) {
			do {
				if (($count = $queue->count()) == 0) {
					break;
				}

				if ($queue->socketPerform()) {
					$queue->socketSelect();
				}
			} while ($count != $queue->count());
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
	 * @deprecated @see ManiaControl\Files\AsyncHttpRequest
	 */
	public function loadFile($url, callable $function, $contentType = 'UTF-8', $keepAlive = 0, $headers = array()) {
		$httpRequest = new AsyncHttpRequest($this->maniaControl, $url);
		$httpRequest->setCallable($function)->setContentType($contentType)->setHeaders($headers);
		$httpRequest->getData($keepAlive);
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
	 * @deprecated @see ManiaControl\Files\AsyncHttpRequest
	 */
	public function postData($url, callable $function, $content, $compression = false, $contentType = 'text/xml; charset=UTF-8;', $headers = array()) {
		$httpRequest = new AsyncHttpRequest($this->maniaControl, $url);
		$httpRequest->setCallable($function)->setContent($content)->setCompression($compression)->setContentType($contentType)->setHeaders($headers);
		$httpRequest->postData();
	}

	/**
	 * Add a Request to the queue, DO NOT CALL MANUALLY!
	 *
	 * @param Request $request
	 */
	public function addRequest(Request $request) {
		$queueId = $request->getSerialize()
			? self::QUEUE_SERIALIZING
			: self::QUEUE_NONSERIALIZING;
		$queue = $this->requestQueue[$queueId];
		$request->attachTo($queue);
	}
}
