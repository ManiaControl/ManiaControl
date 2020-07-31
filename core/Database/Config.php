<?php

namespace ManiaControl\Database;

/**
 * Model Class holding the Database Config
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Config {
	/*
	 * Public properties
	 */
	public $host = null;
	public $port = null;
	public $user = null;
	public $pass = null;
	public $name = null;

	/**
	 * Create a new Database Config Instance
	 *
	 * @param string $host
	 * @param string $port
	 * @param string $user
	 * @param string $pass
	 * @param string $name
	 */
	public function __construct($host = null, $port = null, $user = null, $pass = null, $name = null) {
		$this->host = (string)$host;
		$this->port = (int)$port;
		$this->user = (string)$user;
		$this->pass = (string)$pass;
		$this->name = (string)$name;
	}

	/**
	 * Validate the Config Data
	 *
	 * @return bool
	 */
	public function validate() {
		if (!$this->host || !$this->port || !$this->user || !$this->name) {
			return false;
		}
		if ($this->user === 'mysql_user' || $this->pass === 'mysql_password') {
			return false;
		}
		if ($this->name === 'database_name') {
			return false;
		}
		return true;
	}
}
