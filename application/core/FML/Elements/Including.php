<?php

namespace FML\Elements;

/**
 * Class representing include
 *
 * @author steeffeen
 */
class Including implements Renderable {
	/**
	 * Protected properties
	 */
	protected $url = '';
	protected $tagName = 'include';

	/**
	 * Set url
	 *
	 * @param string $url        	
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
		$xml->setAttribute('url', $this->url);
		return $xml;
	}
}

?>
