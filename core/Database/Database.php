<?php

namespace ManiaControl\Database;

use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;

/**
 * Database Connection Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Database implements TimerListener {
	/*
	 * Public properties
	 */
	/** @var \mysqli $mysqli
	 * @deprecated
	 * @see Database::getMysqli()
	 */
	public $mysqli = null;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var Config $config */
	private $config = null;
	/** @var MigrationHelper $migrationHelper */
	private $migrationHelper = null;

	/**
	 * Construct a new Database Connection
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Enable mysqli Reconnect
		ini_set('mysqli.reconnect', 'on');

		// Open database connection
		$this->loadConfig();
		$this->mysqli = @new \mysqli($this->config->host, $this->config->user, $this->config->pass, null, $this->config->port);
		if ($connectError = $this->getMysqli()->connect_error) {
			$message = "Couldn't connect to Database: '{$connectError}'";
			$this->maniaControl->quit($message, true);
			return;
		}
		$this->getMysqli()->set_charset("utf8");

		$this->initDatabase();
		$this->optimizeTables();

		// Register Method which checks the Database Connection every 5 seconds
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'checkConnection', 5000);

		// Children
		$this->migrationHelper = new MigrationHelper($maniaControl);
	}

	/**
	 * Load the Database Config
	 */
	private function loadConfig() {
		$databaseElements = $this->maniaControl->getConfig()->xpath('database');
		if (!$databaseElements) {
			$this->maniaControl->quit('No Database configured!', true);
		}
		$databaseElement = $databaseElements[0];

		// Host
		$hostElements = $databaseElement->xpath('host');
		if (!$hostElements) {
			$this->maniaControl->quit("Invalid database configuration (Host).", true);
		}
		$host = (string) $hostElements[0];

		// Port
		$portElements = $databaseElement->xpath('port');
		if (!$portElements) {
			$this->maniaControl->quit("Invalid database configuration (Port).", true);
		}
		$port = (string) $portElements[0];

		// User
		$userElements = $databaseElement->xpath('user');
		if (!$userElements) {
			$this->maniaControl->quit("Invalid database configuration (User).", true);
		}
		$user = (string) $userElements[0];

		// Pass
		$passElements = $databaseElement->xpath('pass');
		if (!$passElements) {
			$this->maniaControl->quit("Invalid database configuration (Pass).", true);
		}
		$pass = (string) $passElements[0];

		// Name
		$nameElements = $databaseElement->xpath('name');
		if (!$nameElements) {
			$nameElements = $databaseElement->xpath('db_name');
		}
		if (!$nameElements) {
			$this->maniaControl->quit("Invalid database configuration (Name).", true);
		}
		$name = (string) $nameElements[0];

		// Create config object
		$config = new Config($host, $port, $user, $pass, $name);
		if (!$config->validate()) {
			$this->maniaControl->quit("Your config file doesn't seem to be maintained properly. Please check the database configuration again!", true);
		}
		$this->config = $config;
	}

	/**
	 * Return the mysqli instance
	 *
	 * @return \mysqli
	 */
	public function getMysqli() {
		return $this->mysqli;
	}

	/**
	 * Connect to the defined Database
	 *
	 * @return bool
	 */
	private function initDatabase() {
		// Try to connect
		$result = $this->getMysqli()->select_db($this->config->name);
		if ($result) {
			return true;
		}
		Logger::logInfo("Database '{$this->config->name}' doesn't exist! Trying to create it...");

		// Create database
		$databaseQuery = "CREATE DATABASE " . $this->getMysqli()->escape_string($this->config->name) . ";";
		$this->getMysqli()->query($databaseQuery);
		if ($this->getMysqli()->error) {
			$this->maniaControl->quit($this->getMysqli()->error, true);
			return false;
		}

		// Connect to new database
		$this->getMysqli()->select_db($this->config->name);
		if ($error = $this->getMysqli()->error) {
			$message = "Couldn't select database '{$this->config->name}'. {$error}";
			$this->maniaControl->quit($message, true);
			return false;
		}

		return true;
	}

	/**
	 * Optimize all existing Tables
	 *
	 * @return bool
	 */
	private function optimizeTables() {
		$showQuery = 'SHOW TABLES;';
		$result    = $this->getMysqli()->query($showQuery);
		if ($error = $this->getMysqli()->error) {
			Logger::logError($error);
			return false;
		}
		$count = $result->num_rows;
		if ($count <= 0) {
			$result->free();
			return true;
		}
		$optimizeQuery = 'OPTIMIZE TABLE ';
		$index         = 0;
		while ($row = $result->fetch_row()) {
			$tableName = $row[0];
			$optimizeQuery .= "`{$tableName}`";
			if ($index < $count - 1) {
				$optimizeQuery .= ',';
			}
			$index++;
		}
		$result->free();
		$optimizeQuery .= ';';
		$this->getMysqli()->query($optimizeQuery);
		if ($error = $this->getMysqli()->error) {
			Logger::logError($error);
			return false;
		}
		return true;
	}

	/**
	 * Return the database config
	 *
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Return the migration helper
	 *
	 * @return MigrationHelper
	 */
	public function getMigrationHelper() {
		return $this->migrationHelper;
	}

	/**
	 * Check whether the Database Connection is still open
	 */
	public function checkConnection() {
		if (!$this->getMysqli() || !@$this->getMysqli()->ping()) {
			$this->maniaControl->quit('The MySQL Server has gone away!', true);
		}
	}

	/**
	 * Destruct Database Connection
	 */
	public function __destruct() {
		if ($this->getMysqli() && !$this->getMysqli()->connect_error) {
			$this->getMysqli()->close();
		}
	}
}
