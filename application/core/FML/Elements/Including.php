<?php

namespace FML\Elements;

use FML\Types\Renderable;

/**
 * Include Element
 *
 * @author steeffeen
 */
class Including implements Renderable {
	/**
	 * Protected Properties
	 */
	protected $tagName = 'include';
	protected $url = '';

	/**
	 * Construct a new Include Element
	 *
	 * @param string $url (optional) Include Url
	 * @return \FML\Elements\Including
	 */
	public static function create($url = null) {
		$including = new Including($url);
		return $including;
	}

	/**
	 * Construct a new Include Element
	 *
	 * @param string $url (optional) Include Url
	 */
	public function __construct($url = null) {
		if ($url !== null) {
			$this->setUrl($url);
		}
	}

	/**
	 * Set Url
	 *
	 * @param string $url Include Url
	 */
	public function setUrl($url) {
		$this->url = (string) $url;
	}

	/**
	 *
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		if ($this->url) {
			$xmlElement->setAttribute('url', $this->url);
		}
		return $xmlElement;
	}
}
