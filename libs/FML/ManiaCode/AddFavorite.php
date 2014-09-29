<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element adding a server as favorite
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AddFavorite extends Element {
	/*
	 * Protected properties
	 */
	protected $tagName = 'add_favourite';
	protected $login = null;
	protected $serverIp = null;
	protected $serverPort = null;

	/**
	 * Create a new AddFavorite object
	 *
	 * @param string $login (optional) Server login
	 * @return static
	 */
	public static function create($login = null) {
		return new static($login);
	}

	/**
	 * Construct a new AddFavorite object
	 *
	 * @param string $login (optional) Server login
	 */
	public function __construct($login = null) {
		if ($login !== null) {
			$this->setLogin($login);
		}
	}

	/**
	 * Set the server login
	 *
	 * @param string $login Server login
	 * @return static
	 */
	public function setLogin($login) {
		$this->login      = (string)$login;
		$this->serverIp   = null;
		$this->serverPort = null;
		return $this;
	}

	/**
	 * Set the server ip and port
	 *
	 * @param string $serverIp   Server ip
	 * @param int    $serverPort Server port
	 * @return static
	 */
	public function setIp($serverIp, $serverPort) {
		$this->serverIp   = (string)$serverIp;
		$this->serverPort = (int)$serverPort;
		$this->login      = null;
		return $this;
	}

	/**
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->serverIp === null) {
			$loginElement = $domDocument->createElement('login', $this->login);
			$xmlElement->appendChild($loginElement);
		} else {
			$ipElement = $domDocument->createElement('ip', $this->serverIp . ':' . $this->serverPort);
			$xmlElement->appendChild($ipElement);
		}
		return $xmlElement;
	}
}
