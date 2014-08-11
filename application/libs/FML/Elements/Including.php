<?php

namespace FML\Elements;

use FML\Types\Renderable;

/**
 * Include Element
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Including implements Renderable {
	/*
	 * Protected properties
	 */
	protected $tagName = 'include';
	protected $url = null;

	/**
	 * Create a new Include object
	 *
	 * @param string $url (optional) Include url
	 * @return static
	 */
	public static function create($url = null) {
		return new static($url);
	}

	/**
	 * Construct a new Include object
	 *
	 * @param string $url (optional) Include url
	 */
	public function __construct($url = null) {
		if ($url !== null) {
			$this->setUrl($url);
		}
	}

	/**
	 * Set url
	 *
	 * @param string $url Include url
	 * @return static
	 */
	public function setUrl($url) {
		$this->url = (string)$url;
		return $this;
	}

	/**
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		if ($this->url) {
			$xmlElement->setAttribute('url', $this->url);
		}
		return $xmlElement;
	}
}
