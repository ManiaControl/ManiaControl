<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element going to a Link
 *
 * @author steeffeen
 */
class Go_To implements Element {
	/**
	 * Protected Properties
	 */
	protected $tagName = 'goto';
	protected $link = '';

	/**
	 * Create a new Go_To Element
	 *
	 * @param string $link (optional) Goto Link
	 * @return \FML\ManiaCode\Go_To
	 */
	public static function create($link = null) {
		$goTo = new Go_To($link);
		return $goTo;
	}

	/**
	 * Construct a new Go_To Element
	 *
	 * @param string $link (optional) Goto Link
	 */
	public function __construct($link = null) {
		if ($link !== null) {
			$this->setLink($link);
		}
	}

	/**
	 * Set the Goto Link
	 *
	 * @param string $link Goto Link
	 * @return \FML\ManiaCode\Go_To
	 */
	public function setLink($link) {
		$this->link = (string) $link;
		return $this;
	}

	/**
	 *
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		$linkElement = $domDocument->createElement('link', $this->link);
		$xmlElement->appendChild($linkElement);
		return $xmlElement;
	}
}
