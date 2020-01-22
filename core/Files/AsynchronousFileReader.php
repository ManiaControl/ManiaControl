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
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AsynchronousFileReader implements UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const CONTENT_TYPE_JSON = 'application/json';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/** @var \cURL\RequestsQueue|null $requestQueue */
	private $requestQueue = null;

	/**
	 * Construct a new Asynchronous File Reader Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->requestQueue = new RequestsQueue();
	}

	/**
	 * Append available Data of active Requests
	 */
	public function appendData() {
		do {
			if (($count = $this->requestQueue->count()) == 0) {
				break;
			}

			if ($this->requestQueue->socketPerform()) {
				$this->requestQueue->socketSelect();
			}
		} while ($count != $this->requestQueue->count());
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
		$request->attachTo($this->requestQueue);
	}
}
