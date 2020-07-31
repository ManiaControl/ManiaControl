<?php

namespace ManiaControl\Server;

/**
 * Model Class holding the Server Config
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Config {
	/*
	 * Public properties
	 */
	public $id = null;
	public $host = null;
	public $port = null;
	public $user = null;
	public $pass = null;

	/**
	 * Create a new server config instance
	 *
	 * @param mixed $id
	 * @param mixed $host
	 * @param mixed $port
	 * @param mixed $user
	 * @param mixed $pass
	 */
	public function __construct($id = null, $host = null, $port = null, $user = null, $pass = null) {
		$this->id   = $this->extractConfigData($id);
		$this->host = $this->extractConfigData($host);
		$this->port = $this->extractConfigData($port);
		$this->user = $this->extractConfigData($user);
		$this->pass = $this->extractConfigData($pass);
	}

	/**
	 * Extract the actual Data from the given Config Param
	 *
	 * @param mixed $configParam
	 * @return string
	 */
	private function extractConfigData($configParam) {
		if (is_array($configParam)) {
			return (string)reset($configParam);
		}
		return (string)$configParam;
	}

	/**
	 * Validate the Config Data
	 *
	 * @param string $message
	 * @return bool
	 */
	public function validate(&$message = null) {
		// Host
		if (!$this->host) {
			$message = 'Missing Host!';
			return false;
		}

		// Port
		if (!$this->port || $this->port === 'port') {
			$message = 'Missing Port!';
			return false;
		}

		// User
		if (!$this->user) {
			$message = 'Missing User!';
			return false;
		}
		if (!in_array($this->user, array('SuperAdmin', 'Admin', 'User'))) {
			$message = 'Invalid User!';
			return false;
		}

		// Pass
		if (!$this->pass) {
			$message = 'Missing Pass!';
			return false;
		}

		return true;
	}
}
