<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element adding a buddy
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AddBuddy extends Element {
	/*
	 * Protected properties
	 */
	protected $tagName = 'add_buddy';
	protected $login = null;

	/**
	 * Create a new AddBuddy Element
	 *
	 * @param string $login (optional) Buddy login
	 * @return static
	 */
	public static function create($login = null) {
		return new static($login);
	}

	/**
	 * Construct a new AddBuddy Element
	 *
	 * @param string $login (optional) Buddy login
	 */
	public function __construct($login = null) {
		if (!is_null($login)) {
			$this->setLogin($login);
		}
	}

	/**
	 * Set the buddy login
	 *
	 * @param string $login Buddy login
	 * @return static
	 */
	public function setLogin($login) {
		$this->login = (string)$login;
		return $this;
	}

	/**
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement   = parent::render($domDocument);
		$loginElement = $domDocument->createElement('login', $this->login);
		$xmlElement->appendChild($loginElement);
		return $xmlElement;
	}
}
