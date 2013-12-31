<?php

namespace FML\Elements;

/**
 * Class representing include
 *
 * @author steeffeen
 */
class Including implements Renderable {
	/**
	 * Protected Properties
	 */
	protected $url = '';
	protected $tagName = 'include';

	/**
	 * Set Url
	 *
	 * @param string $url
	 *        	Include Url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}

	/**
	 *
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = $domDocument->createElement($this->tagName);
		if ($this->url) {
			$xml->setAttribute('url', $this->url);
		}
		return $xml;
	}
}
