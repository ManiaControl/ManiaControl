<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element for joining a server
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class JoinServer implements Element {
	/*
	 * Protected properties
	 */
	protected $tagName = 'join_server';
	protected $login = null;
	protected $serverIp = null;
	protected $serverPort = null;

	/**
	 * Create a new JoinServer object
	 *
	 * @param string $login (optional) Server login
	 * @return \FML\ManiaCode\JoinServer|static
	 */
	public static function create($login = null) {
		return new static($login);
	}

	/**
	 * Construct a new JoinServer object
	 *
	 * @param string $login (optional) Server login
	 */
	public function __construct($login = null) {
		if (!is_null($login)) {
			$this->setLogin($login);
		}
	}

	/**
	 * Set the server login
	 *
	 * @param string $login Server login
	 * @return \FML\ManiaCode\JoinServer|static
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
	 * @return \FML\ManiaCode\JoinServer|static
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
		$xmlElement = $domDocument->createElement($this->tagName);
		if (is_null($this->serverIp)) {
			$loginElement = $domDocument->createElement('login', $this->login);
			$xmlElement->appendChild($loginElement);
		} else {
			$ipElement = $domDocument->createElement('ip', $this->serverIp . ':' . $this->serverPort);
			$xmlElement->appendChild($ipElement);
		}
		return $xmlElement;
	}
}
