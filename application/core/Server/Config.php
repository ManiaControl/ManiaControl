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
		$this->id    = $id;
		$this->host  = $host;
		$this->port  = $port;
		$this->login = $login;
		$this->pass  = $pass;
	}
}
