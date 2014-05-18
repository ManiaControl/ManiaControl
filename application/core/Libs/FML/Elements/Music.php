<?php

namespace FML\Elements;

use FML\Types\Renderable;

/**
 * Music Element
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Music implements Renderable {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'music';
	protected $data = '';

	/**
	 * Create a new Music Element
	 *
	 * @param string $data (optional) Media Url
	 * @return \FML\Elements\Music
	 */
	public static function create($data = null) {
		$music = new Music($data);
		return $music;
	}

	/**
	 * Construct a new Music Element
	 *
	 * @param string $data (optional) Media Url
	 */
	public function __construct($data = null) {
		if ($data !== null) {
			$this->setData($data);
		}
	}

	/**
	 * Set Data Url
	 *
	 * @param string $data Media Url
	 * @return \FML\Elements\Music
	 */
	public function setData($data) {
		$this->data = (string)$data;
		return $this;
	}

	/**
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
