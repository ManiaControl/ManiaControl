<?php

namespace FML\Elements;

use FML\Types\Renderable;

/**
 * Music Element
 *
 * @author steeffeen
 */
class Music implements Renderable {
	/**
	 * Protected Properties
	 */
	protected $tagName = 'music';
	protected $data = '';

	/**
	 * Set Data Url
	 *
	 * @param string $data Media Url
	 * @return \FML\Elements\Music
	 */
	public function setData($data) {
		$this->data = (string) $data;
		return $this;
	}

	/**
	 *
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		if ($this->data) {
			$xmlElement->setAttribute('data', $this->data);
		}
		return $xmlElement;
	}
}
