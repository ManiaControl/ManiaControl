<?php

//TODO documentation, finishing

namespace ManiaControl\Files;

use cURL\Event;
use cURL\Request;
use ManiaControl\ManiaControl;

class AsyncHttpRequest {
	/** @var  ManiaControl $maniaControl */
	private $maniaControl;

	private $url;
	private $function;
	private $content;
	private $compression = false;
	private $contentType = 'text/xml; charset=UTF-8;';
	private $headers     = array();

	public function __construct($maniaControl, $url) {
		$this->maniaControl = $maniaControl;
		$this->url          = $url;
	}

	/**
	 * Create a new cURL Request for the given URL
	 *
	 * @param string $url
	 * @return Request
	 */
	private function newRequest($url) {
		$request = new Request($url);
		$request->getOptions()->set(CURLOPT_TIMEOUT, 60)->set(CURLOPT_HEADER, false)// don't display response header
		        ->set(CURLOPT_CRLF, true)// linux line feed
		        ->set(CURLOPT_ENCODING, '')// accept encoding
		        ->set(CURLOPT_USERAGENT, 'ManiaControl v' . ManiaControl::VERSION)// user-agent
		        ->set(CURLOPT_RETURNTRANSFER, true); // return instead of output content
		return $request;
	}

	//TODO merge loadFile / postData
	/**
	 * Carry out a GetData Request
	 *
	 * @param int $keepAlive
	 */
	public function getData($keepAlive = 0) {
		array_push($this->headers, 'Content-Type: ' . $this->contentType);
		if ($keepAlive) {
			array_push($headers, 'Keep-Alive: ' . $keepAlive);
			array_push($headers, 'Connection: Keep-Alive');
		}

		$request = $this->newRequest($this->url);
		$request->getOptions()->set(CURLOPT_AUTOREFERER, true)// accept link reference
		        ->set(CURLOPT_HTTPHEADER, $this->headers); // headers

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

		$fileReader = new AsynchronousFileReader($this->maniaControl);
		$fileReader->addRequest($request);
	}


	/**
	 * Carry out a PostData Request
	 */
	public function postData() {
		array_push($this->headers, 'Content-Type: ' . $this->contentType);
		array_push($this->headers, 'Keep-Alive: timeout=600, max=2000');
		array_push($this->headers, 'Connection: Keep-Alive');

		$content = str_replace(array("\r", "\n"), '', $this->content);
		if ($this->compression) {
			$content = zlib_encode($content, 31);
			array_push($headers, 'Content-Encoding: gzip');
		}


		$request = $this->newRequest($this->url);
		$request->getOptions()->set(CURLOPT_POST, true)// post method
		        ->set(CURLOPT_POSTFIELDS, $content)// post content field
		        ->set(CURLOPT_HTTPHEADER, $this->headers) // headers
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

		$fileReader = new AsynchronousFileReader($this->maniaControl);
		$fileReader->addRequest($request);
	}

	/**
	 * @param $url
	 * @return $this
	 */
	public function setURL($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 * @param callable $function
	 * @return $this
	 */
	public function setCallable($function) {
		$this->function = $function;
		return $this;
	}


	/**
	 * @return mixed
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @param mixed $content
	 * @return $this
	 */
	public function setContent($content) {
		$this->content = $content;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getCompression() {
		return $this->compression;
	}

	/**
	 * @param boolean $compression
	 * @return $this
	 */
	public function setCompression($compression) {
		$this->compression = $compression;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * @param array $headers
	 * @return $this
	 */
	public function setHeaders($headers) {
		$this->headers = $headers;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getContentType() {
		return $this->contentType;
	}

	/**
	 * @param string $contentType
	 * @return $this
	 */
	public function setContentType($contentType) {
		$this->contentType = $contentType;
		return $this;
	}
}