<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element showing a Message
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ShowMessage extends Element {
	/*
	 * Protected properties
	 */
	protected $tagName = 'show_message';
	protected $message = null;

	/**
	 * Create a new ShowMessage object
	 *
	 * @param string $message (optional) Message text
	 * @return static
	 */
	public static function create($message = null) {
		return new static($message);
	}

	/**
	 * Construct a new ShowMessage object
	 *
	 * @param string $message (optional) Message text
	 */
	public function __construct($message = null) {
		if (!is_null($message)) {
			$this->setMessage($message);
		}
	}

	/**
	 * Set the message text
	 *
	 * @param string $message Message text
	 * @return static
	 */
	public function setMessage($message) {
		$this->message = (string)$message;
		return $this;
	}

	/**
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement     = parent::render($domDocument);
		$messageElement = $domDocument->createElement('message', $this->message);
		$xmlElement->appendChild($messageElement);
		return $xmlElement;
	}
}
