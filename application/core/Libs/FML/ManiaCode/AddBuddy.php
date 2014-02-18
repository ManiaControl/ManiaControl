<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element adding a Buddy
 *
 * @author steeffeen
 */
class AddBuddy implements Element {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'add_buddy';
	protected $login = '';

	/**
	 * Construct a new AddBuddy Element
	 *
	 * @param string $login (optional) Buddy Login
	 * @return \FML\ManiaCode\AddBuddy
	 */
	public static function create($login = null) {
		$addBuddy = new AddBuddy($login);
		return $addBuddy;
	}

	/**
	 * Construct a new AddBuddy Element
	 *
	 * @param string $login (optional) Buddy Login
	 */
	public function __construct($login = null) {
		if ($login !== null) {
			$this->setLogin($login);
		}
	}

	/**
	 * Set the Buddy Login
	 *
	 * @param string $login Buddy Login
	 * @return \FML\ManiaCode\AddBuddy
	 */
	public function setLogin($login) {
		$this->login = (string) $login;
		return $this;
	}

	/**
	 *
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		$loginElement = $domDocument->createElement('login', $this->login);
		$xmlElement->appendChild($loginElement);
		return $xmlElement;
	}
}
