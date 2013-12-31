<?php

namespace FML\Elements;

/**
 * Class representing music
 *
 * @author steeffeen
 */
class Music implements Renderable {
	/**
	 * Protected Properties
	 */
	protected $data = '';
	protected $tagName = 'music';

	/**
	 * Set Data Url
	 *
	 * @param string $data
	 *        	Media Url
	 * @return \FML\Elements\Music
	 */
	public function setData($data) {
		$this->data = $data;
		return $this;
	}

	/**
	 *
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = $domDocument->createElement($this->tagName);
		if ($this->data) {
			$xml->setAttribute('data', $this->data);
		}
		return $xml;
	}
}
