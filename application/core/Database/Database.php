<?php

namespace ManiaControl\Database;

use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;

/**
 * Database Connection Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Database implements TimerListener {
	/*
	 * Public Properties
	 */
	public $mysqli = null;
	public $migrationHelper = null;

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	/** @var Config $config */
	private $config = null;

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
		if ($this->mysqli->connect_error) {
			$message = "Couldn't connect to Database: '{$this->mysqli->connect_error}'";
			$this->maniaControl->quit($message, true);
		}
		$this->mysqli->set_charset("utf8");

		$this->initDatabase();
		$this->optimizeTables();

		// Register Method which checks the Database Connection every 5 seconds
		$this->maniaControl->timerManager->registerTimerListening($this, 'checkConnection', 5000);

		// Create migration helper
		$this->migrationHelper = new MigrationHelper($maniaControl);
	}

	/**
	 * Load the Database Config
	 */
	private function loadConfig() {
		$databaseElements = $this->maniaControl->config->xpath('database');
		if (!$databaseElements) {
			trigger_error('No Database configured!', E_USER_ERROR);
		}
		$databaseElement = $databaseElements[0];

		// Host
		$hostElements = $databaseElement->xpath('host');
		if (!$hostElements) {
			trigger_error("Invalid database configuration (Host).", E_USER_ERROR);
		}
		$host = (string)$hostElements[0];

		// Port
		$portElements = $databaseElement->xpath('port');
		if (!$portElements) {
			trigger_error("Invalid database configuration (Port).", E_USER_ERROR);
		}
		$port = (string)$portElements[0];

		// User
		$userElements = $databaseElement->xpath('user');
		if (!$userElements) {
			trigger_error("Invalid database configuration (User).", E_USER_ERROR);
		}
		$user = (string)$userElements[0];

		// Pass
		$passElements = $databaseElement->xpath('pass');
		if (!$passElements) {
			trigger_error("Invalid database configuration (Pass).", E_USER_ERROR);
		}
		$pass = (string)$passElements[0];

		// Name
		$nameElements = $databaseElement->xpath('name');
		if (!$nameElements) {
			$nameElements = $databaseElement->xpath('db_name');
		}
		if (!$nameElements) {
			trigger_error("Invalid database configuration (Name).", E_USER_ERROR);
		}
		$name = (string)$nameElements[0];

		// Create config object
		$config = new Config($host, $port, $user, $pass, $name);
		if (!$config->validate()) {
			$this->maniaControl->quit("Your config file doesn't seem to be maintained properly. Please check the database configuration again!", true);
		}
		$this->config = $config;
	}

	/**
	 * Connect to the defined Database
	 *
	 * @return bool
	 */
	private function initDatabase() {
		// Try to connect
		$result = $this->mysqli->select_db($this->config->name);
		if ($result) {
			return true;
		}
		$this->maniaControl->log("Database '{$this->config->name}' doesn't exit! Trying to create it...");

		// Create database
		$databaseQuery = "CREATE DATABASE " . $this->mysqli->escape_string($this->config->name) . ";";
		$this->mysqli->query($databaseQuery);
		if ($this->mysqli->error) {
			$this->maniaControl->quit($this->mysqli->error, true);
			return false;
		}

		// Connect to new database
		$this->mysqli->select_db($this->config->name);
		if ($this->mysqli->error) {
			$message = "Couldn't select database '{$this->config->name}'. {$this->mysqli->error}";
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
		$showQuery = "SHOW TABLES;";
		$result    = $this->mysqli->query($showQuery);
		if ($this->mysqli->error) {
			trigger_error($this->mysqli->error);
			return false;
		}
		$count = $result->num_rows;
		if ($count <= 0) {
			$result->free();
			return true;
		}
		$optimizeQuery = "OPTIMIZE TABLE ";
		$index         = 0;
		while ($row = $result->fetch_row()) {
			$tableName = $row[0];
			$optimizeQuery .= "`{$tableName}`";
			if ($index < $count - 1) {
				$optimizeQuery .= ", ";
			}
			$index++;
		}
		$result->free();
		$optimizeQuery .= ";";
		$this->mysqli->query($optimizeQuery);
		if ($this->mysqli->error) {
			trigger_error($this->mysqli->error);
			return false;
		}
		return true;
	}

	/**
	 * Check whether the Database Connection is still open
	 */
	public function checkConnection() {
		if (!$this->mysqli || !$this->mysqli->ping()) {
			$this->maniaControl->quit('The MySQL Server has gone away!', true);
		}
	}

	/**
	 * Destruct Database Connection
	 */
	public function __destruct() {
		if ($this->mysqli && !$this->mysqli->connect_error) {
			$this->mysqli->close();
		}
	}
}
