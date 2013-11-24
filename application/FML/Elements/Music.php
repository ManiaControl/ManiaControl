<?php

namespace FML\Elements;

/**
 * Class representing music
 *
 * @author steeffeen
 */
class Music implements Renderable {
	/**
	 * Protected properties
	 */
	protected $data = '';
	protected $tagName = 'music';

	/**
	 * Set data
	 *
	 * @param string $data        	
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

?>
