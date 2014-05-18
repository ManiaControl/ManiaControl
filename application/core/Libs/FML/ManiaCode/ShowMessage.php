<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element showing a Message
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ShowMessage implements Element {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'show_message';
	protected $message = '';

	/**
	 * Create a new ShowMessage Element
	 *
	 * @param string $message (optional) Message Text
	 * @return \FML\ManiaCode\ShowMessage
	 */
	public static function create($message = null) {
		$showMessage = new ShowMessage($message);
		return $showMessage;
	}

	/**
	 * Construct a new ShowMessage Element
	 *
	 * @param string $message (optional) Message Text
	 */
	public function __construct($message = null) {
		if ($message !== null) {
			$this->setMessage($message);
		}
	}

	/**
	 * Set the displayed Message Text
	 *
	 * @param string $message Message Text
	 * @return \FML\ManiaCode\ShowMessage
	 */
	public function setMessage($message) {
		$this->message = (string)$message;
		return $this;
	}

	/**
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement     = $domDocument->createElement($this->tagName);
		$messageElement = $domDocument->createElement('message', $this->message);
		$xmlElement->appendChild($messageElement);
		return $xmlElement;
	}
}
