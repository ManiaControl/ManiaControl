<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element joining a Server
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class JoinServer implements Element {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'join_server';
	protected $login = '';
	protected $ip = null;
	protected $port = null;

	/**
	 * Create a new JoinServer Element
	 *
	 * @param string $login (optional) Server Login
	 * @return \FML\ManiaCode\JoinServer
	 */
	public static function create($login = null) {
		$joinServer = new JoinServer($login);
		return $joinServer;
	}

	/**
	 * Construct a new JoinServer Element
	 *
	 * @param string $login (optional) Server Login
	 */
	public function __construct($login = null) {
		if ($login !== null) {
			$this->setLogin($login);
		}
	}

	/**
	 * Set the Server Login
	 *
	 * @param string $login Server Login
	 * @return \FML\ManiaCode\JoinServer
	 */
	public function setLogin($login) {
		$this->login = (string)$login;
		$this->ip    = null;
		$this->port  = null;
		return $this;
	}

	/**
	 * Set the Server Ip and Port
	 *
	 * @param string $ip   Server Ip
	 * @param int    $port Server Port
	 * @return \FML\ManiaCode\JoinServer
	 */
	public function setIp($ip, $port) {
		$this->ip    = (string)$ip;
		$this->port  = (int)$port;
		$this->login = null;
		return $this;
	}

	/**
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		if ($this->ip === null) {
			$loginElement = $domDocument->createElement('login', $this->login);
			$xmlElement->appendChild($loginElement);
		} else {
			$ipElement = $domDocument->createElement('ip', $this->ip . ':' . $this->port);
			$xmlElement->appendChild($ipElement);
		}
		return $xmlElement;
	}
}
