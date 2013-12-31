<?php

namespace FML\Controls;

/**
 * Class representing Audio (CMlMediaPlayer)
 *
 * @author steeffeen
 */
class Audio extends Control implements Playable, Scriptable {

	/**
	 * Construct a new Audio Control
	 *
	 * @param string $id
	 *        	Control Id
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
