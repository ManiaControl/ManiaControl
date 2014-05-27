<?php

namespace ManiaControl\Server;

/**
 * Model Class holding the Server Config
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Config {
	/*
	 * Public Properties
	 */
	public $id = null;
	public $host = null;
	public $port = null;
	public $login = null;
	public $pass = null;

	/**
	 * Create a new Server Config Instance
	 *
	 * @param string $id    Config Id
	 * @param string $host  Server Ip
	 * @param string $port  Server Port
	 * @param string $login XmlRpc Login
	 * @param string $pass  XmlRpc Password
	 */
	public function __construct($id = null, $host = null, $port = null, $login = null, $pass = null) {
		$this->id    = (string)$id;
		$this->host  = (string)$host;
		$this->port  = (int)$port;
		$this->login = (string)$login;
		$this->pass  = (string)$pass;
	}

	/**
	 * Validate the Config Data
	 *
	 * @return bool
	 */
	public function validate() {
		$invalid = false;
		if (!$this->host) {
			$invalid = true;
		} else if (!$this->port || $this->port === 'port') {
			$invalid = true;
		}
		if ($invalid) {
			return false;
		}
		return true;
	}
}
