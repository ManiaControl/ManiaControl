<?php

namespace FML\Controls;

/**
 * Class representing video (CMlMediaPlayer)
 *
 * @author steeffeen
 */
class Video extends Control implements Playable, Scriptable {

	/**
	 * Construct a new Video Control
	 *
	 * @param string $id
	 *        	Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'video';
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
