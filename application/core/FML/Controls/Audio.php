<?php

namespace FML\Controls;

/**
 * Class representing audio (CMlMediaPlayer)
 *
 * @author steeffeen
 */
class Audio extends Control implements Playable, Scriptable {

	/**
	 * Construct a new audio control
	 *
	 * @param string $id        	
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'audio';
	}

	/**
	 *
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = parent::render($domDocument);
		return $xml;
	}
}
